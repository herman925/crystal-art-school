# Tutor 4.1.0 password-recovery route regression

Tutor marks `retrieve-password` as public (`classes/Utils.php`, `tutor_dashboard_permalinks()`), sends the Forgot password link there (`classes/FormHandler.php::lostpassword_url()`), and may rewrite reset email links to that same route with `reset_key` and `user_id` (`classes/User.php::maybe_update_password_reset_link()`). That route renders either the request form or reset form (`templates/template-part/retrieve-password.php`). The theme's guest-dashboard redirect must preserve it.

Pinned Tutor 4.1.0 source:

- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/classes/Utils.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/classes/Template.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/classes/FormHandler.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/classes/User.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/templates/template-part/retrieve-password.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/templates/template-part/form-retrieve-password.php

## Tests

Run static contracts with `python3 tests/test_login_form_contract.py`. Run the local HTTP smoke test only against an explicitly supplied loopback WordPress URL:

```sh
CAS_RECOVERY_TEST_BASE_URL=http://localhost:8089 python3 tests/test_password_recovery_http.py
```

The HTTP test accepts only `http://localhost`, `http://127.0.0.1` or `http://[::1]`, rejects external redirects, sends GET requests only, and uses no cookies. It checks both slash variants, Tutor's request form, token-shaped GET selecting Tutor's reset form, and the ordinary guest dashboard redirect. Token-shaped GET does not test token validity.

## Not verified here

Docker was unavailable. Recovery request and reset form GETs, valid/invalid/expired reset-token POSTs, local mail capture, password replacement, and old/new credential outcomes remain unrun. Do not use a live mailer or change a live password for this test.
