#!/usr/bin/env python3
"""Deterministic static contract for the #53 Tutor dashboard integration."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
FIXTURE = ROOT / "tests" / "fixtures" / "dashboard_contract.json"


def read(relative: str) -> str:
    path = ROOT / relative
    if not path.is_file():
        raise AssertionError(f"missing contract file: {relative}")
    return path.read_text(encoding="utf-8")


def require(haystack: str, needle: str, context: str) -> None:
    if needle not in haystack:
        raise AssertionError(f"{context} is missing: {needle}")


def main() -> int:
    contract = json.loads(FIXTURE.read_text(encoding="utf-8"))
    source = read(contract["source"])
    script = read(contract["script"])
    templates = {relative: read(relative) for relative in contract["templates"]}

    panel_keys = [panel["key"] for panel in contract["panels"]]
    if len(set(panel_keys)) != len(panel_keys):
        raise AssertionError("the studio contract must define distinct panels")
    for panel in contract["panels"]:
        require(source, f'data-panel="{panel["key"]}"', "dashboard panel")
        require(source, panel["label"], "dashboard tab label")

    for token in contract["source_tokens"]:
        require(source, token, "dashboard source")
    for template_key in contract["native_templates"]:
        require(source, template_key, "native Tutor template")
    for token in contract["preserved_tokens"]:
        require(source, token, "pre-existing account behavior")
    for token in contract["script_tokens"]:
        require(script, token, "dashboard script")
    for relative, template in templates.items():
        require(template, "cas_account_render_dashboard_shell_before", relative)
        require(template, "cas_account_render_dashboard_shell_after", relative)

    combined = "\n".join([source, script, *templates.values()]).lower()
    for token in contract["forbidden_tokens"]:
        if token.lower() in combined:
            raise AssertionError(f"forbidden demo/out-of-scope token present: {token}")

    print(
        "dashboard contract passed: "
        f"{len(panel_keys)} panels, {len(contract['templates'])} Tutor shell overrides"
    )
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (AssertionError, OSError, json.JSONDecodeError) as exc:
        print(f"dashboard contract failed: {exc}", file=sys.stderr)
        raise SystemExit(1)
