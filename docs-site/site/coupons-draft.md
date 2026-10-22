# Coupons

> **Draft — awaiting final-screen and behaviour verification. Not approved for live use.**

## Quick checklist

Check code, discount type, amount, dates and both usage limits before saving or sharing. Confirm once and check the fresh list. An uncertain save is a reason to stop, not to submit again.

**Rule: coupons change future checkout offers; they do not correct or refund past orders.**

The current screen supports code coupons for all courses. Do not assume a code is limited to one course, one recipient or a bundle.

## Create a coupon

1. Open **Content hub → Coupons** and choose the new-coupon form.
2. Give it a recognisable title and distinct code. Codes use 3–40 letters, numbers, dashes or underscores.
3. Choose percentage or fixed-amount discount. A percentage cannot exceed 100.
4. Enter a number above zero with at most two decimals. Percentage `20` means 20%; fixed amount `20` is an amount, not 20%.
5. Set the start date and optional expiry on or after it, using `YYYY-MM-DD`.
6. Check total-use and per-user limits. Blank means no limit, not zero uses. A limit must be a positive whole number.
7. Read the whole form again before saving.
8. For a 100% percentage coupon, tick the free-course warning only if that is intended.
9. Save once. Check the fresh list before distributing the code.

A 100% code makes the course free for anyone who successfully uses it within its restrictions. A large fixed discount can also remove the amount due on a lower-priced course. The absence of a checkbox does not prove a discount is small.

Do not promise a deadline at a particular student's local midnight. Ask Herman to verify an exact timezone or checkout cut-off before advertising it.

## What a checked local example shows

In a local check of the site's native coupon request, an unused 10% code reduced a test basket from `29.00` to `26.10`. With one previous use and a per-user limit of one, the request was refused with a usage-limit message.

Those are test amounts, not agreed course prices. This verifies the local coupon-controller request only—not an external payment, an existing payment-provider session, tax treatment, Tutor Pro integrations or a browser checkout journey. The final screens and wider payment flow still need review.

## Edit, deactivate or delete

- Check the exact code before editing. The change does not refund earlier purchases.
- A used coupon's code cannot be renamed; a used coupon cannot be deleted. Deactivate it instead if it must stop being available.
- Deactivation is not a refund and does not remove access already granted.
- Activation is not proof that dates, usage limits or other checkout eligibility permit every attempted use.
- Do not keep using an old edit tab after someone else changes the coupon.

## An uncertain result

If creation cannot be verified, do **not** submit again. Refresh the list without editing and ask Herman whether the coupon already exists. A lost response or uncertain save must not become two offers through repeated clicking.

For a used-code/delete refusal, check the intended coupon and use deactivation where appropriate; do not create a workaround code without agreeing the new offer. For an audit/database warning or unexpected stored amount, stop and follow [Troubleshooting](./money-help-draft.md).
