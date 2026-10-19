#!/usr/bin/env python3
"""Loopback-only access checks for synthetic paid-materials fixtures."""

from __future__ import annotations

import argparse
import hashlib
import html
import json
import os
import re
import sys
from dataclasses import dataclass
from http.cookiejar import CookieJar
from pathlib import Path, PurePosixPath, PureWindowsPath
from urllib.error import HTTPError, URLError
from urllib.parse import parse_qs, urlencode, urljoin, urlsplit
from urllib.request import (HTTPRedirectHandler, HTTPCookieProcessor, ProxyHandler,
                            Request, build_opener)

LOOPBACK_HOSTS = {"localhost", "127.0.0.1", "::1"}
PRODUCTION_HOST = "crystalau.com"
PRODUCTION_DB_MARKERS = ("crystalau", "production", "prod", "live")
RUN_ID_RE = re.compile(r"^cas-paid-materials-[a-f0-9]{12}$")
USER_ROLES = ("unenrolled", "enrolled", "editor")
WP_ROLES = {"unenrolled": "subscriber", "enrolled": "subscriber", "editor": "editor"}
EDITOR_AUTH_FIELDS = (
    "owns_course",
    "owns_lesson",
    "owns_course_attachment",
    "owns_lesson_attachment",
    "owns_reference_attachment",
    "can_edit_course",
    "can_edit_lesson",
    "can_edit_course_attachment",
    "can_edit_lesson_attachment",
    "can_edit_reference_attachment",
    "manage_options",
    "tutor_course_access",
    "may_access_private_materials",
)
FILE_ROLES = ("paid_course", "paid_lesson", "paid_reference", "preview_attachment", "public_control")
MAX_BODY_BYTES = 4 * 1024 * 1024


class SafetyError(ValueError):
    pass


@dataclass(frozen=True)
class Origin:
    scheme: str
    host: str
    port: int


def url_origin(raw_url: str) -> Origin:
    try:
        parsed = urlsplit(raw_url)
        port = parsed.port or (80 if parsed.scheme == "http" else 443)
    except ValueError as error:
        raise SafetyError("invalid URL authority") from error

    if parsed.scheme not in {"http", "https"} or not parsed.hostname:
        raise SafetyError("URL must use HTTP(S) and include a host")
    if parsed.username or parsed.password:
        raise SafetyError("URL credentials are refused")

    return Origin(parsed.scheme.lower(), parsed.hostname.casefold(), port)


def is_production_host(host: str) -> bool:
    host = host.casefold().rstrip(".")
    return host == PRODUCTION_HOST or host.endswith("." + PRODUCTION_HOST)


def validate_loopback_base(raw_url: str) -> Origin:
    try:
        parsed = urlsplit(raw_url)
        origin = url_origin(raw_url)
    except SafetyError:
        raise

    if origin.scheme != "http" or origin.host not in LOOPBACK_HOSTS:
        raise SafetyError("base URL must use loopback HTTP")
    if is_production_host(origin.host):
        raise SafetyError("production host is refused")
    if parsed.query or parsed.fragment:
        raise SafetyError("base URL cannot include a query or fragment")
    return origin


def validate_same_origin(raw_url: str, expected: Origin) -> str:
    origin = url_origin(raw_url)
    if origin.scheme != "http" or origin.host not in LOOPBACK_HOSTS or origin != expected:
        raise SafetyError("request or redirect left the exact loopback origin")
    if is_production_host(origin.host):
        raise SafetyError("production host is refused")
    return raw_url


def is_windows_path(value: str) -> bool:
    return bool(re.match(r"^[A-Za-z]:[\\\\/]", value)) or value.startswith("\\\\\\\\")


def path_string_is_absolute(value: str) -> bool:
    path = PureWindowsPath(value) if is_windows_path(value) else PurePosixPath(value)
    return path.is_absolute()


def path_string_is_within(path: str, parent: str) -> bool:
    if is_windows_path(path) != is_windows_path(parent):
        return False
    child_path = PureWindowsPath(path) if is_windows_path(path) else PurePosixPath(path)
    parent_path = PureWindowsPath(parent) if is_windows_path(parent) else PurePosixPath(parent)
    try:
        child_path.relative_to(parent_path)
        return True
    except ValueError:
        return False


