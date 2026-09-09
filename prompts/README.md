# Prompt log

The brief asks for the prompts used, with screenshots. The screenshots are in this folder; the
notes below record what was asked at each step and, more usefully, where the first answer was not
the one that shipped.

## 1. Reading the brief

Asked the assistant to read `Laravel_Developer_Mini_Task.pdf`, including the embedded wireframe
image, and summarise the functional requirements and submission checklist.

## 2. Deciding the shape before writing code

Asked what the brief actually pins down versus leaves open. Two things came out of this that
changed the plan:

- The brief never names a database. Migrations and seeders *are* the deliverable, so the reviewer
  runs `migrate --seed` against their own server. MySQL was chosen for the row-level locking the
  concurrency requirement needs.
- No deployment is asked for. The submission is a repo, a README, prompt screenshots and a narrated
  recording. Time went into the concurrency work and the tests instead of hosting.

## 3. Schema

Asked for a normalised schema for products, customers, orders and order lines. Two changes made on
top of the first draft:

- `order_items` snapshots `unit_price` and `tax_percentage` at the time of sale, so repricing a
  product does not rewrite historical bills.
- Added `stock_movements` as the supporting table the brief invites, giving an append-only audit
  trail behind `products.stock_on_hand`.

The first draft also stored a `reference` column on `orders`. Dropped it — generating a second
unique value safely under concurrency is real work for something that is just a formatting of the
primary key, so it became an accessor.

## 4. Order creation and the concurrency requirement

Asked for a service that validates stock, prices lines including tax, deducts stock and returns the
order, safe under concurrent requests.

The first version used `lockForUpdate()` alone. Two things were added:

- Products are locked **ordered by id**, so two overlapping orders holding the same products cannot
  deadlock against each other.
- The decrement is conditional (`where('stock_on_hand', '>=', $qty)`) with the affected row count
  checked, as a second line of defence for drivers without row locking.

Also moved the stock check out of the `FormRequest`. Availability can change between validation and
the write, so the only check worth trusting is the one holding the lock.

## 5. Money

Asked how to avoid float drift on totals and tax. Settled on integer paise inside
`App\Support\Money`, with tax applied and rounded **per line** rather than on the subtotal, so the
printed lines add up to the printed total.

## 6. Tests

Asked for coverage beyond the happy path. The insufficient-stock test was tightened to assert that
a rejected order leaves *nothing* behind — no order row, no stock movement, no queued job, and the
lines that could have been filled still on the shelf.

For the concurrency requirement, a single-process test cannot prove anything, so the test launches
six real `php artisan orders:place` processes at one product with two units in stock. It was then
verified in the other direction: with the guards removed from `OrderService` the test fails, which
is the only evidence that it is testing something.

## 7. UI

Asked for the counter screen from the wireframe: customer fields, product rows with live line
totals, the low-stock panel, and the cash-tendered and balance-to-return block. Kept to Blade plus
plain JavaScript posting to the same `/api/orders` endpoint — nothing here needed a framework.

## 8. Editing and deleting a bill

Asked for edit and delete on a bill. The first version hard-deleted the order row, which throws away
the record of a transaction that really happened and leaves the stock ledger pointing at nothing.
It became a soft delete instead: stock returned, record kept, still readable under a filter.

An intermediate version called this "voiding". That is the accounting word for it, but nobody at a
counter says it, so the whole thing went back to plain "delete" with the soft delete doing the work
underneath.

Every table then got a `deleted_at`. That flushed out a real problem: `order_items` has a unique
index on `(order_id, product_id)`, and a soft-deleted row still occupies it, so replacing lines
during an edit would fail the second time the same product appeared. Replacing lines uses
`forceDelete()` for that reason.

The edit was reworked too. The first version deleted the old lines and took stock for the new ones,
which double-counts anything that appears in both. It now computes **one delta per product** across
the union of the old and new line sets and applies it inside the same locked transaction as a sale.

## 9. Rebuilding the counter screen

The first counter screen used a type-ahead that added one line at a time, which reads well in a
demo and badly at a till. It became a card grid: the whole catalogue visible, search filtering it
live, one tap per unit, and the quantity shown on the card itself.

The customer field changed the same way — a dropdown over everyone on file, with adding a new one
folded into the same control rather than a separate page. Payment moved from the side column to the
bottom of the same column, so the bill reads top to bottom in the order it is actually built:
customer, items, payment.

## 10. Extra screens

Asked for the screens a counter actually needs beyond the wireframe: a dashboard, a customer list,
and a per-product stock ledger. The ledger was the point of `stock_movements` all along — this is
where the table stops being schema and starts being a feature.

## 11. Comments

Asked for the code to be stripped of comments and the reasoning moved into the README, next to the
decision it explains. Docblocks were kept only where they carry types.

## 12. Bugs the assistant introduced, and how they were caught

Worth recording, since the brief asks how well the tooling was used rather than whether it was:

- `InventoryService::restock()` first called `$model->increment()` and then computed
  `balance_after` from the same model — Eloquent had already updated the attribute in memory, so
  every restock recorded double the balance. Caught by a test asserting the exact ledger row.
- The counter form lost its `novalidate` attribute during a rewrite, so the browser's own bubble
  fired before the styled validation could. Caught while capturing the validation screenshot.
- The dashboard chart rendered a percentage height inside a container with no height, so the bars
  were invisible. Caught in a browser screenshot, not by any test.

- A blanket find-and-replace while renaming "void" to "delete" rewrote PHP `: void` return types
  into `: delete`. Caught immediately by the test suite.
- Blade's `@json` directive cannot parse a multi-line array literal written inline in an attribute.
  It broke the page silently at compile time, twice, in two different files. The fix both times was
  to build the array in PHP first and pass the variable.

The general lesson: the tests catch logic, the browser catches everything else. Both were needed.
