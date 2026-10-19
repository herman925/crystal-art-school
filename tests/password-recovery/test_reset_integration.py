#!/usr/bin/env python3
"""End-to-end reset test for the isolated PR146 WordPress/Mailpit stack only.

Required environment:
  CAS_RECOVERY_TEST_BASE_URL=http://127.0.0.1:18089
  CAS_RECOVERY_MAILPIT_URL=http://127.0.0.1:18026
  CAS_RECOVERY_WP_CONTAINER=cas-pr146-w1-20261019-wp-1

Creates one synthetic subscriber in that disposable local database. Sends reset mail only
through the local Mailpit sink. Never prints passwords, reset keys, mail bodies or tokens.
"""

from __future__ import annotations

import html
import json
import os
import re
import secrets
import subprocess
import unittest
from html.parser import HTMLParser
from urllib.error import HTTPError
from urllib.parse import parse_qs, urlencode, urljoin, urlsplit, urlunsplit
from urllib.request import HTTPRedirectHandler, HTTPCookieProcessor, ProxyHandler, Request, build_opener
from http.cookiejar import CookieJar


LOOPBACK = {"127.0.0.1", "localhost", "::1"}


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


class LoopbackRedirectHandler(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        target = urlsplit(newurl)
        if target.scheme != "http" or target.hostname not in LOOPBACK:
            raise AssertionError("refusing redirect outside loopback")
        return super().redirect_request(req, fp, code, msg, headers, newurl)


class LocalHttp:
    def __init__(self, base_url: str) -> None:
        self.base_url = base_url.rstrip("/")
        self.opener = build_opener(ProxyHandler({}), HTTPCookieProcessor(CookieJar()), LoopbackRedirectHandler())

    def request(self, url: str, fields: dict[str, str] | None = None) -> tuple[int, str, str]:
        parts = urlsplit(url)
        if parts.scheme != "http" or parts.hostname not in LOOPBACK:
            raise AssertionError("refusing non-loopback HTTP request")
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
            return response.status, response.geturl(), response.read().decode("utf-8", "replace")

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
        if not cls.base_url or not cls.mailpit_url or not cls.wp_container:
            raise unittest.SkipTest("set loopback URL, Mailpit URL and isolated PR146 WP container")
        for value in (cls.base_url, cls.mailpit_url):
            parsed = urlsplit(value)
            if parsed.scheme != "http" or parsed.hostname not in LOOPBACK:
                raise AssertionError("recovery integration only accepts loopback HTTP")
        if not re.fullmatch(r"cas-pr146-w1-[a-z0-9-]+-wp-1", cls.wp_container):
            raise AssertionError("refusing a WordPress container outside the PR146 test project")
        cls.client = LocalHttp(cls.base_url)
        cls.login = "casreset" + secrets.token_hex(6)
        cls.email = cls.login + "@example.test"
        cls.old_password = secrets.token_urlsafe(24)
        cls.new_password = secrets.token_urlsafe(24)
        cls.user_id = cls.create_test_user()

    @classmethod
    def wp_json(cls, body: str) -> dict:
        host = urlsplit(cls.base_url).netloc
        script = (
            f"$_SERVER['HTTP_HOST']={php_string(host)};"
            "$_SERVER['SERVER_NAME']='127.0.0.1';"
            "$_SERVER['REQUEST_URI']='/';"
            "require '/var/www/html/wp-load.php';"
            f"$result=(function(){{{body}}})();echo json_encode($result);"
        )
        proc = subprocess.run(
            ["docker", "exec", cls.wp_container, "php", "-r", script],
            check=False,
            capture_output=True,
            text=True,
            timeout=30,
        )
        if proc.returncode:
            raise AssertionError("local WP fixture helper failed: " + proc.stderr[-600:])
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
    def mailpit_messages(cls) -> list[dict]:
        with build_opener(ProxyHandler({})).open(cls.mailpit_url + "/api/v1/messages", timeout=10) as response:
            return json.loads(response.read().decode("utf-8" )).get("messages", [])

    @classmethod
    def message_detail(cls, message_id: str) -> dict:
        with build_opener(ProxyHandler({})).open(cls.mailpit_url + "/api/v1/message/" + message_id, timeout=10) as response:
            return json.loads(response.read().decode("utf-8"))

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
                if parts.hostname not in LOOPBACK:
                    continue
                if (parts.path.endswith("wp-login.php") and query.get("action") == ["rp"] and query.get("key") and query.get("login")) or (parts.path.rstrip("/").endswith("/dashboard/retrieve-password") and query.get("reset_key") and query.get("user_id")):
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


if __name__ == "__main__":
    unittest.main(verbosity=2)
