# Store Order & Inventory Mini-System

A small Laravel application for a retail counter: pick products against a catalogue, bill a
customer, and keep stock in sync. Built as a take-home assignment for Mallow Technologies.

There are two ways in. The JSON API is the part the brief specifies; the counter screen at `/` is
a thin front end over that same API, laid out from the wireframe in the brief.

- **Laravel** 12 · **PHP** 8.4 · **MySQL** 8+ / MariaDB (PostgreSQL works unchanged)
- **Blade + Alpine.js + Tailwind 4** for the counter, orders and inventory screens
- **Queue** on the database driver · **Mail** written to the log
- **36 tests**, including one that launches six real processes at the same product to prove it
  cannot be oversold

---

## Setup

```bash
git clone <repo-url> store-billing
cd store-billing

composer install
cp .env.example .env
php artisan key:generate
```

Point the `DB_*` block in `.env` at your server and create the schema:

```bash
mysql -u root -p -e "CREATE DATABASE store_billing"
php artisan migrate --seed
```

Build the front end and start the app. The queue worker is a separate process, which is the
point of putting the confirmation email on a queue in the first place:

```bash
npm install && npm run build     # Node 20+ (Vite 7 / Tailwind 4)
php artisan serve                # http://localhost:8000
php artisan queue:work           # in a second terminal
```

The seed leaves you a catalogue of fifteen products (a few deliberately short on stock so the
low-stock alert has something to show), ten customers, and twelve past orders. Two customers have
predictable emails for testing: `thomas@example.com` and `divya@example.com`.

Confirmation emails are not sent over SMTP. `MAIL_MAILER=log` writes each rendered message to
`storage/logs/laravel.log`, so you can watch the queue worker pick a job up and see the bill it
produced.

---

## The screens

| Route | What it does |
| --- | --- |
| `/` | **New Order.** Look a customer up by email, search the catalogue, build the bill, take cash. |
| `/orders` | **Orders.** Every bill raised, newest first, filterable by customer email. |
| `/orders/{order}` | **Bill.** A printable tax invoice with the change breakdown. |
| `/products` | **Inventory.** Stock levels with a search and a low-stock filter. |

The counter screen does the things a cashier actually needs and nothing else:

- **Type-ahead product search** over name or code, driven from the keyboard — arrows to move,
  Enter to add. Products already on the bill drop out of the results, and stock is shown on every
  option so the cashier can see a short shelf before picking it.
- **Customer lookup as you type.** A recognised email fills the name in and shows how many orders
  that customer has; an unrecognised one flips the name field to required.
- **Live totals** computed the same way the server computes them — integer paise, tax rounded per
  line — so the figure on screen is the figure that gets saved.
- **Change breakdown** into the notes and coins to hand back, updating as the cash amount is typed.
- **Validation on both sides.** The client catches the obvious things before a request is made;
  everything the server rejects is mapped back onto the field that caused it, with a toast
  summarising what happened. Stock shortages highlight the offending row and say how many are left.

`resources/js/money.js` deliberately mirrors `App\Support\Money` and `App\Support\CashDrawer`.
The screen never decides what an order costs; it just avoids making the cashier wait for a round
trip to find out.

---

## The API

### Create an order

`POST /api/orders`

```bash
curl -X POST http://localhost:8000/api/orders \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{
        "customer": { "email": "thomas@example.com", "name": "Thomas Verghese" },
        "items": [
          { "product_id": 11, "quantity": 3 },
          { "product_id": 15, "quantity": 1 }
        ],
        "amount_tendered": 1000
      }'
```

Validates the request shape, takes a row lock on every product in the order, checks stock, prices
each line with its own tax rate, writes the order, deducts stock, and queues the confirmation
email. `201` with the created order:

```json
{
  "data": {
    "id": 13,
    "reference": "ORD-20260909-00013",
    "customer": { "id": 1, "name": "Thomas Verghese", "email": "thomas@example.com" },
    "items": [
      {
        "product_id": 11, "code": "PER-3002", "name": "Dove Soap 100g",
        "quantity": 3, "unit_price": "62.00", "tax_percentage": "18.00",
        "line_subtotal": "186.00", "line_tax": "33.48", "line_total": "219.48"
      }
    ],
    "totals": { "subtotal": "506.00", "tax": "49.48", "grand_total": "555.48" },
    "payment": {
      "amount_tendered": "1000.00",
      "change_due": "444.52",
      "change_breakdown": [
        { "denomination": 200, "count": 2 },
        { "denomination": 20, "count": 2 },
        { "denomination": 2, "count": 2 }
      ]
    }
  }
}
```

`amount_tendered` is optional. When present it must cover the bill, and the response carries the
change owed plus the notes and coins to hand back.

A short shelf returns `422` naming every product that could not be filled, and nothing is written:

```json
{
  "message": "One or more products do not have enough stock to fulfil this order.",
  "shortages": [
    { "product_id": 3, "code": "GRO-1003", "name": "Farm Eggs (12)", "requested": 99, "available": 2 }
  ]
}
```

