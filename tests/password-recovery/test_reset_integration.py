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
        cls.login = "casreset" + secrets.token_hex(6)
        cls.email = cls.login + "@example.test"
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
        return json.loads(cls.mailpit_get("/api/v1/messages").decode("utf-8")).get("messages", [])

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
                return status, final_url, body, form, "wp" if "pass1" in names else "tutor"
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
        empty_status, empty_final, empty_body = self.request_reset("")
        print(f"empty username request: status={empty_status}, path={urlsplit(empty_final).path}, visible={visible_marker(empty_body)}, message={visible_summary(empty_body)}")
        self.assertEqual(visible_marker(empty_body), "username-required")
        self.assertEqual(len(self.mailpit_messages()), len(initial_messages), "invalid nonce/empty input must send no mail")

        known_status, known_final, known_body = self.request_reset(self.email)
        known_marker = self.visible_outcome(known_status, known_final, known_body, "known-account reset request", (self.email, self.login, self.old_password, self.new_password))
        self.assertLess(known_status, 500)
        reset_url, _ = self.email_link_for_request(initial_ids)

        unknown = "unknown-" + secrets.token_hex(6) + "@example.test"
        unknown_status, unknown_final, unknown_body = self.request_reset(unknown)
        unknown_marker = self.visible_outcome(unknown_status, unknown_final, unknown_body, "unknown-account reset request", (unknown, self.email, self.login, self.old_password, self.new_password))
        self.assertLess(unknown_status, 500)
        self.assertNotIn(unknown.lower(), json.dumps(self.mailpit_messages()).lower(), "unknown account must not receive mail")
        print(f"known/unknown visible-outcome difference: {known_marker != unknown_marker} (recorded for hardening follow-up)")

        # Confirm a bad key and expired key cannot change the synthetic user's password.
        parts = urlsplit(reset_url)
        query = parse_qs(parts.query)
        if "key" in query:
            query["key"] = ["invalid-local-reset-key"]
        else:
            query["reset_key"] = ["invalid-local-reset-key"]
        invalid_url = urlunsplit((parts.scheme, parts.netloc, parts.path, urlencode(query, doseq=True), ""))
        invalid_status, invalid_final, invalid_body, invalid_form, invalid_kind = self.reset_form(invalid_url)
        if invalid_kind != "none":
            invalid_status, invalid_final, invalid_body = self.submit_reset(invalid_final, invalid_form, invalid_kind, self.new_password, self.new_password)
        invalid_marker = self.visible_outcome(invalid_status, invalid_final, invalid_body, "invalid reset token", (self.email, self.login, self.old_password, self.new_password))
        self.assertEqual(invalid_marker, "invalid-or-expired-key")
        self.assertTrue(self.auth_ok(self.old_password), "invalid token must not change credentials")

        # Password mismatch must leave the valid email token usable and original password unchanged.
        status, page_url, body, form, kind = self.reset_form(reset_url)
        self.assertNotEqual(kind, "none", "email link must open a password reset form")
        self.assertLess(status, 500)
        mismatch_status, mismatch_final, mismatch_body = self.submit_reset(page_url, form, kind, self.new_password, self.new_password + "-mismatch")
        mismatch_marker = self.visible_outcome(mismatch_status, mismatch_final, mismatch_body, "mismatched passwords", (self.email, self.login, self.old_password, self.new_password))
        self.assertEqual(mismatch_marker, "password-mismatch")
        self.assertTrue(self.auth_ok(self.old_password), "mismatched passwords must not change credentials")

        # A correct confirmation resets the synthetic user's password.
        status, page_url, body, form, kind = self.reset_form(reset_url)
        valid_status, valid_final, valid_body = self.submit_reset(page_url, form, kind, self.new_password, self.new_password)
        valid_marker = self.visible_outcome(valid_status, valid_final, valid_body, "valid reset", (self.email, self.login, self.old_password, self.new_password))
        self.assertEqual(valid_marker, "reset-success")
        self.assertTrue(self.auth_ok(self.new_password), "new password must authenticate")
        self.assertFalse(self.auth_ok(self.old_password), "old password must no longer authenticate")

        # Reuse of the consumed token cannot change the password again.
        reused_status, reused_final, reused_body, reused_form, reused_kind = self.reset_form(reset_url)
        if reused_kind != "none":
            reused_status, reused_final, reused_body = self.submit_reset(reused_final, reused_form, reused_kind, self.old_password, self.old_password)
        reused_marker = self.visible_outcome(reused_status, reused_final, reused_body, "reused reset token", (self.email, self.login, self.old_password, self.new_password))
        self.assertEqual(reused_marker, "invalid-or-expired-key")
        self.assertTrue(self.auth_ok(self.new_password), "reused token must not replace the new password")

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
        expired_status, expired_final, expired_body, expired_form, expired_kind = self.reset_form(expired_url)
        if expired_kind != "none":
            expired_status, expired_final, expired_body = self.submit_reset(expired_final, expired_form, expired_kind, self.old_password, self.old_password)
        expired_marker = self.visible_outcome(expired_status, expired_final, expired_body, "expired reset token", (self.email, self.login, self.old_password, self.new_password))
        self.assertEqual(expired_marker, "invalid-or-expired-key")
        self.assertTrue(self.auth_ok(self.new_password), "expired token must not change credentials")

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
