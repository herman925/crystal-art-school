#!/usr/bin/env python3
"""End-to-end reset test for the isolated PR146 WordPress/Mailpit stack only.

Required environment:
  CAS_RECOVERY_TEST_BASE_URL=http://127.0.0.1:18089
  CAS_RECOVERY_MAILPIT_URL=http://127.0.0.1:18026
  CAS_RECOVERY_WP_CONTAINER=cas-pr146-w1-20261019-wp-1
  CAS_RECOVERY_MAILPIT_CONTAINER=cas-pr146-w1-20261019-mailpit-1

Creates one synthetic subscriber in that disposable local database. Sends reset mail only
through the local Mailpit sink. Never prints passwords, reset keys, mail bodies or tokens.
"""

from __future__ import annotations

import hashlib
import html
import json
import os
import re
import secrets
import subprocess
import unittest
from unittest.mock import patch
from http.cookiejar import CookieJar
from html.parser import HTMLParser
from pathlib import Path
from urllib.error import HTTPError
from urllib.parse import parse_qs, quote, urlencode, urljoin, urlsplit, urlunsplit
from urllib.request import HTTPRedirectHandler, HTTPCookieProcessor, ProxyHandler, Request, build_opener


PROJECT = "cas-pr146-w1-20261019"
EXPECTED_BASE_URL = "http://127.0.0.1:18089"
EXPECTED_MAILPIT_URL = "http://127.0.0.1:18026"
EXPECTED_WP_CONTAINER = PROJECT + "-wp-1"
EXPECTED_DB_CONTAINER = PROJECT + "-db-1"
EXPECTED_MAILPIT_CONTAINER = PROJECT + "-mailpit-1"
EXPECTED_NETWORK = PROJECT + "_default"
EXPECTED_WP_VERSIONS = {"7.1.2", "7.1.3"}
ROOT = Path(__file__).resolve().parents[2]
ACCOUNT_SOURCE = ROOT / "wp-content" / "themes" / "hello-elementor-child" / "inc" / "cas-account.php"


def url_origin(url: str) -> tuple[str, str, int] | None:
    try:
        parts = urlsplit(url)
        if not parts.scheme or not parts.hostname or parts.username is not None or parts.password is not None:
            return None
        port = parts.port if parts.port is not None else (443 if parts.scheme.lower() == "https" else 80)
    except ValueError:
        return None
    return parts.scheme.lower(), parts.hostname.lower(), port


def require_same_origin(url: str, expected: str, purpose: str) -> None:
    if url_origin(url) is None or url_origin(url) != url_origin(expected):
        raise AssertionError(f"refusing {purpose} outside the exact local origin")


def require_fixture_url(value: str, expected: str, purpose: str) -> None:
    if value.rstrip("/") != expected:
        raise AssertionError(f"refusing unexpected {purpose} URL")
    require_same_origin(value, expected, purpose)
    parts = urlsplit(value)
    if parts.path not in ("", "/") or parts.query or parts.fragment:
        raise AssertionError(f"refusing unexpected {purpose} URL")


def container_env(info: dict) -> dict[str, str]:
    return dict(item.split("=", 1) for item in info.get("Config", {}).get("Env", []) if "=" in item)


def validate_container(info: dict, name: str, service: str, published: tuple[int, int] | None = None, unpublished: tuple[int, ...] = ()) -> None:
    labels = info.get("Config", {}).get("Labels", {}) or {}
    if info.get("Name", "").lstrip("/") != name:
        raise AssertionError("refusing unexpected recovery fixture container")
    if labels.get("com.docker.compose.project") != PROJECT or labels.get("com.docker.compose.service") != service:
        raise AssertionError("refusing container outside the exact Compose project and service")
    if info.get("State", {}).get("Running") is not True:
        raise AssertionError("recovery fixture container is not running")
    networks = info.get("NetworkSettings", {}).get("Networks", {})
    if EXPECTED_NETWORK not in networks:
        raise AssertionError("recovery fixture container is outside the isolated network")
    ports = info.get("NetworkSettings", {}).get("Ports", {}) or {}
    expected_bindings = set()
    if published:
        internal_port, host_port = published
        expected_bindings.add((f"{internal_port}/tcp", "127.0.0.1", str(host_port)))
    actual_bindings = {
        (port, binding.get("HostIp"), binding.get("HostPort"))
        for port, bindings in ports.items()
        for binding in (bindings or [])
    }
    if actual_bindings != expected_bindings:
        raise AssertionError("recovery fixture port bindings do not match the exact local targets")
    for internal_port in unpublished:
        if ports.get(f"{internal_port}/tcp"):
            raise AssertionError("recovery fixture exposed an internal-only port")


def require_mount(info: dict, destination: str, mount_type: str, read_only: bool, name_prefix: str = "") -> None:
    mounts = info.get("Mounts", [])
    mount = next((item for item in mounts if item.get("Destination") == destination), None)
    if not mount or mount.get("Type") != mount_type or mount.get("RW") is not (not read_only):
        raise AssertionError("recovery fixture mount does not match the expected isolation")
    if name_prefix and not str(mount.get("Name", "")).startswith(name_prefix):
        raise AssertionError("recovery fixture data volume does not match the exact project")


def validate_wp_environment(info: dict) -> None:
    env = container_env(info)
    if (env.get("WORDPRESS_DB_HOST"), env.get("WORDPRESS_DB_NAME"), env.get("WORDPRESS_DB_USER")) != ("db", "wp", "wp"):
        raise AssertionError("WordPress container points to an unexpected database")
    if not env.get("WORDPRESS_DB_PASSWORD"):
        raise AssertionError("WordPress test database credentials are missing")


def validate_db_environment(info: dict) -> None:
    env = container_env(info)
    if (env.get("MARIADB_DATABASE"), env.get("MARIADB_USER")) != ("wp", "wp") or not env.get("MARIADB_PASSWORD"):
        raise AssertionError("database container is not the synthetic recovery database")


def validate_database_pair(wp: dict, db: dict) -> None:
    validate_wp_environment(wp)
    validate_db_environment(db)
    if container_env(wp).get("WORDPRESS_DB_PASSWORD") != container_env(db).get("MARIADB_PASSWORD"):
        raise AssertionError("WordPress and database containers do not share the expected synthetic credentials")


def validate_network(info: dict) -> None:
    labels = info.get("Labels", {}) or {}
    if info.get("Name") != EXPECTED_NETWORK or labels.get("com.docker.compose.project") != PROJECT:
        raise AssertionError("refusing unexpected recovery fixture network")
    if info.get("Driver") != "bridge" or info.get("Internal") is not False:
        raise AssertionError("recovery fixture network is not the approved bridge topology")


