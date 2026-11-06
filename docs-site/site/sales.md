# Find an order

**Open Content Hub → Sales.**

1. Search or filter by order number, course, status or date.
2. Check the student login and course together.
3. Read **Order status** and **Payment status** separately.

The filters are **Order number**, **Status**, **From**, **To** and **Course ID**. Choose
**Filter** to apply them. Use the course's number supplied for the review if you do not know
its Course ID. The student login appears in the results so you can distinguish students
with similar display names. No matches can mean the filters are too narrow; clear them
before assuming an order is missing.

A completed order is not proof that money reached the bank or that an email was delivered.
Keep the website order number when asking for help; it can differ from a payment provider's reference.

<figure class="guide-screenshot" aria-labelledby="sales-filters-current-visual">
  <figcaption id="sales-filters-current-visual">Find the right order before changing anything</figcaption>
  <div class="guide-screenshot-images">
    <a href="/guide-screenshots/sales-filters.png" target="_blank" rel="noopener" style="--screenshot-width:1238px" aria-label="Open full-size screenshot: Sales: actual order, status, date and Course ID filters and export control (new tab)"><img src="/guide-screenshots/sales-filters.png" width="1238" height="41" alt="Sales: actual order, status, date and Course ID filters and export control" loading="lazy"></a>
  </div>
  <p>Apply these filters, then inspect the student, course and separate order/payment states. The screenshot is the real filter bar, not an editable form in this guide. Select a screenshot to open it full size.</p>
</figure>

## Change a pending order

<figure class="guide-screenshot" aria-labelledby="sales-review-visual">
  <figcaption id="sales-review-visual">Sales: the actual order search and filters</figcaption>
  <div class="guide-screenshot-images">
    <a href="/guide-screenshots/sales-controls.png" target="_blank" rel="noopener" style="--screenshot-width:810px" aria-label="Open full-size screenshot: Sales: actual search filters; customer and order rows excluded (new tab)"><img src="/guide-screenshots/sales-controls.png" width="810" height="81" alt="Sales: actual search filters; customer and order rows excluded" loading="lazy"></a>
  </div>
  <p>Find the exact order first. Student and order rows are excluded from this screenshot; eligible pending orders offer their own review and confirmation controls. Select a screenshot to open it full size.</p>
</figure>

Only pending orders offer the supported completed/cancelled changes.

1. Choose the intended change on the exact order.
2. Check the student, course, current status and access consequence on the review screen.
3. Confirm once and check the fresh list.

**Mark completed gives course access.** It does not collect money or mark a payment paid.
If no payment is recorded, a separate checkbox warns that you are giving access for free.
Use it only when that is the agreed intention; otherwise ask the site administrator.

**Mark cancelled is not a refund.** It does not reverse a payment or remove independent
access from another completed purchase. Completed orders cannot be cancelled by this tool.

## Export a list

Apply the intended filters, then choose **Download CSV of this list**. The export includes
order/date, student login, course, order/payment statuses and amount. It is capped at 200
matching orders, so ask the site administrator for a larger reconciliation.

Keep student information private. Leave protective spreadsheet prefixes in place.
Monthly/course totals are order reports, not bank-deposit, profit or tax statements.

If a confirmation no longer matches, review the fresh order. If a change or restoration
cannot be verified, [stop and ask the site administrator](/money-help); do not use another action as a repair.


## Course popularity

Below the order totals, **Course popularity — live paid purchases** shows purchase counts
**By course** and **By Medium**. It uses the selected From/To dates, Course ID and Order
number; empty dates mean the last 12 months. The Status filter does not change which
payments qualify for popularity.

Only completed, paid individual-course purchases with verified live Stripe or PayPal
evidence count. Sandbox/test payments, administrator-marked test accounts, free purchases, recorded refunds and payments
whose mode cannot be verified are excluded. During UAT, seeing no verified live purchases
is expected. The page tells you how many paid orders were excluded as test or unverified.

Each course counts once per order. If a course has several media, its purchase counts once
in each of its current media, so Medium totals can overlap. Renaming or reassigning a
Medium changes how those courses are grouped. These are purchase counts, not views,
revenue or bank deposits. Older payments without the necessary evidence remain excluded.