def same_path_string(first: str, second: str) -> bool:
    if is_windows_path(first) != is_windows_path(second):
        return False
    if is_windows_path(first):
        return PureWindowsPath(first) == PureWindowsPath(second)
    return PurePosixPath(first) == PurePosixPath(second)


def path_is_within(path: Path, parent: Path) -> bool:
    try:
        path.resolve(strict=True).relative_to(parent.resolve(strict=True))
        return True
    except (OSError, ValueError):
        return False


def validate_manifest_path(manifest_path: Path, repo_root: Path) -> Path:
    try:
        manifest = manifest_path.resolve(strict=True)
        repo = repo_root.resolve(strict=True)
    except OSError as error:
        raise SafetyError("manifest or repository root is unavailable") from error

    if not manifest.is_file():
        raise SafetyError("manifest must be a regular file")
    if path_is_within(manifest, repo):
        raise SafetyError("manifest must stay outside the repository")
    return manifest


def validate_manifest(data: object, expected_origin: Origin) -> dict:
    if not isinstance(data, dict) or data.get("schema") != 1 or data.get("synthetic") is not True:
        raise SafetyError("manifest is not a supported synthetic fixture")

    run_id = data.get("run_id")
    if not isinstance(run_id, str) or not RUN_ID_RE.fullmatch(run_id):
        raise SafetyError("manifest run ID is invalid")
    if data.get("environment") not in {"local", "staging"}:
        raise SafetyError("manifest is not marked local or staging")

    for key in ("home", "siteurl"):
        value = data.get(key)
        if not isinstance(value, str) or url_origin(value) != expected_origin:
            raise SafetyError(f"manifest {key} does not match loopback origin")
        validate_same_origin(value, expected_origin)

    database = data.get("database")
    if not isinstance(database, dict):
        raise SafetyError("manifest lacks database identity")
    for key in ("host", "name"):
        value = database.get(key)
        if not isinstance(value, str) or not value.strip():
            raise SafetyError("manifest database identity is incomplete")
        folded = value.casefold()
        if any(marker in folded for marker in PRODUCTION_DB_MARKERS):
            raise SafetyError("production database indicator is refused")

    users = data.get("users")
    if not isinstance(users, dict) or set(users) != set(USER_ROLES):
        raise SafetyError("manifest must contain only the three synthetic test roles")
    prefix = f"caspaid_{run_id}_"
    for role in USER_ROLES:
        user = users[role]
        if not isinstance(user, dict):
            raise SafetyError("synthetic user entry is invalid")
        login = user.get("login")
        password = user.get("password")
        email = user.get("email")
        if user.get("role") != WP_ROLES[role]:
            raise SafetyError("synthetic user role is invalid")
        if not isinstance(login, str) or not login.startswith(prefix):
            raise SafetyError("non-synthetic username refused")
        if not isinstance(password, str) or len(password) < 24:
            raise SafetyError("synthetic password is missing or too short")
        if not isinstance(email, str) or not email.endswith("@example.test"):
            raise SafetyError("non-synthetic email refused")
        if role == "editor":
            authorization = user.get("authorization")
            if not isinstance(authorization, dict) or set(authorization) != set(EDITOR_AUTH_FIELDS):
                raise SafetyError("editor capability snapshot is missing")
            if any(not isinstance(authorization[field], bool) for field in EDITOR_AUTH_FIELDS):
                raise SafetyError("editor capability snapshot is invalid")
            may_access = any(
                authorization[field]
                for field in (
                    "can_edit_course",
                    "can_edit_lesson",
                    "can_edit_course_attachment",
                    "can_edit_lesson_attachment",
                    "can_edit_reference_attachment",
                    "manage_options",
                    "tutor_course_access",
                )
            )
            if authorization["may_access_private_materials"] != may_access:
                raise SafetyError("editor file-access expectation does not match recorded capabilities")

    web_root = data.get("web_root")
    repo_root = data.get("repo_root")
    manifest_remote_path = data.get("manifest_path")
    if not all(isinstance(value, str) and path_string_is_absolute(value) for value in (web_root, repo_root, manifest_remote_path)):
        raise SafetyError("manifest runtime paths are invalid")
    if path_string_is_within(manifest_remote_path, web_root) or path_string_is_within(manifest_remote_path, repo_root):
        raise SafetyError("manifest must stay outside WordPress and source roots")

    pages = data.get("pages")
    files = data.get("files")
    markers = data.get("markers")
    if not isinstance(pages, dict) or not isinstance(files, dict) or not isinstance(markers, dict):
        raise SafetyError("manifest fixture routes are incomplete")
    for page_key in ("course", "paid_lesson", "preview_lesson"):
        route = pages.get(page_key)
        if not isinstance(route, str):
            raise SafetyError("manifest page route is missing")
        validate_same_origin(route, expected_origin)
        parsed_route = urlsplit(route)
        if parsed_route.query or parsed_route.fragment or run_id not in parsed_route.path:
            raise SafetyError("page route is not a synthetic fixture permalink")
    for file_key in FILE_ROLES:
        item = files.get(file_key)
        if not isinstance(item, dict):
            raise SafetyError("manifest file fixture is missing")
        url = item.get("url")
        digest = item.get("sha256")
        name = item.get("name")
        if not isinstance(url, str) or not isinstance(name, str):
            raise SafetyError("manifest file fixture is invalid")
        validate_same_origin(url, expected_origin)
        parsed_file = urlsplit(url)
        if parsed_file.query or parsed_file.fragment or "/wp-content/uploads/" not in parsed_file.path:
            raise SafetyError("file URL is outside expected WordPress uploads path")
        if not name.startswith(f"{run_id}-") or not parsed_file.path.rsplit("/", 1)[-1].startswith(f"{run_id}-"):
            raise SafetyError("non-synthetic attachment name refused")
        if not isinstance(digest, str) or not re.fullmatch(r"[a-f0-9]{64}", digest):
            raise SafetyError("fixture hash is invalid")

    for key in ("course", "paid_lesson", "preview_lesson"):
        marker = markers.get(key)
        if not isinstance(marker, str) or not marker.startswith(f"CAS_PAID_MATERIALS_{key.upper()}_{run_id}"):
            raise SafetyError("manifest page marker is invalid")

    return data


