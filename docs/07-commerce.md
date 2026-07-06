# 07 — Commerce

The site models two revenue streams — **video courses** (sold on-site, demo checkout) and
**original artwork** (sold off-site via Etsy). All of it is simulated; no real payment is
taken.

---

## Membership tiers

Three tiers, surfaced on `Lessons.dc.html#membership` and referenced across course pages:

| Tier | Price | What you get |
|------|-------|--------------|
| **Wanderer** | Free | Browse the site + read the journal + the full free intro course |
| **Apprentice** | Per-course | The course: videos, downloadable materials, lifetime access |
| **Fellow** | Per-course + | Everything in Apprentice **plus** the teacher's private Discord (critique, Q&A) |

Tiers are per-course purchases, not subscriptions. "Fellow" is priced at **1.8×** the
Apprentice price (`FELLOW_MULT = 1.8` in `site-extras.js`).

---

## The course purchase flow

1. **Add to cart** — any course "Add to cart" button carries `data-add-to-cart data-tiered`.
   `site-extras.js` intercepts it and opens the **tier picker** popover (Apprentice /
   Fellow, with computed prices).
2. **Pick a tier** — pushes a cart line
   `{ title, price, tier, kind: 'Course · <tier>', img, qty: 1 }` into `cas-cart`.
3. **Cart drawer** — the slide-down drawer (any `.cart-btn`) shows the line, lets you
   adjust qty / remove, and links to checkout.
4. **Checkout** (`Checkout.dc.html`) — 3 steps: basket → details & payment (demo) →
   confirmation. Confirmation issues a random `#CAS-####` and **clears `cas-cart`**.

Where the tier-picker fires: catalog cards (`Lessons.dc.html`), the home New Release
mini-carousel and shop rail (`index.html`), and course pages (`course.dc.html`). The free
course has no tier picker — it's a "Start the free course" link straight to the player.

---

## The artwork flow (Etsy bridge)

Original paintings and prints are **not** sold natively. On `Gallery.dc.html`:

- **"Buy on Etsy"** opens an Etsy **search** for the piece
  (`https://www.etsy.com/search?q=Crystal's Art School <title>`) in a new tab. Etsy handles
  checkout, payment, fulfilment.
- **"Add to cart"** does add the artwork to the local `cas-cart` (so the checkout demo can
  include artwork), but the real purchase path is Etsy.

This is a deliberate Phase-1 decision: native artwork commerce (its own product flow,
shipping, fulfilment) is out of scope, so artwork links out to an existing Etsy shop
instead. See the project's `CLAUDE.md` decision log for the rationale.

---

## The cart data model

`cas-cart` is an array of line items. A line can be a course or an artwork:

```js
// course line
{ title: 'Light & shadow on the head', price: 42, tier: 'Apprentice',
  kind: 'Course · Apprentice', img: 'cover1.jpg', qty: 1 }

// artwork line
{ title: 'Harbour at Dusk', price: 320, img: 'cover1.jpg',
  kind: 'Original', qty: 1 }
```

- `qty` +/− and remove happen in the drawer (`site-extras.js`) and on the checkout basket
  step; both read/write the same array.
- Subtotal = Σ `price × qty`. Checkout adds a flat **£18** shipping.
- The cart badge (`.cart-badge`, in the header on every page) shows Σ `qty` and hides at 0.
- Cross-tab sync: a `storage` event listener re-renders the drawer/badge if another tab
  changes the cart.

Money is displayed in **GBP** (`£`) via `money()` in `site-extras.js`.

---

## What's real vs faked

| Thing | Status |
|-------|--------|
| Cart add/remove/qty, badge, subtotal | Real (client-side, `localStorage`) |
| Tier pricing (Fellow = 1.8×) | Real (computed) |
| Checkout steps + order number | UI real, **no payment taken** |
| Card fields | Cosmetic — not validated, not sent |
| Artwork "Buy on Etsy" | Real outbound link (Etsy search) |
| Course video | Simulated (scripted timer, not a real video) |
| Accounts / login | Faked in `cas-user` |

Next: [08 — Claude Design Sync](08-claude-design-sync.md).
