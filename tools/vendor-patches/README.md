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

## Preserve existing billing fields on partial updates

`tutor-4.1.3-billing-partial-update.patch` fixes the native country/state change request, which submits only those fields. The original controller filled every omitted required field with an empty string and overwrote the rest of the saved billing profile. The patch reads the profile once, preserves omitted fields during updates, and keeps existing required-field defaults for new inserts. Explicitly submitted empty values still clear their fields.

Target: `wp-content/plugins/tutor/ecommerce/BillingController.php`, Tutor **4.1.3** only.

- Original SHA-256: `adbb60c2a41c6eb84a3c46df662cc6b5927921728e557b1c709bca39d278284a`
- Patched SHA-256: `66423779151dbe5c62cf6a0b55e5856cc33c2c4e40c4c2d40566b8989fe12abd`

Apply the same exact-byte, private-backup, staged-lint and conditional-rollback requirements above. Upgrade review must account for both vendor patches.

```sh
php tests/test_tutor_billing_partial_update.php \
  /path/to/ecommerce/BillingController.php \
  /path/to/classes/BaseController.php \
  /path/to/models/BillingModel.php
```

The test executes the actual controller with the real BaseController field handling and BillingModel field lists, using synthetic in-memory persistence. Original source reproduces five destructive update cases; patched source passes all ten checks, including new profiles, explicit clears and existing failure gates. Validation failure is injected; these tests do not claim that native email-validation rules are fixed.

Connected checkout/profile retesting is tracked in [Payments: preserve billing details on country changes](https://github.com/herman925/crystal-art-school/issues/256).

## Keep native order IDs separate from WordPress and legacy order IDs

`tutor-4.1.3-native-order-ids.patch` qualifies and fixes the site's **native Tutor commerce** mode:

- WordPress content deletion no longer interprets that post's ID as a native order ID. The native OrderModel deletion path remains responsible for native orders.
- Native enrolment creation/deletion keeps the enrolment's own order/product metadata but no longer writes/deletes compatibility fields on an unrelated WordPress post with the same numeric ID.
- Native compatibility readers use the actual order, order items and linked enrolments, checking the enrolment type, student and purchased item. Their three-field return shape remains compatible.
- Native earnings reads/existence/deletion use `process_by=tutor`, preserving legacy earnings that share an order ID.

The exact four source preimages/postimages are in `tutor-4.1.3-native-order-ids.json`. The patch changes Course, EnrollmentModel, Utils and Earnings only. Existing WooCommerce/EDD branches are preserved; tests include legacy order APIs, HPOS reading and unmarked legacy earnings controls. Arbitrary backend migrations and complete WooCommerce/EDD application runtimes are not certified by these tests.

```sh
php tests/test_tutor_order_ids.php /path/to/tutor
```

The test extracts the actual changed methods plus native order lookup/deletion from read-only vendor inputs and supplies synthetic persistence/platform boundaries. Original source reproduces 16 failures out of 26 checks; fixed source passes all26. Test only in an isolated environment with no external network, no real credentials/data, read-only inputs and bounded resources.

Stage/lint every file before replacing any, preserve originals privately, check exact current preimages, and verify all postimages. Preserve the two earlier refund/billing patches. This source change does not migrate or delete historical metadata, orders, enrolments or earnings. Upgrade review must account for all three version-pinned patches. See [Payments: qualify native order ID safety before launch](https://github.com/herman925/crystal-art-school/issues/258).