def body_matches_fixture(body: bytes, expected_sha256: str) -> bool:
    return hashlib.sha256(body).hexdigest() == expected_sha256


def is_login_redirect(url: str) -> bool:
    parsed = urlsplit(url)
    # Only the root login routes verified in the local runtime count as login gates.
    if parsed.path not in {"/wp-login.php", "/login", "/login/"}:
        return False
    return parse_qs(parsed.query).get("action", ["login"]) == ["login"]


class SameOriginRedirectHandler(HTTPRedirectHandler):
    def __init__(self, expected_origin: Origin):
        super().__init__()
        self.expected_origin = expected_origin
        self.redirects: list[tuple[int, str, str]] = []

    def redirect_request(self, request, response, code, message, headers, new_url):
        validate_same_origin(new_url, self.expected_origin)
        redirected = super().redirect_request(request, response, code, message, headers, new_url)
        if redirected is not None:
            self.redirects.append((code, request.full_url, new_url))
        return redirected


class LoopbackClient:
    def __init__(self, origin: Origin):
        self.cookie_jar = CookieJar()
        self.redirect_handler = SameOriginRedirectHandler(origin)
        self.opener = build_opener(
            ProxyHandler({}),
            HTTPCookieProcessor(self.cookie_jar),
            self.redirect_handler,
        )
        self.origin = origin

    def request(self, url: str, data: bytes | None = None):
        validate_same_origin(url, self.origin)
        self.redirect_handler.redirects.clear()
        request = Request(
            url,
            data=data,
            headers={"User-Agent": "CAS synthetic paid-materials test"},
            method="POST" if data is not None else "GET",
        )
        try:
            with self.opener.open(request, timeout=8) as response:
                body = response.read(MAX_BODY_BYTES + 1)
                if len(body) > MAX_BODY_BYTES:
                    raise SafetyError("response exceeded local test size limit")
                return response.status, response.geturl(), body
        except HTTPError as error:
            body = error.read(MAX_BODY_BYTES + 1)
            if len(body) > MAX_BODY_BYTES:
                raise SafetyError("error response exceeded local test size limit") from error
            return error.code, error.geturl(), body
        except URLError as error:
            raise SafetyError("loopback request failed") from error

    def login(self, base_url: str, login: str, password: str) -> None:
        login_url = urljoin(base_url.rstrip("/") + "/", "wp-login.php")
        self.request(login_url)
        form = urlencode(
            {
                "log": login,
                "pwd": password,
                "wp-submit": "Log In",
                "redirect_to": base_url.rstrip("/") + "/",
                "testcookie": "1",
            }
        ).encode("ascii")
        self.request(login_url, form)
        if not any(cookie.name.startswith("wordpress_logged_in_") for cookie in self.cookie_jar):
            raise SafetyError("synthetic account login did not establish a WordPress session")