### A customer's order history

`GET /api/orders?email=thomas@example.com&per_page=15`

Most recent first, paginated. `404` if that email has never bought anything.

### Products running low

`GET /api/products/low-stock`
`GET /api/products/low-stock?threshold=25`

The threshold resolves in three steps, most specific first:

1. a `threshold` parameter on the request,
2. the product's own `low_stock_threshold` column, for lines that move faster than the rest,
3. `INVENTORY_LOW_STOCK_THRESHOLD` in `.env` (defaults to 10).

### Supporting endpoints

`GET /api/products` backs the picker on the counter screen and accepts `?search=`.

`GET /api/customers/lookup?email=` returns a customer and their order count, or `404`. The counter
screen uses it to fill in the name of a returning customer; a `404` is the signal to ask for one.

---

## Schema

```
customers ──< orders ──< order_items >── products
                 │                          │
                 └────< stock_movements >────┘
```

| Table | Notes |
| --- | --- |
| `products` | `code` unique, `unit_price`, `tax_percentage`, `stock_on_hand`, optional `low_stock_threshold` |
| `customers` | `email` unique, stored lower-cased |
| `orders` | totals, plus `amount_tendered` / `change_due` for cash sales |
| `order_items` | quantity and a **snapshot** of price and tax rate, unique per `(order_id, product_id)` |
| `stock_movements` | append-only ledger of every change to `stock_on_hand` |

Three decisions worth calling out.

**Prices are snapshotted onto the order line.** `order_items` stores the `unit_price` and
`tax_percentage` that applied at the moment of sale. Repricing a product tomorrow must not quietly
rewrite last week's bills, and reprinting an old receipt has to produce the same figures it did the
first time.

**`stock_movements` is a supporting table I judged necessary.** `products.stock_on_hand` alone tells
you where stock is now but never how it got there. Every deduction writes a row with the change and
the balance it left behind, so a mismatch between the shelf and the system can be traced back to the
order that caused it. It is also the natural place to hang restocks and manual adjustments.

**The bill number is derived, not stored.** `Order::reference` is an accessor that formats the
primary key as `ORD-20260909-00013`. Storing it as a column would mean a second unique value to
generate safely under concurrency and to keep in sync, for something that is a presentation of the
id and nothing more.

---

## No overselling under concurrent requests

This is the requirement I spent the most time on, so it is worth describing what it actually does.

Order creation runs inside one transaction, in [`OrderService::place()`](app/Services/OrderService.php):

1. **Lock the rows.** Every product in the order is fetched with `lockForUpdate()`, ordered by id.
   A second order touching the same product blocks here until the first one commits or rolls back.
   Ordering by id matters: two orders holding overlapping products in different orders would
   deadlock, and a stable order removes that.
2. **Check stock against the locked rows**, and reject the whole order if any line is short. It is
   all or nothing — a partial fill would leave the counter with a bill that does not match the bag.
3. **Deduct conditionally.** The decrement runs as
   `where('stock_on_hand', '>=', $quantity)->decrement(...)` and the affected row count is checked.
   The lock already serialises things; this is the second line of defence, and it is what keeps the
   invariant on a driver without row locking.
4. **Queue the email with `afterCommit()`**, so no confirmation goes out for a transaction that
   later rolls back.

Stock is checked in the service rather than in the form request on purpose. Availability can change
between validation and the write, so a `FormRequest` rule would be reassuring and wrong. The only
check that can be trusted is the one holding the lock.

### Proving it

`tests/Feature/ConcurrentOrderTest.php` does not simulate concurrency — it launches six separate
`php artisan orders:place` processes at one product with two units in stock and waits for all of
them:

```
PASS  Tests\Feature\ConcurrentOrderTest
✓ overlapping tills cannot sell the same last units
```

Exactly two succeed, four are told the stock ran out, `stock_on_hand` lands on `0`, and there are
exactly two orders. Removing either guard from `OrderService` makes it fail, which is the only real
evidence that the test is doing its job.

It needs a server with row-level locking, so it runs on MySQL against a throwaway schema
(`CONCURRENCY_DB_DATABASE`) that it creates, migrates and tears down itself — it never touches your
development database. If no MySQL server is reachable it skips with a message rather than failing.

The same command is useful by hand:

```bash
php artisan orders:place --email=thomas@example.com --item=7:2 --item=10:1 --tendered=500
```

---

## Tests

```bash
php artisan test
```

```
Tests:  36 passed (126 assertions)
```

The suite runs on in-memory SQLite and takes a few seconds. Beyond the happy path it covers the
cases I would expect to break in production:

- an order rejected for insufficient stock leaves **nothing** behind — no order, no stock movement,
  no queued email, and the lines that *could* have been filled are still on the shelf
- a product with zero stock cannot be sold at all
- repeated lines for the same product are merged rather than violating the per-order unique index
- cash that does not cover the bill is refused before any stock moves
- a returning customer is matched on email regardless of case, and keeps the name already on file
- a name is required only the first time an email is seen
- per-line tax rounding, so the printed lines add up to the printed total
- the low-stock threshold resolving through all three of its levels
- the confirmation job is queued for the right order, and only after the transaction commits
- the customer lookup returns a clean `404` for an unknown email, which is what the counter screen
  reads as "new customer"