def validate_wp_runtime(runtime: dict, account_sha256: str) -> None:
    expected = {
        "home": EXPECTED_BASE_URL,
        "siteurl": EXPECTED_BASE_URL,
        "db_host": "db",
        "db_name": "wp",
        "db_user": "wp",
        "db_prefix": "wp_",
        "theme": "hello-elementor-child",
        "tutor": "4.1.0",
        "active_theme_sha256": account_sha256,
        "mounted_theme_sha256": account_sha256,
        "mailer": "smtp",
        "mail_host": "mailpit",
        "mail_port": 1025,
        "mail_auth": False,
        "mail_auto_tls": False,
        "mail_secure": "",
        "sendmail_path": "/bin/false",
    }
    for key, value in expected.items():
        if runtime.get(key) != value:
            raise AssertionError(f"refusing unexpected local WordPress fixture setting: {key}")
    if runtime.get("wp_version") not in EXPECTED_WP_VERSIONS:
        raise AssertionError("refusing unexpected local WordPress version")
    if "test-mail-sink.php" not in runtime.get("mu_plugins", []):
        raise AssertionError("local Mailpit-only MU plugin is not active")


def docker_inspect(args: list[str]) -> dict:
    proc = subprocess.run(args, check=False, capture_output=True, text=True, timeout=30)
    if proc.returncode:
        raise AssertionError("could not inspect the expected local recovery fixture")
    try:
        result = json.loads(proc.stdout)
    except json.JSONDecodeError as exc:
        raise AssertionError("local recovery fixture inspection returned invalid JSON") from exc
    if not isinstance(result, list) or len(result) != 1 or not isinstance(result[0], dict):
        raise AssertionError("local recovery fixture inspection returned an unexpected result")
    return result[0]


