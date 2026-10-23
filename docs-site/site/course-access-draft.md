# Course access

> **Draft — awaiting final-screen and behaviour verification. Not approved for live use.**

## Quick checklist

Check the existing student and exact published course. Agree whether access should be added or removed. Read the warning, confirm once, check the result, and stop if it is uncertain.

**Rule: this is a one-off access correction, not a money tool. It does not charge, refund, create an order or change an order's status.**

## Review and confirm

1. Open **Content hub → Course access**.
2. Enter the existing student's login or email. This does not create an account; staff accounts are not valid targets.
3. Choose the correct published course and **Add access** or **Remove access**.
4. Click **Review change**.
5. Check the student's name/email, course and current access. Keep this private; do not circulate a confirmation screenshot unnecessarily.
6. Read the warning. Use **Confirm give access** or **Confirm remove access** once only if that is intended.
7. Check the result. If the student already has access or has no active access, do not force another action.

Adding access gives the course without creating or correcting a payment record. Removing access removes access to the selected course, including access represented by existing enrolment rows; it does not issue a refund or cancel a paid order.

Removing access keeps the cancelled enrolment record and the existing student account. It does not delete the person’s account or erase their student designation.

Keep these actions separate:

- **Remove access:** changes the student's access to the selected course.
- **Cancel a pending order in Sales:** changes that pending order; it is not a refund and does not remove independent access from another completed purchase.
- **Refund:** concerns a payment and is not performed by this screen. Ask Herman about the particular payment instead of substituting an access change.

An isolated backend check verified that these manual access changes for an existing student left order/payment/refund records unchanged. That check is not approval of the final screens, payment-provider behaviour or automatic email content; this guide remains a draft.

This content-access form does not implement a refund or dispute policy. The recorded operator procedure below is separate: neither a payment refund nor a bank dispute should be treated as an automatic access change.

## Operator-only refunds and bank disputes

> **Recorded intended operator workflow — not a verified live runbook.** This section records the operating rule chosen on 20 October 2026. The current native Tutor admin screen, remove-enrolment control, provider behaviour and end-to-end result remain unverified and must be checked before live use.

The business policy remains **no refunds**. These operator instructions do not offer refunds to students. **Financial refund and payment-dispute handling belongs to Herman**, not to the limited content-editing account. Do not share or borrow an administrator login.

Your approved **Add access / Remove access** controls remain available for agreed manual access corrections. They do not perform a financial refund or change the bank/provider dispute, and this operator procedure does not remove those existing controls.

### A refund handled by Herman

1. Identify the exact payment/order, existing student and affected course before taking any action.
2. The recorded route is the order's **refund flow in Tutor's admin**, with **remove enrolment selected**. The actual native control and current behaviour must be verified before this draft is used. If the control is absent or unclear, stop rather than substituting another action.
3. **Do not issue the refund from Stripe's dashboard.** That is not the recorded operator route, and a payment-provider action must not be assumed to remove Tutor access.
4. Check the payment result and course-access result separately. Do not treat an order label, success notice or missing email as proof of both.
5. If someone already refunded through Stripe or the result is uncertain, do not issue a second refund. Record what happened and let Herman reconcile the payment and access state.

### A bank dispute

1. Record the dispute reference and identify the student, course and order. Do not issue a new refund merely because a bank dispute exists.
2. The recorded intended rule is to **cancel the affected student's course enrolment in Tutor**, coordinated by Herman. Do not assume the dispute automatically did this, and do not describe it as a change to the dispute's bank/provider status.
3. Verify the affected course-access state separately; do not delete the student's account or claim a receipt/email was sent.

If the same course has another independent valid purchase or grant of access, or the affected enrolment is ambiguous, stop for Herman to resolve that entitlement before acting. This draft does not decide how to cancel one entitlement without affecting another. An uncertain payment/access result always means stop and reconcile, not repeat an action or infer that nothing changed.

## If something goes wrong

If access changes after you open the review page, start from a fresh review. If the screen reports an audit failure, save failure or unverified restoration, stop and contact Herman through [Troubleshooting](./money-help-draft.md).

Do not change an order to repair an access error. A success message does not promise an email was delivered; do not repeat the access change to try to send one.