def add_result(results: list[tuple[bool | None, str]], outcome: bool | None, label: str) -> None:
    results.append((outcome, label))
    state = "OBSERVE" if outcome is None else "PASS" if outcome else "FAIL"
    print(f"{state} {label}")


def is_explicit_denial(client: LoopbackClient, status: int, final_url: str) -> bool:
    try:
        validate_same_origin(final_url, client.origin)
    except SafetyError:
        return False
    if status in {401, 403, 404}:
        return True
    if not 200 <= status < 300 or not is_login_redirect(final_url):
        return False
    redirects = client.redirect_handler.redirects
    return bool(redirects) and (
        redirects[-1][0] in {301, 302, 303, 307, 308} and redirects[-1][2] == final_url
    )


def check_page(client: LoopbackClient, url: str, marker: str, allowed: bool) -> tuple[bool, int, str, bytes]:
    status, final_url, body = client.request(url)
    contains = marker.encode("utf-8") in body
    if allowed:
        passed = 200 <= status < 300 and contains
    else:
        passed = not contains and is_explicit_denial(client, status, final_url)
    return passed, status, final_url, body


def check_file(client: LoopbackClient, item: dict, allowed: bool) -> tuple[bool, int, bool]:
    status, final_url, body = client.request(item["url"])
    matches = body_matches_fixture(body, item["sha256"])
    if allowed:
        passed = 200 <= status < 300 and matches
    else:
        passed = not matches and is_explicit_denial(client, status, final_url)
    return passed, status, matches


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--manifest", required=True, type=Path)
    parser.add_argument("--repo-root", required=True, type=Path)
    args = parser.parse_args()

    if os.environ.get("CAS_PAID_TEST_ISOLATED") != "1" or os.environ.get("CAS_PAID_TEST_LOCAL_OPT_IN") != "1":
        raise SafetyError("explicit isolated-local opt-in is required")

    base_url = os.environ.get("CAS_PAID_TEST_BASE_URL", "")
    if not base_url:
        raise SafetyError("CAS_PAID_TEST_BASE_URL is required")
    origin = validate_loopback_base(base_url)
    web_root_value = os.environ.get("CAS_PAID_TEST_WEB_ROOT", "")
    if not web_root_value:
        raise SafetyError("CAS_PAID_TEST_WEB_ROOT is required")
    manifest_path = validate_manifest_path(args.manifest, args.repo_root)
    with manifest_path.open("r", encoding="utf-8") as source:
        data = validate_manifest(json.load(source), origin)
    if not same_path_string(data["web_root"], web_root_value):
        raise SafetyError("manifest WordPress root does not match runtime")

    clients = {"guest": LoopbackClient(origin)}
    for role in USER_ROLES:
        user = data["users"][role]
        client = LoopbackClient(origin)
        client.login(base_url, user["login"], user["password"])
        clients[role] = client

    results: list[tuple[bool | None, str]] = []
    course_marker = data["markers"]["course"]
    paid_marker = data["markers"]["paid_lesson"]
    preview_marker = data["markers"]["preview_lesson"]
    editor_auth = data["users"]["editor"]["authorization"]
    add_result(
        results,
        None,
        "editor capabilities: "
        f"owns_course={editor_auth['owns_course']} "
        f"owns_lesson={editor_auth['owns_lesson']} "
        f"owns_course_attachment={editor_auth['owns_course_attachment']} "
        f"owns_lesson_attachment={editor_auth['owns_lesson_attachment']} "
        f"owns_reference_attachment={editor_auth['owns_reference_attachment']} "
        f"edit_course={editor_auth['can_edit_course']} "
        f"edit_lesson={editor_auth['can_edit_lesson']} "
        f"edit_course_attachment={editor_auth['can_edit_course_attachment']} "
        f"edit_lesson_attachment={editor_auth['can_edit_lesson_attachment']} "
        f"edit_reference_attachment={editor_auth['can_edit_reference_attachment']} "
        f"manage_options={editor_auth['manage_options']} "
        f"tutor_course_access={editor_auth['tutor_course_access']} "
        f"may_access_private_materials={editor_auth['may_access_private_materials']}",
    )

    for role, client in clients.items():
        passed, status, _, body = check_page(client, data["pages"]["course"], course_marker, True)
        add_result(results, passed, f"{role}: public course page (HTTP {status})")

        lesson_page_allowed = role == "enrolled" or (role == "editor" and editor_auth["tutor_course_access"])
        paid_page_ok, paid_status, _, _ = check_page(client, data["pages"]["paid_lesson"], paid_marker, lesson_page_allowed)
        add_result(results, paid_page_ok, f"{role}: paid lesson content gate (HTTP {paid_status})")

        preview_ok, preview_status, _, _ = check_page(client, data["pages"]["preview_lesson"], preview_marker, True)
        add_result(results, preview_ok, f"{role}: explicit preview lesson (HTTP {preview_status})")

        public_item = data["files"]["public_control"]
        public_ok, public_status, public_matches = check_file(client, public_item, True)
        add_result(results, public_ok, f"{role}: public media control (HTTP {public_status}, exact={public_matches})")

        for file_key in ("paid_course", "paid_lesson", "paid_reference"):
            item = data["files"][file_key]
            editor_can_edit = editor_auth["can_edit_course"] or editor_auth["can_edit_course_attachment"]
            if file_key == "paid_lesson":
                editor_can_edit = editor_can_edit or editor_auth["can_edit_lesson"] or editor_auth["can_edit_lesson_attachment"]
            elif file_key == "paid_reference":
                editor_can_edit = editor_can_edit or editor_auth["can_edit_lesson"] or editor_auth["can_edit_reference_attachment"]
            editor_allowed = editor_can_edit or editor_auth["manage_options"] or editor_auth["tutor_course_access"]
            file_allowed = role == "enrolled" or (role == "editor" and editor_allowed)
            file_ok, file_status, file_matches = check_file(client, item, file_allowed)
            add_result(results, file_ok, f"{role}: {file_key} access (HTTP {file_status}, exact={file_matches})")

    guest = clients["guest"]
    _, course_status, _, course_body = check_page(guest, data["pages"]["course"], course_marker, True)
    paid_course = data["files"]["paid_course"]
    rendered = html.unescape(course_body.decode("utf-8", "replace"))
    paid_path = urlsplit(paid_course["url"]).path
    link_leak = paid_course["url"] in rendered or paid_path in rendered
    add_result(results, not link_leak, f"guest: paid course attachment absent from public markup (HTTP {course_status})")

    preview_item = data["files"]["preview_attachment"]
    for role, client in (("guest", guest), ("enrolled", clients["enrolled"])):
        status, _, body = client.request(preview_item["url"])
        exact = body_matches_fixture(body, preview_item["sha256"])
        add_result(results, None, f"{role}: preview attachment policy unknown (HTTP {status}, exact={exact})")

    failures = sum(outcome is False for outcome, _ in results)
    print(f"\nchecks={len(results)} fail={failures} runtime=loopback-only")
    return 1 if failures else 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except SafetyError as error:
        print(f"SAFETY STOP: {error}", file=sys.stderr)
        raise SystemExit(2)
    except (OSError, json.JSONDecodeError, KeyError, TypeError) as error:
        print(f"HARNESS ERROR: {type(error).__name__}", file=sys.stderr)
        raise SystemExit(2)
