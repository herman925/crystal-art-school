#!/usr/bin/env python3
"""Contract for the ajax guard: the admin-only action list keeps its entries and the Tutor Pro 4.1.3 additions (review 2026-10-20).

Run with pytest (python3 -m pytest tests/test_ajax_guard_contract.py) or directly (python3 tests/test_ajax_guard_contract.py).
"""
import re
import shutil
import subprocess
import tempfile
from pathlib import Path

SOURCE = Path(__file__).parents[1] / "wp-content/mu-plugins/cas-ajax-guard.php"

# Actions that must stay denied to non-administrators.
REQUIRED_ADMIN_ONLY = {
    "delete_tutor_license",
    "tutor_admin_student_list_bulk_action",
    "tutor_remove_device_manually",
    "tutor_pro_gift_enrollment",
    "tutor_remove_assignment_attempt",
    "tutor_membership_plan_duplicate",
    "import_gradebook_sample_data",
    "tutor_pn_save_subscription",
    "tutor_save_notification_preference",
    # Added for Tutor Pro 4.1.3 (owner review, 2026-10-20).
    "tutor_report_get_student_enrollments",
    "process_tutor_template",
    "tutor_gc_credential_upgrade",
    "tutor_template_import_list",
    "install_tutor_plugin",
}
REQUIRED_ACTION_GUARD = {"tutor_apply_instructor", "tutor_register_instructor"}


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def listed(source: str, name: str) -> tuple:
    body = re.search(rf"const {name} = array\((.*?)\);", source, re.S)
    if not body:
        raise AssertionError(f"{name} is missing")
    return tuple(re.findall(r"'([a-z_]+)'", body.group(1)))


def check_guard(path: Path) -> None:
    """Raise AssertionError on any contract violation in the guard file at path."""
    source = path.read_text(encoding="utf-8")

    admin_only = listed(source, "CAS_AJAX_GUARD_ADMIN_ONLY")
    missing = REQUIRED_ADMIN_ONLY - set(admin_only)
    if missing:
        raise AssertionError(f"admin-only list lost or never gained: {sorted(missing)}")
    if len(admin_only) != len(set(admin_only)):
        raise AssertionError("admin-only list contains a duplicate entry")

    action_guard = set(listed(source, "CAS_ACTION_GUARD_ADMIN_ONLY"))
    if not REQUIRED_ACTION_GUARD <= action_guard:
        raise AssertionError("the form-post guard list lost an entry")

    require(source, "current_user_can( 'manage_options' )", "administrators must not be denied")
    require(source, "wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );", "ajax denial must return 403")
    require(source, "add_action( 'admin_init', function () {", "the ajax guard must run on admin_init")


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


def test_negative_dropped_new_action_fails():
    # Removing one of the Tutor Pro 4.1.3 names must make the contract fail.
    with _mutated(SOURCE, "'tutor_gc_credential_upgrade',", "") as mutated:
        try:
            check_guard(mutated)
        except AssertionError:
            return
    raise AssertionError("contract accepted a guard without tutor_gc_credential_upgrade")


def test_negative_dropped_template_import_fails():
    with _mutated(SOURCE, "'tutor_template_import_list',", "") as mutated:
        try:
            check_guard(mutated)
        except AssertionError:
            return
    raise AssertionError("contract accepted a guard without tutor_template_import_list")


def test_negative_dropped_existing_action_fails():
    with _mutated(SOURCE, "'delete_tutor_license',", "") as mutated:
        try:
            check_guard(mutated)
        except AssertionError:
            return
    raise AssertionError("contract accepted a guard without delete_tutor_license")


def test_negative_administrator_check_removed_fails():
    with _mutated(SOURCE, "current_user_can( 'manage_options' )", "current_user_can( 'read' )") as mutated:
        try:
            check_guard(mutated)
        except AssertionError:
            return
    raise AssertionError("contract accepted a guard that no longer exempts administrators")


def main() -> None:
    check_guard(SOURCE)
    print(f"ajax guard contract passed: {len(REQUIRED_ADMIN_ONLY)} admin-only actions and 2 form-post actions present; administrators are not denied")


if __name__ == "__main__":
    main()