def php_string(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


class VisibleMessageParser(HTMLParser):
    VOID_TAGS = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr"}
    MESSAGE_CLASSES = {"tutor-alert", "notice", "message", "error"}

    def __init__(self) -> None:
        super().__init__()
        self.depth = 0
        self.current: list[str] = []
        self.messages: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = dict(attrs)
        if self.depth:
            if tag not in self.VOID_TAGS:
                self.depth += 1
            return
        classes = set((values.get("class") or "").split())
        if tag in {"div", "p", "section", "span"} and (
            values.get("role") == "alert" or values.get("id") == "login_error" or classes & self.MESSAGE_CLASSES
        ):
            self.depth = 1
            self.current = []

    def handle_data(self, data: str) -> None:
        if self.depth:
            self.current.append(data)

    def handle_endtag(self, tag: str) -> None:
        if not self.depth or tag in self.VOID_TAGS:
            return
        self.depth -= 1
        if self.depth == 0:
            message = " ".join(" ".join(self.current).split())
            if message and message not in self.messages:
                self.messages.append(message)


def visible_messages(source: str, redact: tuple[str, ...] = ()) -> list[str]:
    parser = VisibleMessageParser()
    parser.feed(source)
    results = []
    for message in parser.messages:
        for value in redact:
            if value:
                message = message.replace(value, "[synthetic]")
        results.append(message)
    return results


def visible_marker(source: str) -> str:
    text = html.unescape(re.sub(r"<[^>]*>", " ", source))
    text = " ".join(text.split()).lower()
    if "if an account matches those details, a reset email will be sent" in text:
        return "generic-reset-outcome"
    if "invalid username or email" in text:
        return "invalid-account"
    if "sent an email to this account" in text:
        return "reset-email-sent"
    if "check your email" in text:
        return "reset-email-sent"
    if "passwords do not match" in text:
        return "password-mismatch"
    if "password has been reset" in text:
        return "reset-success"
    if "password reset link" in text and ("invalid" in text or "expired" in text):
        return "invalid-or-expired-key"
    if "invalid key" in text or "expired" in text or "already been used" in text:
        return "invalid-or-expired-key"
    if "enter a username or email address" in text:
        return "username-required"
    return "other-visible-response"


def visible_summary(source: str, redact: tuple[str, ...] = ()) -> str:
    messages = visible_messages(source, redact)
    return " | ".join(messages) if messages else "<no alert text>"


class FormParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.forms: list[dict] = []
        self.current: dict | None = None

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = dict(attrs)
        if tag == "form":
            self.current = {"action": values.get("action") or "", "fields": []}
            self.forms.append(self.current)
        elif tag == "input" and self.current is not None and values.get("name"):
            self.current["fields"].append(
                {"name": values["name"], "value": values.get("value") or "", "type": values.get("type") or ""}
            )

    def handle_endtag(self, tag: str) -> None:
        if tag == "form":
            self.current = None


def find_form(source: str, names: set[str]) -> dict:
    parser = FormParser()
    parser.feed(source)
    for form in parser.forms:
        fields = {item["name"] for item in form["fields"]}
        if names.issubset(fields):
            return form
    raise AssertionError("expected local form fields were absent")


class ExactOriginRedirectHandler(HTTPRedirectHandler):
    def __init__(self, allowed_origin: str) -> None:
        super().__init__()
        self.allowed_origin = allowed_origin

    def redirect_request(self, req, fp, code, msg, headers, newurl):
        require_same_origin(newurl, self.allowed_origin, "redirect")
        return super().redirect_request(req, fp, code, msg, headers, newurl)


class LocalHttp:
    def __init__(self, base_url: str) -> None:
        self.base_url = base_url.rstrip("/")
        require_fixture_url(self.base_url, EXPECTED_BASE_URL, "WordPress")
        self.opener = build_opener(
            ProxyHandler({}),
            HTTPCookieProcessor(CookieJar()),
            ExactOriginRedirectHandler(self.base_url),
        )

    def request(self, url: str, fields: dict[str, str] | None = None) -> tuple[int, str, str]:
        require_same_origin(url, self.base_url, "WordPress request")
        data = urlencode(fields).encode() if fields is not None else None
        request = Request(
            url,
            data=data,
            headers={"User-Agent": "CAS isolated password recovery integration test"},
        )
        try:
            response = self.opener.open(request, timeout=10)
        except HTTPError as error:
            response = error
        with response:
            final_url = response.geturl()
            require_same_origin(final_url, self.base_url, "WordPress response")
            return response.status, final_url, response.read().decode("utf-8", "replace")

    def get(self, path_or_url: str) -> tuple[int, str, str]:
        url = urljoin(self.base_url + "/", path_or_url.lstrip("/")) if path_or_url.startswith("/") else path_or_url
        return self.request(url)

    def post_form(self, page_url: str, form: dict, overrides: dict[str, str]) -> tuple[int, str, str]:
        fields = {item["name"]: item["value"] for item in form["fields"]}
        fields.update(overrides)
        action = urljoin(page_url, form["action"] or page_url)
        return self.request(action, fields)


class TestIsolatedPasswordRecoveryFlow(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.base_url = os.environ.get("CAS_RECOVERY_TEST_BASE_URL", "").strip()
        cls.mailpit_url = os.environ.get("CAS_RECOVERY_MAILPIT_URL", "").strip().rstrip("/")
        cls.wp_container = os.environ.get("CAS_RECOVERY_WP_CONTAINER", "").strip()
        cls.mailpit_container = os.environ.get("CAS_RECOVERY_MAILPIT_CONTAINER", "").strip()
        configured = (cls.base_url, cls.mailpit_url, cls.wp_container, cls.mailpit_container)
        if not any(configured):
            raise unittest.SkipTest("set exact PR146 local URLs and container names to run the integration test")
        if not all(configured):
            raise AssertionError("all exact PR146 local URLs and container names are required")
        require_fixture_url(cls.base_url, EXPECTED_BASE_URL, "WordPress")
        require_fixture_url(cls.mailpit_url, EXPECTED_MAILPIT_URL, "Mailpit")
        if cls.wp_container != EXPECTED_WP_CONTAINER or cls.mailpit_container != EXPECTED_MAILPIT_CONTAINER:
            raise AssertionError("refusing unexpected PR146 fixture container names")
        cls.db_container = EXPECTED_DB_CONTAINER
        cls.client = LocalHttp(cls.base_url)
        cls.preflight()
        identity = "casreset" + secrets.token_hex(6)
        cls.login = identity + "@local"
        cls.email = identity + "@example.test"
        cls.old_password = secrets.token_urlsafe(24)
        cls.new_password = secrets.token_urlsafe(24)
        cls.user_id = cls.create_test_user()

    @classmethod
    def preflight(cls) -> None:
        wp = docker_inspect(["docker", "inspect", cls.wp_container])
        db = docker_inspect(["docker", "inspect", cls.db_container])
        mailpit = docker_inspect(["docker", "inspect", cls.mailpit_container])
        network = docker_inspect(["docker", "network", "inspect", EXPECTED_NETWORK])

        validate_container(wp, EXPECTED_WP_CONTAINER, "wp", published=(80, 18089))
        validate_container(db, EXPECTED_DB_CONTAINER, "db", unpublished=(3306,))
        validate_container(mailpit, EXPECTED_MAILPIT_CONTAINER, "mailpit", published=(8025, 18026), unpublished=(1025,))
        validate_network(network)
        validate_database_pair(wp, db)
        require_mount(wp, "/var/www/html", "volume", read_only=False, name_prefix=PROJECT + "_wpdata")
        require_mount(db, "/var/lib/mysql", "volume", read_only=False, name_prefix=PROJECT + "_dbdata")
        require_mount(wp, "/var/www/html/wp-content/mu-plugins", "bind", read_only=True)
        require_mount(wp, "/theme-src", "bind", read_only=True)
        require_mount(wp, "/usr/local/etc/php/conf.d/zz-cas-test-mail.ini", "bind", read_only=True)

        account_sha256 = hashlib.sha256(ACCOUNT_SOURCE.read_bytes()).hexdigest()
        runtime = cls.wp_json(
            "global $wpdb;require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';$mailer=new \\PHPMailer\\PHPMailer\\PHPMailer(true);do_action('phpmailer_init',$mailer);"
            + "return array('wp_version'=>get_bloginfo('version'),'home'=>get_option('home'),'siteurl'=>get_option('siteurl'),"
            + "'db_host'=>(string)$wpdb->dbhost,'db_name'=>(string)$wpdb->dbname,'db_user'=>(string)DB_USER,"
            + "'db_prefix'=>(string)$wpdb->prefix,'theme'=>get_stylesheet(),"
            + "'tutor'=>defined('TUTOR_VERSION')?TUTOR_VERSION:'missing',"
            + "'active_theme_sha256'=>hash_file('sha256',get_stylesheet_directory().'/inc/cas-account.php'),"
            + "'mounted_theme_sha256'=>hash_file('sha256','/theme-src/inc/cas-account.php'),"
            + "'mu_plugins'=>array_values(array_map('basename',glob(WPMU_PLUGIN_DIR.'/*.php')?:array())),"
            + "'mailer'=>$mailer->Mailer,'mail_host'=>$mailer->Host,'mail_port'=>(int)$mailer->Port,"
            + "'mail_auth'=>(bool)$mailer->SMTPAuth,'mail_auto_tls'=>(bool)$mailer->SMTPAutoTLS,'mail_secure'=>$mailer->SMTPSecure,"
            + "'sendmail_path'=>ini_get('sendmail_path'));"
        )
        validate_wp_runtime(runtime, account_sha256)
        cls.mailpit_messages()

    @classmethod
    def wp_json(cls, body: str) -> dict:
        host = urlsplit(cls.base_url).netloc
        script = (
            "<?php" + chr(10)
            + f"$_SERVER['HTTP_HOST']={php_string(host)};"
            + "$_SERVER['SERVER_NAME']='127.0.0.1';"
            + "$_SERVER['REQUEST_URI']='/';"
            + "require '/var/www/html/wp-load.php';"
            + f"$result=(function(){{{body}}})();echo json_encode($result);"
        )
        proc = subprocess.run(
            ["docker", "exec", "-i", cls.wp_container, "php"],
            check=False,
            capture_output=True,
            input=script,
            text=True,
            timeout=30,
        )
        if proc.returncode:
            raise AssertionError(f"local WP fixture helper failed with exit code {proc.returncode}")
        for line in reversed(proc.stdout.splitlines()):
            try:
                return json.loads(line)
            except json.JSONDecodeError:
                continue
        raise AssertionError("local WP fixture helper returned no JSON")

    @classmethod
    def create_test_user(cls) -> int:
        body = (
            "$id=wp_insert_user(array("
            + "'user_login'=>" + php_string(cls.login) + ","
            + "'user_email'=>" + php_string(cls.email) + ","
            + "'user_pass'=>" + php_string(cls.old_password) + ","
            + "'display_name'=>'Local Recovery Test','role'=>'subscriber'));"
            + "if(is_wp_error($id)){return array('ok'=>false,'error'=>$id->get_error_code());}"
            + "return array('ok'=>true,'id'=>(int)$id);"
        )
        result = cls.wp_json(body)
        if not result.get("ok"):
            raise AssertionError("could not create synthetic local subscriber")
        return int(result["id"])

    @classmethod
    def auth_ok(cls, password: str) -> bool:
        body = (
            "$u=wp_authenticate(" + php_string(cls.login) + "," + php_string(password) + ");"
            + "return !is_wp_error($u);"
        )
        return bool(cls.wp_json(body))

    @classmethod
    def password_hash(cls) -> str:
        body = "$wpdb->get_var($wpdb->prepare('SELECT user_pass FROM '.$wpdb->users.' WHERE ID=%d'," + str(cls.user_id) + "));"
        return str(cls.wp_json("global $wpdb;return " + body))

    def authenticated_cookie_names(self) -> list[str]:
        processor = next((item for item in self.client.opener.handlers if isinstance(item, HTTPCookieProcessor)), None)
        if not processor:
            return []
        return sorted(
            cookie.name
            for cookie in processor.cookiejar
            if cookie.name.startswith(("wordpress_logged_in_", "wordpress_sec_"))
        )

    def assert_tutor_reset_form(self, form: dict) -> None:
        fields = {item["name"]: item["value"] for item in form["fields"]}
        self.assertEqual(fields.get("tutor_action"), "tutor_process_reset_password")
        self.assertTrue(fields.get("reset_key"))
        self.assertTrue(fields.get("user_id"))

    def assert_reset_failure_response(self, status: int, final_url: str, body: str, kind: str, expected_marker: str) -> str:
        self.assertLess(status, 500)
        marker = visible_marker(body)
        path = urlsplit(final_url).path

        if path == "/wp-login.php":
            self.assertEqual(marker, expected_marker)

            return "visible-feedback"

        self.assertEqual(path, "/dashboard/retrieve-password/")
        if kind == "none":
            request_form = find_form(body, {"user_login", "tutor_action"})
            fields = {item["name"]: item["value"] for item in request_form["fields"]}
            self.assertEqual(fields["tutor_action"], "tutor_retrieve_password")
        else:
            self.assertEqual(kind, "tutor")
            self.assert_tutor_reset_form(find_form(body, {"password", "confirm_password"}))
        self.assertEqual(marker, expected_marker)
        self.assertTrue(visible_messages(body), "Tutor reset errors must include a rendered alert")
        return "visible-feedback"

    @classmethod
    def mailpit_get(cls, path: str) -> bytes:
        url = urljoin(cls.mailpit_url + "/", path.lstrip("/"))
        require_same_origin(url, cls.mailpit_url, "Mailpit request")
        opener = build_opener(ProxyHandler({}), ExactOriginRedirectHandler(cls.mailpit_url))
        request = Request(url, method="GET")
        with opener.open(request, timeout=10) as response:
            require_same_origin(response.geturl(), cls.mailpit_url, "Mailpit response")
            return response.read()

    @classmethod
    def mailpit_messages(cls) -> list[dict]:
        messages = []
        while True:
            page = json.loads(cls.mailpit_get(f"/api/v1/messages?start={len(messages)}&limit=50").decode("utf-8"))
            batch = page.get("messages", [])
            messages.extend(batch)
            if len(messages) >= page["total"]:
                return messages
            if not batch:
                raise AssertionError("Mailpit returned an empty page before its reported total")

    @classmethod
    def message_detail(cls, message_id: str) -> dict:
        path = "/api/v1/message/" + quote(message_id, safe="")
        return json.loads(cls.mailpit_get(path).decode("utf-8"))

    @staticmethod
    def message_strings(value) -> list[str]:
        if isinstance(value, str):
            return [value]
        if isinstance(value, dict):
            return sum((TestIsolatedPasswordRecoveryFlow.message_strings(v) for v in value.values()), [])
        if isinstance(value, list):
            return sum((TestIsolatedPasswordRecoveryFlow.message_strings(v) for v in value), [])
        return []

    @classmethod
    def captured_reset_message(cls, old_ids: set[str]) -> tuple[str, str]:
        new = [m for m in cls.mailpit_messages() if str(m.get("ID") or m.get("id") or "") not in old_ids]
        for summary in new:
            msgid = str(summary.get("ID") or summary.get("id") or "")
            if not msgid:
                continue
            detail = cls.message_detail(msgid)
            body = "\n".join(cls.message_strings(detail))
            if cls.email.lower() not in json.dumps(summary).lower() and cls.email.lower() not in body.lower():
                continue
            body = html.unescape(body)
            candidates = re.findall(r"https?://[^\s<>\"']+", body)
            for candidate in candidates:
                candidate = candidate.rstrip(".,);]")
                parts = urlsplit(candidate)
                query = parse_qs(parts.query)
                wp_reset_link = parts.path.endswith("wp-login.php") and query.get("action") == ["rp"] and query.get("key") and query.get("login")
                tutor_reset_link = parts.path.rstrip("/").endswith("/dashboard/retrieve-password") and query.get("reset_key") and query.get("user_id")
                if wp_reset_link or tutor_reset_link:
                    require_same_origin(candidate, cls.base_url, "password reset link")
                    return candidate, msgid
        raise AssertionError("known synthetic user reset email/link was not captured in local Mailpit")

    def visible_outcome(self, status: int, path: str, body: str, label: str, redact: tuple[str, ...] = ()) -> str:
        marker = visible_marker(body)
        messages = visible_messages(body, redact)
        text = " | ".join(messages) if messages else "<no alert text>"
        print(f"{label}: status={status}, path={urlsplit(path).path}, visible={marker}, message={text}")
        return marker

    def request_form(self) -> tuple[str, dict]:
        url = self.base_url + "/dashboard/retrieve-password/"
        status, final_url, body = self.client.get(url)
        form = find_form(body, {"user_login", "tutor_action"})
        self.assertEqual(status, 200)
        self.assertIn("retrieve-password", urlsplit(final_url).path)
        return final_url, form

    def request_reset(self, login: str) -> tuple[int, str, str]:
        final_url, form = self.request_form()
        return self.client.post_form(final_url, form, {"user_login": login})

    def reset_form(self, url: str) -> tuple[int, str, str, dict, str]:
        status, final_url, body = self.client.get(url)
        parser = FormParser()
        parser.feed(body)
        for form in parser.forms:
            names = {item["name"] for item in form["fields"]}
            if {"pass1", "pass2"}.issubset(names) or {"password", "confirm_password"}.issubset(names):
                kind = "wp" if "pass1" in names else "tutor"
                if kind == "tutor":
                    self.assert_tutor_reset_form(form)
                return status, final_url, body, form, kind
        return status, final_url, body, {}, "none"

    def submit_reset(self, url: str, form: dict, kind: str, password: str, confirmation: str) -> tuple[int, str, str]:
        fields = {}
        if kind == "wp":
            fields = {"pass1": password, "pass2": confirmation}
        elif kind == "tutor":
            fields = {"password": password, "confirm_password": confirmation}
        else:
            raise AssertionError("no reset form to submit")
        return self.client.post_form(url, form, fields)

    def email_link_for_request(self, old_ids: set[str]) -> tuple[str, str]:
        return self.captured_reset_message(old_ids)

    def test_password_recovery_email_token_and_credential_matrix(self) -> None:
        version = self.wp_json(
            "return array('wp'=>get_bloginfo('version'),'tutor'=>defined('TUTOR_VERSION')?TUTOR_VERSION:'missing',"
            + "'native_login'=>(string)tutor_utils()->get_option('enable_tutor_native_login',false));"
        )
        self.assertEqual(version["tutor"], "4.1.0")
        print(f"runtime: WordPress {version['wp']}; Tutor {version['tutor']}; native_login={version['native_login']}")
        self.assertTrue(self.auth_ok(self.old_password), "initial synthetic password must authenticate")

        initial_messages = self.mailpit_messages()
        initial_ids = {str(m.get("ID") or m.get("id") or "") for m in initial_messages}

        # A bad nonce and empty username must not send reset mail.
        bad_nonce_url, bad_nonce_form = self.request_form()
        nonce_names = [item["name"] for item in bad_nonce_form["fields"] if "nonce" in item["name"].lower()]
        self.assertTrue(nonce_names, "Tutor recovery form must keep its nonce")
        bad_nonce_fields = {item["name"]: item["value"] for item in bad_nonce_form["fields"]}
        bad_nonce_fields.update({"user_login": self.email, nonce_names[0]: "invalid-local-nonce"})
        bad_nonce_status, bad_nonce_final, bad_nonce_body = self.client.request(urljoin(bad_nonce_url, bad_nonce_form["action"] or bad_nonce_url), bad_nonce_fields)
        print(f"bad nonce request: status={bad_nonce_status}, path={urlsplit(bad_nonce_final).path}, visible={visible_marker(bad_nonce_body)}, message={visible_summary(bad_nonce_body, (self.email, self.login))}")
        self.assertLess(bad_nonce_status, 500)
        self.assertNotEqual(visible_marker(bad_nonce_body), "generic-reset-outcome", "bad nonce must not reach the generic success outcome")
        empty_status, empty_final, empty_body = self.request_reset("")
        print(f"empty username request: status={empty_status}, path={urlsplit(empty_final).path}, visible={visible_marker(empty_body)}, message={visible_summary(empty_body)}")
        self.assertEqual(visible_marker(empty_body), "username-required")
        self.assertEqual(len(self.mailpit_messages()), len(initial_messages), "invalid nonce/empty input must send no mail")

        # Tutor accepts usernames containing @ even when they are not email addresses.
        username_status, _, username_body = self.request_reset(self.login)
        self.assertLess(username_status, 500)
        self.assertEqual(visible_marker(username_body), "generic-reset-outcome")
        after_known_username = len(self.mailpit_messages())
        self.assertEqual(after_known_username, len(initial_messages) + 1)
        unknown_username = "unknown-" + secrets.token_hex(6) + "@local"
        unknown_username_status, _, unknown_username_body = self.request_reset(unknown_username)
        self.assertLess(unknown_username_status, 500)
        self.assertEqual(visible_marker(unknown_username_body), "generic-reset-outcome")
        self.assertEqual(visible_messages(username_body), visible_messages(unknown_username_body))
        self.assertEqual(len(self.mailpit_messages()), after_known_username, "unknown @username must receive no mail")
        print("LOCAL_AT_USERNAME_PASS: known/unknown @local usernames show identical generic alerts; known user alone receives mail")
        initial_ids = {str(m.get("ID") or m.get("id") or "") for m in self.mailpit_messages()}

        known_status, known_final, known_body = self.request_reset(self.email)
        known_marker = self.visible_outcome(known_status, known_final, known_body, "known-account reset request", (self.email, self.login, self.old_password, self.new_password))
        self.assertLess(known_status, 500)
        self.assertEqual(known_marker, "generic-reset-outcome")
        reset_url, _ = self.email_link_for_request(initial_ids)

        unknown = "unknown-" + secrets.token_hex(6) + "@example.test"
        unknown_status, unknown_final, unknown_body = self.request_reset(unknown)
        unknown_marker = self.visible_outcome(unknown_status, unknown_final, unknown_body, "unknown-account reset request", (unknown, self.email, self.login, self.old_password, self.new_password))
        self.assertLess(unknown_status, 500)
        self.assertEqual(unknown_marker, "generic-reset-outcome")
        self.assertEqual(known_marker, unknown_marker, "known and unknown requests must share their visible outcome")
        self.assertEqual(visible_messages(known_body), visible_messages(unknown_body), "known and unknown requests must show the same complete alert")
        self.assertNotIn(unknown.lower(), json.dumps(self.mailpit_messages()).lower(), "unknown account must not receive mail")
        print("known/unknown visible outcome is generic and identical; mail is delivered only for known user")

        # Confirm a bad key and expired key cannot change the synthetic user's password.
        parts = urlsplit(reset_url)
        query = parse_qs(parts.query)
        if "key" in query:
            query["key"] = ["invalid-local-reset-key"]
        else:
            query["reset_key"] = ["invalid-local-reset-key"]
        invalid_url = urlunsplit((parts.scheme, parts.netloc, parts.path, urlencode(query, doseq=True), ""))
        invalid_hash_before = self.password_hash()
        invalid_status, invalid_final, invalid_body, invalid_form, invalid_kind = self.reset_form(invalid_url)
        if invalid_kind != "none":
            invalid_status, invalid_final, invalid_body = self.submit_reset(invalid_final, invalid_form, invalid_kind, self.new_password, self.new_password)
        self.visible_outcome(invalid_status, invalid_final, invalid_body, "invalid reset token", (self.email, self.login, self.old_password, self.new_password))
        self.assert_reset_failure_response(invalid_status, invalid_final, invalid_body, invalid_kind, "invalid-or-expired-key")
        self.assertEqual(self.password_hash(), invalid_hash_before, "invalid token must not change the password hash")
        self.assertTrue(self.auth_ok(self.old_password), "invalid token must not change credentials")
        self.assertFalse(self.auth_ok(self.new_password), "invalid token must not set the new password")
        if invalid_kind == "tutor":
            self.assertEqual(self.authenticated_cookie_names(), [], "invalid token must not sign in the synthetic user")
            dashboard_status, dashboard_final, _ = self.client.get("/dashboard/")
            self.assertLess(dashboard_status, 500)
            self.assertEqual(urlsplit(dashboard_final).path, "/login/", "invalid token must not grant dashboard access")

        # Password mismatch must leave the valid email token usable and original password unchanged.
        status, page_url, body, form, kind = self.reset_form(reset_url)
        self.assertNotEqual(kind, "none", "email link must open a password reset form")
        self.assertLess(status, 500)
        mismatch_hash_before = self.password_hash()
        mismatch_status, mismatch_final, mismatch_body = self.submit_reset(page_url, form, kind, self.new_password, self.new_password + "-mismatch")
        self.visible_outcome(mismatch_status, mismatch_final, mismatch_body, "mismatched passwords", (self.email, self.login, self.old_password, self.new_password))
        self.assert_reset_failure_response(mismatch_status, mismatch_final, mismatch_body, kind, "password-mismatch")
        self.assertEqual(self.password_hash(), mismatch_hash_before, "mismatched passwords must not change the password hash")
        self.assertTrue(self.auth_ok(self.old_password), "mismatched passwords must not change credentials")
        self.assertFalse(self.auth_ok(self.new_password), "mismatched passwords must not set the new password")

        # A correct confirmation resets the synthetic user's password.
        status, page_url, body, form, kind = self.reset_form(reset_url)
        valid_status, valid_final, valid_body = self.submit_reset(page_url, form, kind, self.new_password, self.new_password)
        valid_marker = self.visible_outcome(valid_status, valid_final, valid_body, "valid reset", (self.email, self.login, self.old_password, self.new_password))
        self.assertLess(valid_status, 500)
        if kind == "wp":
            self.assertEqual(valid_marker, "reset-success")
        else:
            self.assertEqual(kind, "tutor")
            self.assertEqual(urlsplit(valid_final).path, "/dashboard/")
            self.assertTrue(self.authenticated_cookie_names(), "Tutor valid reset must sign in the synthetic user")
            dashboard_status, dashboard_final, _ = self.client.get("/dashboard/")
            self.assertLess(dashboard_status, 500)
            self.assertEqual(urlsplit(dashboard_final).path, "/dashboard/")
        self.assertTrue(self.auth_ok(self.new_password), "new password must authenticate")
        self.assertFalse(self.auth_ok(self.old_password), "old password must no longer authenticate")
        valid_password_hash = self.password_hash()

        # Reuse of the consumed token cannot change the password again.
        reused_password_hash = self.password_hash()
        reused_status, reused_final, reused_body, reused_form, reused_kind = self.reset_form(reset_url)
        if reused_kind != "none":
            reused_status, reused_final, reused_body = self.submit_reset(reused_final, reused_form, reused_kind, self.old_password, self.old_password)
        self.visible_outcome(reused_status, reused_final, reused_body, "reused reset token", (self.email, self.login, self.old_password, self.new_password))
        self.assert_reset_failure_response(reused_status, reused_final, reused_body, reused_kind, "invalid-or-expired-key")
        self.assertEqual(self.password_hash(), reused_password_hash, "reused token must not change the password hash")
        self.assertTrue(self.auth_ok(self.new_password), "reused token must not replace the new password")
        self.assertFalse(self.auth_ok(self.old_password), "reused token must not restore the old password")

        # Mint a second token, age only this synthetic user's key in the local disposable DB, then verify expiry.
        before_second = {str(m.get("ID") or m.get("id") or "") for m in self.mailpit_messages()}
        self.request_reset(self.email)
        expired_url, _ = self.email_link_for_request(before_second)
        expired_state = self.wp_json(
            "global $wpdb;$user=get_user_by('ID'," + str(self.user_id) + ");"

            + "$parts=explode(':',$user->user_activation_key,2);"
            + "if(count($parts)!==2){return array('ok'=>false,'format'=>'legacy');}"
            + "$wpdb->update($wpdb->users,array('user_activation_key'=>(string)(time()-3*DAY_IN_SECONDS).':'.$parts[1]),array('ID'=>" + str(self.user_id) + "));"
            + "return array('ok'=>true);"
        )
        self.assertTrue(expired_state.get("ok"), "local WordPress reset key must carry its expiry timestamp")
        expired_password_hash = self.password_hash()
        expired_status, expired_final, expired_body, expired_form, expired_kind = self.reset_form(expired_url)
        if expired_kind != "none":
            expired_status, expired_final, expired_body = self.submit_reset(expired_final, expired_form, expired_kind, self.old_password, self.old_password)
        self.visible_outcome(expired_status, expired_final, expired_body, "expired reset token", (self.email, self.login, self.old_password, self.new_password))
        self.assert_reset_failure_response(expired_status, expired_final, expired_body, expired_kind, "invalid-or-expired-key")
        self.assertEqual(self.password_hash(), expired_password_hash, "expired token must not change the password hash")
        self.assertTrue(self.auth_ok(self.new_password), "expired token must not change credentials")
        self.assertFalse(self.auth_ok(self.old_password), "expired token must not restore the old password")

        # The late WordPress anti-abuse hook runs after Tutor's preflight filter.
        before_late_validation = len(self.mailpit_messages())
        late_validation = self.wp_json(
            "$_SERVER['REQUEST_METHOD']='POST';$_POST['user_login']=" + php_string(self.email) + ";"
            + "$_POST[tutor()->nonce]=wp_create_nonce(tutor()->nonce_action);"
            + "cas_account_password_recovery_start(null);$empty=new WP_Error();do_action('lostpassword_post',$empty);"
            + "$empty_kept=cas_account_password_recovery_request_active()&&!$empty->has_errors();"
            + "cas_account_password_recovery_request_active(false);"
            + "$deny=function($errors){$errors->add('synthetic_rate_limit','Please wait before trying again.');};"
            + "add_action('lostpassword_post',$deny);$preserved=true;"
            + "foreach(array(" + php_string(self.email) + ",'unknown-late-validation@example.test') as $identifier){"
            + "tutor_flash_get('danger');tutor_flash_get('success');$_POST['user_login']=$identifier;"
            + "do_action('tutor_action_tutor_retrieve_password');"
            + "$danger=wp_json_encode(tutor_flash_get('danger'));$success=wp_json_encode(tutor_flash_get('success'));"
            + "$preserved=$preserved&&strpos($danger,'Please wait before trying again.')!==false"
            + "&&strpos($success,'If an account matches')===false;}"
            + "remove_action('lostpassword_post',$deny);"
            + "add_filter('allow_password_reset',function(){return new WP_Error('synthetic_account_policy','Synthetic account policy detail');},PHP_INT_MAX);"
            + "$_POST['user_login']=" + php_string(self.email) + ";do_action('tutor_action_tutor_retrieve_password');"
            + "$policy=wp_json_encode(tutor_flash_get('success'));$danger=wp_json_encode(tutor_flash_get('danger'));"
            + "return array('empty_kept'=>$empty_kept,'late_preserved'=>$preserved,"
            + "'policy_masked'=>strpos($policy,'If an account matches those details')!==false&&strpos($danger,'Synthetic account policy detail')===false);"
        )
        for key in ("empty_kept", "late_preserved", "policy_masked"):
            self.assertTrue(late_validation.get(key), "late validation regression failed: " + key)
        self.assertEqual(len(self.mailpit_messages()), before_late_validation, "late anti-abuse and account-policy denials must send no mail")
        print("LOCAL_LATE_VALIDATION_PASS: empty WP_Error accepted, known/unknown anti-abuse errors preserved, account policy masked")

        # Force one in-process local SMTP failure; no option or transport setting persists.
        messages_before_failure = len(self.mailpit_messages())
        failure = self.wp_json(
            "$_POST['user_login']=" + php_string(self.email) + ";"
            + "$error=new WP_Error('synthetic_preflight');"
            + "$preserved=cas_account_password_recovery_start($error)===$error&&!cas_account_password_recovery_request_active();"
            + "cas_account_password_recovery_start(true);"
            + "$preserved=$preserved&&cas_account_password_recovery_preflight($error)===$error&&!cas_account_password_recovery_request_active();"
            + "$_POST['user_login']=array('invalid');$scalar_rejected=!cas_account_password_recovery_input_valid();"
            + "$_POST['user_login']=" + php_string(self.email) + ";cas_account_password_recovery_start(true);"
            + "$before=is_file('/tmp/audit.log')?filesize('/tmp/audit.log'):0;"
            + "add_action('phpmailer_init',function($m){$m->isSMTP();$m->Host='mailpit';$m->Port=1;$m->SMTPAuth=false;$m->SMTPAutoTLS=false;$m->Timeout=1;},PHP_INT_MAX);"
            + "$sent=wp_mail(" + php_string(self.email) + ",'Local recovery failure test','Synthetic local failure');"
            + "cas_account_password_recovery_finish();$flash=tutor_flash_get('success');"
            + "$log=is_file('/tmp/audit.log')?substr(file_get_contents('/tmp/audit.log'),$before):'';"
            + "return array('sent'=>$sent,'logged'=>strpos($log,'password_reset_mail_failed')!==false,"
            + "'private'=>strpos($log," + php_string(self.email) + ")===false&&strpos($log," + php_string(self.login) + ")===false,"
            + "'generic'=>strpos(wp_json_encode($flash),'If an account matches those details, a reset email will be sent.')!==false,"
            + "'preflight_preserved'=>$preserved,'scalar_rejected'=>$scalar_rejected);"
        )
        self.assertFalse(failure.get("sent"), "local SMTP failure probe must fail delivery")
        for key in ("logged", "private", "generic", "preflight_preserved", "scalar_rejected"):
            self.assertTrue(failure.get(key), "local recovery failure check failed: " + key)
        self.assertEqual(len(self.mailpit_messages()), messages_before_failure, "failed SMTP probe must not deliver mail")
        print("LOCAL_MAIL_FAILURE_PASS: private audit event, generic response, preserved preflight errors and scalar rejection")
        print("LOCAL_MATRIX_PASS: bad nonce, empty input, known/unknown request, captured reset link, invalid token, mismatch, valid reset, reused token, expired token, old/new credential checks")


class TestRecoveryHarnessSafety(unittest.TestCase):
    @staticmethod
    def container_info(name: str, service: str, published: tuple[int, int] | None = None) -> dict:
        ports = {}
        if published:
            internal_port, host_port = published
            ports[f"{internal_port}/tcp"] = [{"HostIp": "127.0.0.1", "HostPort": str(host_port)}]
        return {
            "Name": "/" + name,
            "Config": {
                "Labels": {
                    "com.docker.compose.project": PROJECT,
                    "com.docker.compose.service": service,
                }
            },
            "State": {"Running": True},
            "NetworkSettings": {"Networks": {EXPECTED_NETWORK: {}}, "Ports": ports},
        }

    def test_only_exact_fixture_origins_are_allowed(self) -> None:
        require_fixture_url(EXPECTED_BASE_URL, EXPECTED_BASE_URL, "WordPress")
        require_fixture_url(EXPECTED_MAILPIT_URL, EXPECTED_MAILPIT_URL, "Mailpit")
        for target in (
            "http://127.0.0.1:18090",
            "https://127.0.0.1:18089",
            "http://localhost:18089",
            "http://127.0.0.1:18089/path",
        ):
            with self.subTest(target=target), self.assertRaises(AssertionError):
                require_fixture_url(target, EXPECTED_BASE_URL, "WordPress")
        with self.assertRaises(AssertionError):
            require_fixture_url("http://127.0.0.1:18027", EXPECTED_MAILPIT_URL, "Mailpit")

    def test_wrong_compose_project_container_and_port_fail_closed(self) -> None:
        valid = self.container_info(EXPECTED_WP_CONTAINER, "wp", (80, 18089))
        validate_container(valid, EXPECTED_WP_CONTAINER, "wp", published=(80, 18089))
        for path, value in (
            (("Config", "Labels", "com.docker.compose.project"), "other-project"),
            (("Config", "Labels", "com.docker.compose.service"), "db"),
            (("Name",), "/other-project-wp-1"),
            (("NetworkSettings", "Ports", "80/tcp", 0, "HostPort"), "18090"),
            (("NetworkSettings", "Ports", "80/tcp", 0, "HostIp"), "0.0.0.0"),
        ):
            info = json.loads(json.dumps(valid))
            parent = info
            for key in path[:-1]:
                parent = parent[key]
            parent[path[-1]] = value
            with self.subTest(path=path), self.assertRaises(AssertionError):
                validate_container(info, EXPECTED_WP_CONTAINER, "wp", published=(80, 18089))
        exposed_db = self.container_info(EXPECTED_DB_CONTAINER, "db", (3306, 13306))
        with self.assertRaises(AssertionError):
            validate_container(exposed_db, EXPECTED_DB_CONTAINER, "db", unpublished=(3306,))

    def test_wrong_database_environment_or_volume_is_rejected(self) -> None:
        wp = {"Config": {"Env": ["WORDPRESS_DB_HOST=db", "WORDPRESS_DB_NAME=wp", "WORDPRESS_DB_USER=wp", "WORDPRESS_DB_PASSWORD=local"]}}
        db = {"Config": {"Env": ["MARIADB_DATABASE=wp", "MARIADB_USER=wp", "MARIADB_PASSWORD=local"]}}
        validate_database_pair(wp, db)
        wrong_db = json.loads(json.dumps(wp))
        wrong_db["Config"]["Env"][1] = "WORDPRESS_DB_NAME=production"
        with self.assertRaises(AssertionError):
            validate_database_pair(wrong_db, db)
        wrong_password = json.loads(json.dumps(db))
        wrong_password["Config"]["Env"][2] = "MARIADB_PASSWORD=other"
        with self.assertRaises(AssertionError):
            validate_database_pair(wp, wrong_password)

        mount = {"Mounts": [{"Destination": "/var/lib/mysql", "Type": "volume", "RW": True, "Name": PROJECT + "_dbdata"}]}
        require_mount(mount, "/var/lib/mysql", "volume", read_only=False, name_prefix=PROJECT + "_dbdata")
        mount["Mounts"][0]["Name"] = "other-project_dbdata"
        with self.assertRaises(AssertionError):
            require_mount(mount, "/var/lib/mysql", "volume", read_only=False, name_prefix=PROJECT + "_dbdata")

    def test_wrong_wordpress_home_theme_or_mail_transport_is_rejected(self) -> None:
        account_sha256 = "a" * 64
        runtime = {
            "wp_version": "7.1.3",
            "home": EXPECTED_BASE_URL,
            "siteurl": EXPECTED_BASE_URL,
            "db_host": "db",
            "db_name": "wp",
            "db_user": "wp",
            "db_prefix": "wp_",
            "theme": "hello-elementor-child",
            "tutor": "4.1.0",
            "active_theme_sha256": account_sha256,
            "mounted_theme_sha256": account_sha256,
            "mu_plugins": ["test-mail-sink.php"],
            "mailer": "smtp",
            "mail_host": "mailpit",
            "mail_port": 1025,
            "mail_auth": False,
            "mail_auto_tls": False,
            "mail_secure": "",
            "sendmail_path": "/bin/false",
        }
        validate_wp_runtime(runtime, account_sha256)
        validate_wp_runtime(dict(runtime, wp_version="7.1.2"), account_sha256)
        for key, value in (
            ("wp_version", "7.1.4"),
            ("home", "http://127.0.0.1:18090"),
            ("siteurl", "https://127.0.0.1:18089"),
            ("db_host", "database.example.test"),
            ("db_name", "production"),
            ("db_user", "real-user"),
            ("db_prefix", "live_"),
            ("tutor", "4.0.0"),
            ("active_theme_sha256", "b" * 64),
            ("mail_host", "smtp.example.test"),
            ("mail_port", 25),
            ("mail_auth", True),
            ("sendmail_path", "/usr/sbin/sendmail"),
        ):
            wrong_runtime = dict(runtime, **{key: value})
            with self.subTest(key=key), self.assertRaises(AssertionError):
                validate_wp_runtime(wrong_runtime, account_sha256)

    def test_network_must_be_project_scoped_bridge(self) -> None:
        network = {"Name": EXPECTED_NETWORK, "Labels": {"com.docker.compose.project": PROJECT}, "Driver": "bridge", "Internal": False}
        validate_network(network)
        wrong_driver = dict(network, Driver="host")
        wrong_internal = dict(network, Internal=True)
        for wrong_network in (wrong_driver, wrong_internal):
            with self.assertRaises(AssertionError):
                validate_network(wrong_network)

    def test_cross_port_redirect_and_reset_link_are_rejected(self) -> None:
        handler = ExactOriginRedirectHandler(EXPECTED_BASE_URL)
        request = Request(EXPECTED_BASE_URL + "/dashboard/retrieve-password/")
        for code in (307, 308):
            with self.subTest(code=code), self.assertRaises(AssertionError):
                handler.redirect_request(request, None, code, "Temporary Redirect", {}, "http://127.0.0.1:18090/wp-login.php")
        with self.assertRaises(AssertionError):
            require_same_origin("http://127.0.0.1:18090/wp-login.php?action=rp", EXPECTED_BASE_URL, "password reset link")
        with self.assertRaises(AssertionError):
            require_same_origin("http://127.0.0.1:18027/api/v1/messages", EXPECTED_MAILPIT_URL, "Mailpit request")

    def test_tutor_native_requires_visible_error_and_exact_form_route(self) -> None:
        flow = TestIsolatedPasswordRecoveryFlow("test_password_recovery_email_token_and_credential_matrix")
        final_url = EXPECTED_BASE_URL + "/dashboard/retrieve-password/"
        form = (
            '<form><input name="tutor_action" value="tutor_process_reset_password">'
            '<input name="reset_key" value="invalid-local-reset-key"><input name="user_id" value="9">'
            '<input name="password"><input name="confirm_password"></form>'
        )
        alert = '<div class="tutor-alert" role="alert">This key is invalid or has already been used. Please reset your password again if needed.</div>'
        self.assertEqual(
            flow.assert_reset_failure_response(200, final_url, alert + form, "tutor", "invalid-or-expired-key"),
            "visible-feedback",
        )
        with self.assertRaises(AssertionError):
            flow.assert_reset_failure_response(200, final_url, form, "tutor", "invalid-or-expired-key")
        with self.assertRaises(AssertionError):
            flow.assert_reset_failure_response(200, EXPECTED_BASE_URL + "/login/", form, "tutor", "invalid-or-expired-key")
        with self.assertRaises(AssertionError):
            flow.assert_reset_failure_response(
                200,
                final_url,
                '<div class="tutor-alert">Unexpected server response.</div>',
                "tutor",
                "invalid-or-expired-key",
            )
        self.assertEqual(
            visible_marker("This key is invalid or has already been used. Please reset your password again if needed."),
            "invalid-or-expired-key",
        )

    def test_core_token_error_requires_visible_error_on_exact_tutor_request_form(self) -> None:
        flow = TestIsolatedPasswordRecoveryFlow("test_password_recovery_email_token_and_credential_matrix")
        form = '<form><input name="user_login"><input name="tutor_action" value="tutor_retrieve_password"></form>'
        alert = '<div class="tutor-alert">This password reset link is invalid or has expired. Please request a new link.</div>'
        url = EXPECTED_BASE_URL + '/dashboard/retrieve-password/'
        self.assertEqual(flow.assert_reset_failure_response(200, url, alert + form, 'none', 'invalid-or-expired-key'), 'visible-feedback')
        with self.assertRaises(AssertionError):
            flow.assert_reset_failure_response(200, url, form, 'none', 'invalid-or-expired-key')
        with self.assertRaises(AssertionError):
            flow.assert_reset_failure_response(200, url, alert + form.replace('tutor_retrieve_password', 'other_action'), 'none', 'invalid-or-expired-key')

    def test_mailpit_message_snapshot_paginates_past_fifty(self) -> None:
        flow = TestIsolatedPasswordRecoveryFlow
        first = {"messages": [{"ID": str(n)} for n in range(50)], "total": 51}
        second = {"messages": [{"ID": "50"}], "total": 51}
        with patch.object(flow, 'mailpit_get', side_effect=[json.dumps(first).encode(), json.dumps(second).encode()]) as get:
            messages = flow.mailpit_messages()
        self.assertEqual(len(messages), 51)
        self.assertEqual(get.call_args_list[0].args[0], '/api/v1/messages?start=0&limit=50')
        self.assertEqual(get.call_args_list[1].args[0], '/api/v1/messages?start=50&limit=50')
        with patch.object(flow, 'mailpit_get', return_value=b'{"messages":[],"total":1}'), self.assertRaises(AssertionError):
            flow.mailpit_messages()

    def test_mailpit_uses_get_at_its_fixed_origin(self) -> None:
        class Harness:
            mailpit_url = EXPECTED_MAILPIT_URL

        class Response:
            def __enter__(self):
                return self

            def __exit__(self, *args):
                return None

            def geturl(self):
                return EXPECTED_MAILPIT_URL + "/api/v1/messages"

            def read(self):
                return b'{"messages":[]}'

        class Opener:
            request = None

            def open(self, request, timeout):
                self.request = request
                return Response()

        opener = Opener()
        with patch(__name__ + ".build_opener", return_value=opener):
            response = TestIsolatedPasswordRecoveryFlow.mailpit_get.__func__(Harness, "/api/v1/messages")
        self.assertEqual(response, b'{"messages":[]}')
        self.assertEqual(opener.request.get_method(), "GET")
        require_same_origin(opener.request.full_url, EXPECTED_MAILPIT_URL, "Mailpit request")

    def test_php_payload_stays_out_of_docker_exec_arguments(self) -> None:
        class Harness:
            base_url = EXPECTED_BASE_URL
            wp_container = EXPECTED_WP_CONTAINER

        secret = "synthetic-secret-not-for-argv"
        result = subprocess.CompletedProcess([], 0, '{"ok":true}\n', "")
        with patch.object(subprocess, "run", return_value=result) as run:
            parsed = TestIsolatedPasswordRecoveryFlow.wp_json.__func__(Harness, "$password=" + php_string(secret) + ";return array('ok'=>true);")
        command = run.call_args.args[0]
        self.assertEqual(command, ["docker", "exec", "-i", EXPECTED_WP_CONTAINER, "php"])
        self.assertNotIn(secret, " ".join(command))
        self.assertIn(secret, run.call_args.kwargs["input"])
        self.assertTrue(run.call_args.kwargs["input"].startswith("<?php\n"))
        self.assertEqual(parsed, {"ok": True})


if __name__ == "__main__":
    unittest.main(verbosity=2)