- each screen is asserted on the data it puts in front of the user, not just a `200`

---

## Assumptions and judgment calls

The brief asked for these to be written down rather than asked about.

**A UI was built, though only API endpoints were required.** The wireframe shows fields the
functional requirements never mention — cash tendered, balance to return, a denomination
breakdown — so I took the screen as part of the intended scope, and added the two screens a counter
needs alongside it: order history and inventory.

The stack is Blade, Tailwind and Alpine.js. No SPA, no build-time API client, no duplicated
routing: the pages are server-rendered and Alpine handles the parts that genuinely need to be
interactive — the type-ahead, the live totals, validation state. That keeps the whole front end at
roughly 400 lines of JavaScript against an API a reviewer can also drive with curl.

**Money is calculated in integer paise.** Floats are fine for display and wrong for arithmetic.
`App\Support\Money` converts to paise, does the sums, and converts back once. Columns stay
`decimal` so the database is readable and sortable.

**Tax is applied per line and rounded there**, not on the order subtotal. Rounding once at the
bottom is a rupee or two cheaper to compute and produces receipts whose lines do not add up to
their own total, which customers notice.

**The bill is rejected, never partially filled.** If any line is short, nothing is sold.

**Duplicate lines for one product are merged** into a single line with the summed quantity, which
is what a cashier scanning the same item twice means.

**Change is broken into notes and coins down to ₹1.** Paise below a rupee are not dispensable at a
counter, so the breakdown covers the rupee part while `change_due` keeps the exact figure. The
wireframe's own example (`₹22.80 → 1×20 + 1×2 + 1×1`) is inconsistent, as are its line totals
against its subtotal, so I read it as a layout reference rather than a spec for the arithmetic.

**The confirmation email is HTML, not a PDF attachment.** The wireframe annotates the Generate
Bill button with "emails PDF to customer", but the functional requirement asks only that the queued
job simulate sending a confirmation, and explicitly allows a log entry. Pulling in a PDF renderer for
a mailer that never reaches SMTP seemed like weight without value, so the job renders a Markdown
mailable of the bill. Swapping the body for an attachment is a change inside
`SendOrderConfirmation`, not to anything around it.

**A customer is identified by email alone.** Name is required the first time an email is seen and
optional afterwards; a returning customer keeps the name on file unless a new one is typed. Emails
are normalised to lower case so `THOMAS@example.com` and `thomas@example.com` are one person.

**There is no authentication, so there are no `users` or `sessions` tables.** The default Laravel
scaffolding for both was removed rather than left sitting unused in a schema the brief asked to be
normalised; sessions use the file driver. Adding auth later means adding those back, which is a
smaller cost than shipping a schema with tables nobody can explain.

**`GET /api/orders?email=` rather than a path segment.** Putting an email in the URL path invites
encoding problems for addresses containing `+` or `.`, and a query parameter leaves room for the
pagination options that a history endpoint wants anyway.

**Stock is only ever deducted, never restored.** Returns and cancellations are outside the brief.
`stock_movements` already carries a `reason` column, so adding them later is a new movement type
rather than a change to the schema.

**Products are not soft-deleted and cannot be removed** once they appear on an order — the foreign
key is `restrictOnDelete`. Deleting a product would orphan the history that the order-line snapshot
exists to preserve.

---

## AI assistance

AI tooling was used for this task, as the brief encourages. Screenshots of the prompts are in
[`prompts/`](prompts/) alongside a written log of what was asked at each step and where I changed
the direction the output was heading.

## Project layout

```
app/
  Console/Commands/PlaceOrder.php     Command-line sale; the concurrency test runs it in parallel
  Data/                               Readonly DTOs carrying a validated order into the service
  Exceptions/InsufficientStockException.php
  Http/Controllers/Api/               Thin controllers: validate, delegate, return a resource
  Http/Requests/StoreOrderRequest.php Request shape only; stock is the service's business
  Http/Resources/                     API response shaping
  Jobs/SendOrderConfirmation.php      Queued, dispatched afterCommit
  Services/OrderService.php           Locking, stock checks, deduction, the transaction boundary
  Services/OrderTotals.php            Line pricing and tax rounding
  Support/Money.php                   Integer-paise arithmetic
  Support/CashDrawer.php              Change split into notes and coins
resources/js/
  money.js                            Mirrors Money and CashDrawer so totals stay live on screen
  components/orderForm.js             Counter screen: type-ahead, totals, validation, submit
  stores/toasts.js                    Shared success and failure notifications
resources/views/
  components/                         Blade UI kit: field, stat, stock-badge, empty-state
  layouts/app.blade.php               Shell, navigation, toast outlet
  billing/ orders/ products/          The four screens
tests/Feature/ConcurrentOrderTest.php Six real processes against one product
```
