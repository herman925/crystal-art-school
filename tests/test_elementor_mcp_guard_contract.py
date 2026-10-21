#!/usr/bin/env python3
"""Contract for the MCP route guard: /elementor/v1/mcp-proxy and the MCP adapter default server are removed, nothing else is (review 2026-10-20).

Run with pytest (python3 -m pytest tests/test_elementor_mcp_guard_contract.py) or directly (python3 tests/test_elementor_mcp_guard_contract.py).
"""
import re
import shutil
import subprocess
import tempfile
from pathlib import Path

SOURCE = Path(__file__).parents[1] / "wp-content/mu-plugins/cas-elementor-mcp-guard.php"
ROUTES = ("/elementor/v1/mcp-proxy", "/mcp/mcp-adapter-default-server")


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def check_guard(path: Path) -> None:
    """Raise AssertionError on any contract violation in the guard file at path."""
    source = path.read_text(encoding="utf-8")

    require(source, "add_filter( 'rest_endpoints'", "the rest_endpoints filter is missing")

    const = re.search(r"const CAS_ELEMENTOR_MCP_GUARD_ROUTES = array\((.*?)\);", source, re.S)
    if not const:
        raise AssertionError("CAS_ELEMENTOR_MCP_GUARD_ROUTES is missing")
    listed = tuple(re.findall(r"'(/[^']+)'", const.group(1)))
    if listed != ROUTES:
        raise AssertionError(f"guarded routes must be exactly {ROUTES}, found {listed}")

    require(source, "if ( ! is_array( $endpoints ) )", "the existence guard for the route table is missing")

    matcher = re.search(r"function cas_elementor_mcp_guard_matches\( \$route \) \{.*?\n\}\n", source, re.S)
    if not matcher:
        raise AssertionError("cas_elementor_mcp_guard_matches() is missing")
    body = matcher.group(0)
    require(body, "$route === $prefix", "the exact route must match")
    require(body, "$prefix . '/'", "sub-paths must match on the prefix")
    require(body, "$prefix . '('", "regex variants must match on the prefix")

    # Only the two guarded families may be named as quoted route keys. Comments may cite source paths.
    named_elementor = set(re.findall(r"'(/elementor/v1/[a-z0-9-]+)'", source))
    if named_elementor != {"/elementor/v1/mcp-proxy"}:
        raise AssertionError(f"no other elementor/v1 route may be named, found {sorted(named_elementor)}")
    named_mcp = set(re.findall(r"'(/mcp/[a-z0-9-]+)'", source))
    if named_mcp != {"/mcp/mcp-adapter-default-server"}:
        raise AssertionError(f"no other mcp route may be named, found {sorted(named_mcp)}")

    # unset() may only run inside the match branch, never on the whole table.
    unset_lines = [line for line in source.splitlines() if "unset(" in line]
    if len(unset_lines) != 1:
        raise AssertionError("exactly one unset() call is allowed in the guard")
    require(source, "unset( $endpoints[ $route ] );", "matched routes must be unset")
    call = source.index("is_string( $route ) && cas_elementor_mcp_guard_matches( $route )")
    if call > source.index("unset( $endpoints[ $route ] );"):
        raise AssertionError("unset must sit behind the route match")


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




def test_guard_contract_passes_on_repo_file():
    check_guard(SOURCE)


def test_guard_php_lint():
    php = shutil.which("php")
    if not php:
        print("php not found: lint step skipped")
        return
    result = subprocess.run([php, "-l", str(SOURCE)], capture_output=True, text=True)
    assert result.returncode == 0, f"php -l failed: {result.stdout}{result.stderr}"


def test_negative_missing_proxy_prefix_fails():
    # Dropping one guarded route must make the contract fail.
    with _mutated(SOURCE, "\t'/elementor/v1/mcp-proxy',\n", "") as mutated:
        try:
            check_guard(mutated)
        except AssertionError:
            return
    raise AssertionError("contract accepted a guard without the proxy route")


def test_negative_missing_adapter_prefix_fails():
    with _mutated(SOURCE, "\t'/mcp/mcp-adapter-default-server',\n", "") as mutated:
        try:
            check_guard(mutated)
        except AssertionError:
            return
    raise AssertionError("contract accepted a guard without the adapter default server route")


def test_negative_existence_guard_removed_fails():
    with _mutated(SOURCE, "if ( ! is_array( $endpoints ) )", "if ( false )") as mutated:
        try:
            check_guard(mutated)
        except AssertionError:
            return
    raise AssertionError("contract accepted a guard without the route-table existence check")


def main() -> None:
    check_guard(SOURCE)
    print("elementor MCP guard contract passed: two routes removed by prefix, existence guard present, no other routes named")


if __name__ == "__main__":
    main()
