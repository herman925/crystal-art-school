# Tutor native refund enrolment fix

`tutor-4.1.3-refund-enrolment.patch` fixes two reproduced cases in Tutor 4.1.3:

- Refunding an older order while keeping access must not rebind the existing enrolment to that fully refunded order. Otherwise refunding the remaining order with access removal misses the enrolment.
- Refunding a repurchase must select the enrolment linked to that order, even when an older cancelled enrolment exists for the same learner/course.

The patch prefers an exact order/course/student enrolment and prevents fully refunded orders from creating or rebinding enrolment. It preserves the native matching-order keep/remove and partial-refund behavior. It does not change payment APIs, prices, roles or historical orders, and does not repair existing enrolment records automatically.

## Source and deployment checks

This patch applies only to `wp-content/plugins/tutor/ecommerce/HooksHandler.php` from Tutor **4.1.3**:

- Original SHA-256: `44645502784465963f5300301176b8120cb489df500cf08dc83538ac4ea1ce2c`
- Patched SHA-256: `70b1ea8e4b2643308b8095f6221e11cac6ebd0198a1ae1e13556fbd6bce7c26e`

Check the installed version and exact original bytes before deployment. Preserve the original privately, lint the staged PHP, replace only that file and verify the patched hash. A rollback must verify the installed patched hash before restoring the original. Do not load WordPress merely to lint or apply this source change. Do not restore a database or rerun any payment/refund to apply it.

From a disposable WordPress source copy, the standard patch checks are:

```sh
patch -p1 --dry-run < /path/to/tutor-4.1.3-refund-enrolment.patch
patch -p1 < /path/to/tutor-4.1.3-refund-enrolment.patch
```

An upgrade may replace this vendor file. Review the new Tutor implementation and rerun the regression tests before accepting an upgrade; never force this version-specific patch onto different source bytes. Remove the local patch when an upstream release supplies and verifies the fix.

## Regression checks

The test extracts the actual `manage_earnings_and_enrollments` method from the supplied source and executes it with synthetic orders, enrolments and WordPress dependencies. It covers both reproduced regressions, full-refund creation, paid/free and partial-refund cases, matching-order keep/remove, repeat handling and foreign candidate rejection.

```sh
php tests/test_tutor_refund_enrolment.php /path/to/HooksHandler.php
```

Run in an isolated, network-disabled environment with read-only inputs and bounded resources. The original source reproduces four failures; the patched source passes all fifteen checks. These tests establish the method behavior, not full provider or browser acceptance. The connected sandbox regression is tracked in [Payments: prevent refunded orders from rebinding course access](https://github.com/herman925/crystal-art-school/issues/254) and the [sandbox journey](https://github.com/herman925/crystal-art-school/issues/253).
