# Course prices

> **Draft — awaiting final-screen and behaviour verification. Not approved for live use.**

## Quick checklist

Check the course title, current amounts, agreed new Regular/Sale, and whether it is a draft or already published. Use one confirmation tab. Confirm once, check the fresh result, and stop if it is uncertain.

**Rule: change prices only through Course prices, never through the ordinary course-builder save.**

## Understand Regular and Sale

- **Regular** is the normal price and must be greater than zero.
- **Sale** is optional. Enter a positive amount below Regular, or leave it completely empty.
- Use numbers such as `60` or `60.50`, with at most two decimals. Do not type `£`, commas, words or percentages.
- For no sale, do not type `0`, `none`, a dash or `empty`.
- An old stored zero sale may display as no sale. That does not make a newly entered zero sale valid.
- The agreed currency is GBP (£). If the screen appears to use another currency, stop and ask Herman; do not change global settings.

Example amounts here are not approved real-course prices. A price is not a promise of the amount received in a bank account; this page does not set fees, settlement currency, payout timing or tax treatment.

| Intended offer | Regular | Sale | What to do |
|---|---|---|---|
| Normal price £60, no sale | `60` | Leave empty | Valid paid-course proposal |
| Normal £60, sale £45 | `60` | `45` | Valid paid-course proposal |
| A free course | `0` | Leave empty | Stop; this screen is for paid courses |
| No sale entered as zero | `60` | `0` | Clear Sale instead |
| Sale equals/exceeds Regular | `60` | `60` or `65` | Correct the offer before requesting review |
| Negative amount | `-60` | `-15` | Not a valid proposal |

A displayed `—` means no amount; it is not something to type. When an older stored zero sale is shown as no sale, leave **Current sale** empty on confirmation. This does not permit entering a new zero Sale.

## New paid course: prepare a draft first

**Create → prepare content → save as draft → confirm first price → reload the same course → publish only when ready.**

### Prepare the draft

1. Open **Content hub → Add course** once and wait for the builder.
2. Give it a distinct title. Do not leave several identically named drafts and guess between them.
3. Prepare the description, images, topics, lessons and lesson order. Save individual lesson changes using their own save controls.
4. Save the course content as a draft. Moving to another page is not proof that unfinished work was saved.
5. Keep that saved course tab open. Open **Course prices** separately.

If creation stalls or reports an error, stop: a draft may already exist. Repeated Add course clicks can create duplicates.

### Set the first price

1. Find the exact draft title in **Course prices** and use its **Set first price** row.
2. If unexpected current amounts appear, stop and check rather than assuming it is unpriced.
3. Enter agreed Regular and optional Sale amounts; click **Set first price**.
4. Check the title and **Now / New** table.
5. Type **Current regular**, **Current sale**, **New regular** and **New sale** from that table. Leave genuinely empty/no-sale fields empty; do not invent zero values.
6. Click **Save the new prices** once. Continue only after a clear saved result and fresh check of both amounts.

Example: for a genuinely unpriced draft becoming £60 with no sale, leave both Current fields empty, enter New regular `60`, and leave New sale empty.

### Publish separately

1. Return to the same saved course tab. Preserve any unsaved text before refreshing.
2. Reload the builder so it reads the confirmed price, not stale earlier values.
3. Check title, lessons, images, displayed price and agreed publication timing.
4. Publish only when ready. Check the final status and public course-information page afterwards.
5. Do not switch the course to free or make the paid lessons publicly accessible to bypass a refusal.

A saved price is not a published course. A public information page is not permission to expose paid lessons. If the first price is saved but publication remains blocked, keep the draft and ask Herman; do not set the price again.

## Existing paid course: review before saving

1. Open **Content hub → Course prices** and identify the right course.
2. Read its current amounts. Enter the complete proposed Regular and Sale; clear Sale if the sale should end.
3. Click **Review change**. This prepares a proposal, not a saved change.
4. Check the title and both **Now / New** rows.
5. Type the four confirmation fields. Current means the old amount, not the proposed amount.
6. Click **Save the new prices** once. Check the saved message and both amounts in a fresh list.
7. Reload any already-open builder before its next content save so it does not submit stale prices.

Example: Now £60/£45, New £68/£52 → Current regular `60`, Current sale `45`, New regular `68`, New sale `52`.

To end that later sale, propose Regular `68` and empty Sale. Confirm Current regular `68`, Current sale `52`, New regular `68`, empty New sale.

## Check all four confirmation fields

The **Now** column is the current amount for this proposal. **New** is the amount you intend to save.

| Confirmation field | Type this |
|---|---|
| **Current regular** | The Regular amount under Now |
| **Current sale** | The Sale under Now; leave empty when there is no current sale |
| **New regular** | The Regular amount under New |
| **New sale** | The Sale under New; leave empty when there will be no sale |

For example, `60` and `60.00` represent the same amount. The important checks are the value and which box it belongs in. Do not copy a proposed new amount into a Current field simply to get past confirmation.

Look for **“The price is saved.”**, then check both amounts in the fresh Course prices list. If a different warning appears, read it before doing anything else; a first-price save can succeed while publication is still blocked.

If a save result is lost or an old confirmation says it was already used, first check the current list without editing. It may follow an earlier successful save. Never use Back to replay that form, share it with another person or create a second proposal until the current saved state is clear.

## Timing and limits

- Confirmations expire after 10 minutes and belong to the signed-in account. That is not the duration of a sale.
- Existing free or unknown-type courses cannot be converted through this screen. Ask Herman if the intended course is missing.
- A published course's successful price save is a real change now, not a future announcement.
- This tool does not schedule a sale end. Agree when to remove it before advertising.
- Existing orders are not rewritten. Do not promise instant repricing of an already-open cart, checkout or payment session; ask Herman about the specific case.
- Do not use the ordinary builder save or an administrator account as a workaround.

A reviewed local test found that a newly requested, server-rendered basket and checkout page reflected the updated price for the same signed-in basket. Historical order, item and enrolment records stayed unchanged in that test. This does **not** verify automatic refresh of an already-open browser page, cached/CDN pages, tax or coupon rules, an existing payment-provider session, or live payments. Do not promise that every open checkout changes instantly.

## If something goes wrong

Read [Troubleshooting](./money-help-draft.md) before another attempt. A failed confirmation may need a fresh proposal; audit failure or unverified restoration requires stopping and contacting Herman. Closing a tab does not remove a published price, and typing the old amounts back is another money change, not a safe repair.
