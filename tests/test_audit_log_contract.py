#!/usr/bin/env python3
"""Contract and offline behaviour for cas_audit_log(): every field is made safe for one line (review 2026-10-20).

Run with pytest (python3 -m pytest tests/test_audit_log_contract.py) or directly (python3 tests/test_audit_log_contract.py).
"""
import re
import shutil
import subprocess
import tempfile
from pathlib import Path

SOURCE = Path(__file__).parents[1] / "wp-content/mu-plugins/cas-audit-log.php"

# A PHP driver that loads the real mu-plugin with WordPress functions stubbed out, then writes hostile fields.
DRIVER = r"""<?php
define( 'ABSPATH', $argv[1] );
function add_action( ...$a ) {}
function add_filter( ...$a ) {}
function get_current_user_id() { return 7; }
require $argv[2];
$_SERVER['REMOTE_ADDR']          = "1.2.3.4";
$_SERVER['HTTP_X_FORWARDED_FOR'] = "5.6.7.8|evil, 9.9.9.9";
cas_audit_log( "ev|ent\nX", "detail \x01 bad \xC3( utf8 | pipe \\ back \u{2028} sep \u{0085} para \u{2029} end" );
cas_audit_log( "plain", "ok" );
echo "driver done\n";
"""


def check_source(path: Path) -> None:
    """Raise AssertionError if the audit mu-plugin lost the line-safety contract."""
    source = path.read_text(encoding="utf-8")

    helper = re.search(r"function cas_audit_field\( \$value \) \{.*?\n\}\n", source, re.S)
    if not helper:
        raise AssertionError("cas_audit_field() is missing")
    body = helper.group(0)
    for needle, why in (
        ("preg_match( '//u'", "invalid UTF-8 must be detected"),
        ("mb_scrub", "invalid UTF-8 must be scrubbed when mbstring is present"),
        ("UTF-8//IGNORE", "invalid UTF-8 must be scrubbed by the iconv fallback"),
        ("\\p{Cc}", "control characters must be stripped"),
        ("\\x{2028}", "U+2028 must be stripped"),
        ("\\x{2029}", "U+2029 must be stripped"),
        ("\\x{0085}", "U+0085 must be stripped"),
        ("'\\\\', '|'", "backslash and pipe must be escaped"),
        ("'\\\\\\\\', '\\\\|'", "the escape values must be backslash-backslash and backslash-pipe"),
        ("null === $text", "a failed regex must not reach the log"),
    ):
        if needle not in body:
            raise AssertionError(f"{why}: '{needle}' not found in cas_audit_field()")

    log = re.search(r"function cas_audit_log\( \$event, \$detail = '' \) \{.*?\n\}\n", source, re.S)
    if not log:
        raise AssertionError("cas_audit_log() is missing")
    code = log.group(0)
    for needle in (
        "cas_audit_field( isset( $_SERVER['REMOTE_ADDR'] )",
        "cas_audit_field( trim( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0] ) )",
        "cas_audit_field( $event )",
        "cas_audit_field( $detail )",
    ):
        if needle not in code:
            raise AssertionError(f"every caller field must pass through cas_audit_field(): missing '{needle}'")
    # Line format stays as it was.
    fmt = "gmdate( 'Y-m-d H:i:s' ) . ' UTC | ' . $ip . ' | uid=' . $uid . ' | ' . cas_audit_field( $event ) . ' | ' . cas_audit_field( $detail ) . \"\\n\""
    if fmt not in code:
        raise AssertionError("the log line format changed")


from contextlib import contextmanager


@contextmanager
def _mutated(source_path, old, new=""):
    """Yield the path of a temp copy of source_path with one replacement; the temp folder is always removed."""
    text = Path(source_path).read_text(encoding="utf-8")
    if old not in text:
        raise AssertionError(f"test setup: '{old}' not found in the source")
    tmpdir = Path(tempfile.mkdtemp())
    try:
        tmp = tmpdir / Path(source_path).name
        tmp.write_text(text.replace(old, new), encoding="utf-8")
        yield tmp
    finally:
        shutil.rmtree(tmpdir, ignore_errors=True)




def test_audit_source_contract():
    check_source(SOURCE)


def test_audit_php_lint():
    php = shutil.which("php")
    if not php:
        print("php not found: lint step skipped")
        return
    result = subprocess.run([php, "-l", str(SOURCE)], capture_output=True, text=True)
    assert result.returncode == 0, f"php -l failed: {result.stdout}{result.stderr}"


def test_negative_pipe_escape_removed_fails():
    with _mutated(SOURCE, r"str_replace( array( '\\', '|' ), array( '\\\\', '\\|' ), $text )", "$text") as mutated:
        try:
            check_source(mutated)
        except AssertionError:
            return
    raise AssertionError("contract accepted an audit helper without the pipe escape")


def test_negative_line_separator_strip_removed_fails():
    with _mutated(SOURCE, r"\x{2028}\x{2029}", "") as mutated:
        try:
            check_source(mutated)
        except AssertionError:
            return
    raise AssertionError("contract accepted an audit helper without the U+2028/U+2029 strip")


def test_offline_behaviour_with_hostile_fields():
    php = shutil.which("php")
    if not php:
        print("php not found: behaviour step skipped")
        return
    work = Path(tempfile.mkdtemp())
    try:
        abspath = work / "domains" / "crystalau.com" / "public_html"
        abspath.mkdir(parents=True)
        (work / "cas-logs").mkdir()          # dirname(ABSPATH, 3) with ABSPATH ending in "/" is the work folder
        driver = work / "driver.php"
        driver.write_text(DRIVER, encoding="utf-8")
        result = subprocess.run([php, str(driver), str(abspath) + "/", str(SOURCE)], capture_output=True, text=True)
        assert result.returncode == 0, f"driver failed: {result.stdout}{result.stderr}"

        raw = (work / "cas-logs" / "audit.log").read_bytes()
        lines = raw.split(b"\n")
        assert lines[-1] == b"", "every line must end with a single newline"
        lines = lines[:-1]
        assert len(lines) == 2, f"one line per call expected, got {len(lines)}"
        text = raw.decode("utf-8")                       # must be valid UTF-8
        for ch in ("\u2028", "\u2029", "\u0085"):
            assert ch not in text, f"U+{ord(ch):04X} must not reach the log"
        assert not any(b < 0x20 and b != 0x0A for b in raw), "control bytes must not reach the log (newline is the line separator)"

        first = lines[0].decode("utf-8")
        fmt = re.compile(r"^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} UTC \| .+ \| uid=7 \| .+ \| .*$")
        assert fmt.match(first), f"line format changed: {first!r}"
        assert " | " not in first.split(" | uid=7 | ", 1)[1].split(" | ", 1)[0].replace("\\|", ""), "event column forged"
        assert "ev\\|ent X" in first, "pipe in the event must be escaped"
        assert "xff=5.6.7.8\\|evil" in first, "pipe in X-Forwarded-For must be escaped"
        assert first.count(" | ") == 4, f"exactly four column separators expected, got {first.count(' | ')}"
        assert "detail" in first and "utf8" in first, "the readable text must survive"
        assert "\\\\" in first, "backslash must be escaped"
        assert "\\|" in first.split(" | ")[-1], "pipe in the detail must be escaped"
    finally:
        shutil.rmtree(work, ignore_errors=True)


def main() -> None:
    check_source(SOURCE)
    print("audit log contract passed: every field is line-safe; the line format is unchanged")


if __name__ == "__main__":
    main()
