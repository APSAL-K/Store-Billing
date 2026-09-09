# Store Order & Inventory Mini-System

A Laravel application for a retail counter: bill a customer against a product catalogue, keep stock
in sync, and edit or delete a bill afterwards without the stock ledger drifting.

Built as a take-home assignment for Mallow Technologies. The JSON API is the part the brief
specifies; the screens on top of it use that same API.

**Laravel 12** · **PHP 8.4** · **MySQL 8+ / MariaDB** (PostgreSQL works unchanged) ·
**Blade + Alpine.js + Tailwind 4** · **67 tests**

![Dashboard](docs/screenshots/01-dashboard.png)

---

## Contents

- [Setup](#setup)
- [What it does](#what-it-does)
- [The screens](#the-screens)
- [The API](#the-api)
- [Schema](#schema)
- [No overselling under concurrent requests](#no-overselling-under-concurrent-requests)
- [Editing and deleting a bill](#editing-and-deleting-a-bill)
- [Tests](#tests)
- [How the brief is covered](#how-the-brief-is-covered)
- [Assumptions and judgment calls](#assumptions-and-judgment-calls)
- [Project layout](#project-layout)
- [AI assistance](#ai-assistance)

---

## Setup

```bash
git clone <repo-url> store-billing
cd store-billing

composer install
cp .env.example .env
php artisan key:generate
```

Point the `DB_*` block in `.env` at your server, then create and fill the schema:

```bash
mysql -u root -p -e "CREATE DATABASE store_billing"
php artisan migrate --seed
```

Build the front end and start the app. The queue worker is a separate process, which is the point
of putting the confirmation email on a queue in the first place:

```bash
npm install && npm run build     # Node 20+ (Vite 7 / Tailwind 4)
php artisan serve                # http://localhost:8000
php artisan queue:work           # in a second terminal
```

The seed leaves you fifteen products (a few deliberately short on stock so the low-stock alert has
something to show), ten customers and twelve past bills. Two customers have predictable emails for
testing: `thomas@example.com` and `divya@example.com`.

Confirmation emails do not go over SMTP. `MAIL_MAILER=log` writes each rendered message to
`storage/logs/laravel.log`, so you can watch the worker pick a job up and read the bill it produced.

---

## What it does

**Billing** — three steps down one column: customer, items, payment

- Customer picked from a searchable dropdown, or added inline without leaving the bill
- Products chosen from a card grid with a live search; a card shows its price, stock and how many
  are on the bill
- Line quantities clamped to stock, with live subtotal, per-line tax and grand total
- Cash tendered with quick-amount chips, balance to return, and the notes and coins to hand back
- Validation on both sides, with server errors mapped back onto the field that caused them

**Orders**

- Full bill history, filterable by customer email or by deleted
- A printable tax invoice for every bill
- **Edit a bill** — change lines, quantities or the customer; stock is reconciled as a delta
- **Delete a bill** — every unit goes back on the shelf, the record is kept for the audit trail

**Inventory**

- Catalogue with stock levels, search and a low-stock filter
- **Restock** any product, recorded as its own movement
- Per-product **stock ledger**: every change to stock with the bill that caused it

**Dashboard**

- Today's revenue, units and tax; a fourteen-day revenue trend
- Best sellers, recent bills, and what needs reordering

**Customers**

- Add, edit and delete a customer from the customer list
- Ranked by lifetime value, with bill count and last purchase
- A per-customer page with their full history, deleted bills included

---

## The screens

| Route | Screen |
| --- | --- |
| `/` | Dashboard |
| `/pos` | New Order |
| `/orders` | Orders, filterable by email or deleted |
| `/orders/{order}` | Printable bill |
| `/orders/{order}/edit` | Edit a bill |
| `/customers`, `/customers/{customer}` | Customers and their history |
| `/products`, `/products/{product}` | Inventory and a product's stock ledger |

### New Order

The bill is built in three numbered steps down one column — customer, items, payment — with what
needs reordering kept out of the way in the side column.

![New order](docs/screenshots/04-new-order-filled.png)

The customer comes from a searchable dropdown of everyone on file.

![Customer picker](docs/screenshots/03-customer-picker.png)

A walk-in who is not on file yet is added inline, without leaving the bill.

![New customer](docs/screenshots/05-new-customer-inline.png)

Products are a card grid rather than a dropdown: search filters it live, a tap adds a unit, and a
card that is already on the bill carries its quantity.

![New order, empty](docs/screenshots/02-new-order-empty.png)

### Validation

The client catches the obvious cases before a request is made.

![Validation](docs/screenshots/06-validation-errors.png)

Anything the server rejects comes back onto the field that caused it. A stock shortage highlights
the offending row and says how many are actually left — that number is the server's answer, not the
browser's guess.

![Stock shortage](docs/screenshots/07-stock-shortage.png)

### Orders

Every bill, with View, Edit and Delete on the row.

![Orders](docs/screenshots/08-orders.png)

A deleted bill is not gone: it stays under its own filter, marked, and can still be opened.

![Deleted bills](docs/screenshots/09-orders-deleted.png)

![Bill](docs/screenshots/10-bill.png)

### Editing and deleting

Editing reopens the bill with its lines loaded and the units it already holds added back to
sellable stock, so a cashier can raise a quantity without the screen claiming there is none left.

![Edit a bill](docs/screenshots/11-edit-bill.png)

![Delete a bill](docs/screenshots/12-delete-bill.png)

### Customers

Add, edit and delete from the list.

![Customers](docs/screenshots/13-customers.png)

![Customer form](docs/screenshots/14-customer-form.png)

![Customer detail](docs/screenshots/15-customer-detail.png)

### Inventory

![Inventory](docs/screenshots/16-inventory.png)

Every product carries its own ledger. Sales, edits, deletions and restocks all land here with the
balance they left behind.

![Stock ledger](docs/screenshots/17-stock-ledger.png)

![Restock](docs/screenshots/18-restock.png)

### On a phone

<img src="docs/screenshots/19-mobile-pos.png" width="320" alt="The counter screen on a phone">

---

## The API

### Create a bill

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
each line with its own tax rate, writes the bill, deducts stock, and queues the confirmation email.
`201` with the created order:

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

### Edit a bill

`PUT /api/orders/{order}`

Takes the same body as `POST`. The line set is replaced, stock is reconciled as a delta, and the
bill is repriced. Editing a deleted bill returns `409`.

### Delete a bill

`DELETE /api/orders/{order}`

```bash
curl -X DELETE http://localhost:8000/api/orders/13 -H 'Accept: application/json'
```

`204`. Every unit goes back on the shelf and the bill is soft deleted. Deleting a deleted bill
returns `409` rather than crediting the stock twice.

### Customers

```
GET    /api/customers            list, with ?search=
POST   /api/customers            create
PUT    /api/customers/{customer} update
DELETE /api/customers/{customer} delete
GET    /api/customers/lookup?email=
```

```bash
curl -X POST http://localhost:8000/api/customers \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{ "name": "Priya Nair", "email": "priya@example.com" }'
```

Emails are unique and normalised to lower case; the uniqueness rule ignores the customer being
edited so saving a record unchanged does not trip over itself.

### A customer's bill history

`GET /api/orders?email=thomas@example.com&per_page=15`

Most recent first, paginated. `404` if that email has never bought anything. Deleted bills are
excluded unless you pass `include_deleted=1`.

### Products running low

`GET /api/products/low-stock`
`GET /api/products/low-stock?threshold=25`

The threshold resolves in three steps, most specific first:

1. a `threshold` parameter on the request,
2. the product's own `low_stock_threshold` column, for lines that move faster than the rest,
3. `INVENTORY_LOW_STOCK_THRESHOLD` in `.env` (defaults to 10).

### Restock a product

`POST /api/products/{product}/restock`

```bash
curl -X POST http://localhost:8000/api/products/2/restock \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{ "quantity": 50, "note": "Supplier invoice 4471" }'
```

### Supporting endpoints

`GET /api/products` backs the picker on the counter screen and accepts `?search=`.

`GET /api/customers/lookup?email=` returns a customer and their bill count, or `404`. The counter
screen uses it to fill in the name of a returning customer; the `404` is the signal to ask for one.

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
| `orders` | totals, `amount_tendered` / `change_due` |
| `order_items` | quantity and a **snapshot** of price and tax rate, unique per `(order_id, product_id)` |
| `stock_movements` | append-only ledger of every change to `stock_on_hand` |

Every table carries a `deleted_at`, so nothing in this schema is ever removed outright.

Four decisions worth calling out.

**Prices are snapshotted onto the order line.** `order_items` stores the `unit_price` and
`tax_percentage` that applied at the moment of sale. Repricing a product tomorrow must not quietly
rewrite last week's bills, and reprinting an old receipt has to produce the same figures it did the
first time.

**`stock_movements` is the supporting table the brief invites.** `products.stock_on_hand` alone
tells you where stock is now but never how it got there. Every sale, edit, deletion and restock writes a
row with the change and the balance it left behind, so a mismatch between the shelf and the system
can be traced back to the bill that caused it. It is what the per-product ledger screen renders.

**The bill number is derived, not stored.** `Order::reference` is an accessor that formats the
primary key as `ORD-20260909-00013`. Storing it as a column would mean a second unique value to
generate safely under concurrency and to keep in sync, for something that is a presentation of the
id and nothing more.

**Deleting is soft, everywhere.** A tax invoice that has been handed to a customer is not something
to remove a row for, and neither is a customer with history behind them. Every table soft deletes:
the record stays, it just stops appearing. A deleted bill returns its stock, stops counting towards
revenue, and remains readable under the deleted filter.

One consequence worth knowing about: `order_items` has a unique index on `(order_id, product_id)`,
and a soft-deleted row still occupies it. Replacing the lines during an edit therefore uses
`forceDelete()` — the invoice is the record that matters, not the intermediate line sets it passed
through.

---

## No overselling under concurrent requests

This is the requirement I spent the most time on, so it is worth describing what it actually does.

Every write that touches stock runs inside one transaction in
[`OrderService`](app/Services/OrderService.php):

1. **Lock the rows.** Every product involved is fetched with `lockForUpdate()`, ordered by id. A
   second bill touching the same product blocks here until the first commits or rolls back.
   Ordering by id matters: two bills holding overlapping products in different orders would
   deadlock, and a stable order removes that.
2. **Check stock against the locked rows**, and reject the whole bill if any line is short. It is
   all or nothing — a partial fill would leave the counter with a bill that does not match the bag.
3. **Apply the change conditionally.** A deduction runs as
   `where('stock_on_hand', '>=', $quantity)->decrement(...)` and the affected row count is checked.
   The lock already serialises things; this is the second line of defence, and it is what keeps the
   invariant on a driver without row locking.
4. **Queue the email with `afterCommit()`**, so no confirmation goes out for a transaction that
   later rolls back.

Stock is checked in the service rather than in the form request on purpose. Availability can change
between validation and the write, so a `FormRequest` rule would be reassuring and wrong. The only
check that can be trusted is the one holding the lock.

### Proving it

[`tests/Feature/ConcurrentOrderTest.php`](tests/Feature/ConcurrentOrderTest.php) does not simulate
concurrency — it launches six separate `php artisan orders:place` processes at one product with two
units in stock and waits for all of them:

```
PASS  Tests\Feature\ConcurrentOrderTest
✓ overlapping tills cannot sell the same last units
```

Exactly two succeed, four are told the stock ran out, `stock_on_hand` lands on `0`, and there are
exactly two bills. Removing either guard from `OrderService` makes it fail, which is the only real
evidence that the test is doing its job.

It needs a server with row-level locking, so it runs on MySQL against a throwaway schema
(`CONCURRENCY_DB_DATABASE`) that it creates, migrates and tears down itself — it never touches your
development database. If no MySQL server is reachable it skips with a message rather than failing.

The same command is useful by hand:

```bash
php artisan orders:place --email=thomas@example.com --item=7:2 --item=10:1 --tendered=500
```

---

## Editing and deleting a bill

Editing is the harder of the two, because stock has to be reconciled rather than simply taken.
`OrderService::update()` compares the previous line quantities against the requested ones and
applies **one delta per product**:

| Change | Delta | Effect on stock |
| --- | --- | --- |
| Quantity 8 → 3 | `+5` | five units returned |
| Quantity 3 → 9 | `-6` | six more taken, if available |
| Line removed | `+quantity` | all its units returned |
| Line added | `-quantity` | taken like a new sale |

The whole reconciliation runs inside the same locked transaction as a new sale, over the union of
the old and new product sets. If any product cannot cover its delta the edit is rejected and the
bill is left exactly as it was — the tests assert that both the lines and the stock are unchanged
after a failed edit.

Deleting is the same machinery with every line as a positive delta, followed by a soft delete. Both
operations write `stock_movements` rows tagged with their reason, so the ledger reads as a story:
sale, bill edited, bill deleted, restock.

---

## Tests

```bash
php artisan test
```

```
Tests:  67 passed (286 assertions)
```

The suite runs on in-memory SQLite and takes a few seconds. Beyond the happy path it covers the
cases I would expect to break in production.

**Creating a bill**
- a bill rejected for insufficient stock leaves **nothing** behind — no order, no stock movement, no
  queued email, and the lines that *could* have been filled are still on the shelf
- a product with zero stock cannot be sold at all
- repeated lines for the same product are merged rather than violating the per-order unique index
- cash that does not cover the bill is refused before any stock moves
- a returning customer is matched on email regardless of case, and keeps the name already on file
- a name is required only the first time an email is seen
- per-line tax rounding, so the printed lines add up to the printed total
- the confirmation job is queued for the right order, and only after the transaction commits

**Editing a bill**
- reducing, increasing, adding and removing lines each move exactly the right number of units
- an edit that cannot be stocked changes neither the bill nor the shelf
- the adjustment is recorded as its own stock movement with the correct balance
- cash that no longer covers a grown bill is refused

**Deleting a bill**
- every unit goes back, and the bill is kept as a soft-deleted record with its lines intact
- a deleted bill drops out of history unless explicitly asked for
- deleting twice returns `409` and does **not** credit the stock a second time
- a deleted bill can be neither edited through the API nor opened in the edit screen

**Customers**
- created, renamed and deleted through the API, with emails normalised and kept unique
- the uniqueness rule ignores the record being edited
- a deleted customer disappears from the list and from the counter lookup, while their bills stay
  readable with their name on them

**Inventory and reads**
- the low-stock threshold resolving through all three of its levels
- a restock lifts a product back out of the low-stock list, and its balance is recorded correctly
- the customer lookup returns a clean `404` for an unknown email
- the dashboard leaves deleted bills out of revenue
- each screen is asserted on the data it puts in front of the user, not just a `200`

**Concurrency**
- six real processes against one product, described above

---

## How the brief is covered

| Requirement | Where |
| --- | --- |
| Products: name, unique code, price, tax percentage, stock | [`products` migration](database/migrations/2026_09_08_100100_create_products_table.php) |
| Customers: name, unique email | [`customers` migration](database/migrations/2026_09_08_100200_create_customers_table.php) |
| Orders: one customer, one or more lines, computed totals | [`orders`](database/migrations/2026_09_08_100300_create_orders_table.php), [`order_items`](database/migrations/2026_09_08_100400_create_order_items_table.php) |
| Seed with factories and seeders | [`database/seeders`](database/seeders), [`database/factories`](database/factories) |
| 1. Normalised schema, plus supporting tables | [Schema](#schema), [`stock_movements`](database/migrations/2026_09_08_100500_create_stock_movements_table.php) |
| 2. Create-order endpoint with stock, tax and totals | [`OrderController@store`](app/Http/Controllers/Api/OrderController.php) → [`OrderService::place`](app/Services/OrderService.php) |
| 3. Customer order history by email | [`OrderController@index`](app/Http/Controllers/Api/OrderController.php) |
| 4. Low-stock endpoint, configurable threshold | [`ProductController@lowStock`](app/Http/Controllers/Api/ProductController.php), [`config/inventory.php`](config/inventory.php) |
| 5. Queued job on order creation | [`SendOrderConfirmation`](app/Jobs/SendOrderConfirmation.php) |
| 6. Feature and unit tests with edge cases | [`tests`](tests) — 67 tests |
| 7. Safe under concurrent requests | [Concurrency](#no-overselling-under-concurrent-requests), [`ConcurrentOrderTest`](tests/Feature/ConcurrentOrderTest.php) |
| Eloquent relationships, migrations, form-request validation | [`app/Models`](app/Models), [`app/Http/Requests`](app/Http/Requests) |
| Thin controllers, logic in services | [`app/Services`](app/Services) — controllers validate, delegate, return a resource |
| README with setup and assumptions | this file |
| Prompt log | [`prompts/`](prompts/) |
| Screen recording | script in [`docs/RECORDING-SCRIPT.md`](docs/RECORDING-SCRIPT.md) |

---

## Assumptions and judgment calls

The brief asked for these to be written down rather than asked about.

**A UI was built, though only API endpoints were required.** The wireframe shows fields the
functional requirements never mention — cash tendered, balance to return, a denomination
breakdown — so I took the screen as part of the intended scope, and added the screens a counter
actually needs around it.

The stack is Blade, Tailwind and Alpine.js. No SPA, no build-time API client, no duplicated
routing: the pages are server-rendered and Alpine handles only the parts that genuinely need to be
interactive.

**Money is calculated in integer paise.** Floats are fine for display and wrong for arithmetic.
[`App\Support\Money`](app/Support/Money.php) converts to paise, does the sums, and converts back
once. Columns stay `decimal` so the database is readable and sortable.
[`resources/js/money.js`](resources/js/money.js) deliberately mirrors it so the figure on screen is
the figure that gets saved.

**Tax is applied per line and rounded there**, not on the order subtotal. Rounding once at the
bottom is a rupee or two cheaper to compute and produces receipts whose lines do not add up to their
own total, which customers notice.

**The bill is rejected, never partially filled.** If any line is short, nothing is sold.

**Duplicate lines for one product are merged** into a single line with the summed quantity, which
is what a cashier scanning the same item twice means.

**Change is broken into notes and coins down to ₹1.** Paise below a rupee are not dispensable at a
counter, so the breakdown covers the rupee part while `change_due` keeps the exact figure. The
wireframe's own example (`₹22.80 → 1×20 + 1×2 + 1×1`) is inconsistent, as are its line totals
against its subtotal, so I read it as a layout reference rather than a spec for the arithmetic.

**Nothing is hard deleted.** Every table soft deletes. Removing a bill throws away the record of a
transaction that really happened and leaves the stock ledger pointing at nothing; removing a
customer takes their name off bills that have already been printed. From the counter's point of
view a deleted record is gone, and from the auditor's it is still there.

**Editing a bill re-sends the confirmation.** The customer's copy is now wrong, so the job is
dispatched again with the revised bill. Deleting does not send anything — telling someone their bill
was cancelled is a decision for whoever cancelled it, not an automatic email.

**The edit screen counts the bill's own units as available.** A bill holding four units of a
product that has six left can be raised to ten, because those four are only committed to that bill.
The server checks the real delta under a lock regardless.

**A customer is identified by email alone.** The counter screen picks one from a dropdown, but the
API still keys on email so an integration can post a bill without looking an id up first. A name is
required only the first time an email is seen. Emails are normalised to lower case so
`THOMAS@example.com` and `thomas@example.com` are one person.

**Deleting a customer does not touch their bills.** Their name and email were snapshotted onto
nothing — the bill points at the customer row — so the relation reads through the soft delete with
`withTrashed()`. The bill still prints correctly; the customer simply stops appearing in the list
and in the counter's dropdown.

**The confirmation email is HTML, not a PDF attachment.** The wireframe annotates the Generate Bill
button with "emails PDF to customer", but the functional requirement asks only that the queued job
simulate sending a confirmation, and explicitly allows a log entry. Pulling in a PDF renderer for a
mailer that never reaches SMTP seemed like weight without value, so the job renders a Markdown
mailable and the bill screen is print-styled instead.

**There is no authentication, so there are no `users` or `sessions` tables.** The default Laravel
scaffolding for both was removed rather than left sitting unused in a schema the brief asked to be
normalised; sessions use the file driver. Adding auth later means adding those back, which is a
smaller cost than shipping a schema with tables nobody can explain.

**`GET /api/orders?email=` rather than a path segment.** Putting an email in the URL path invites
encoding problems for addresses containing `+` or `.`, and a query parameter leaves room for the
pagination options a history endpoint wants anyway.

**Products cannot be deleted** once they appear on a bill — the foreign key is `restrictOnDelete`.
Deleting a product would orphan the history that the order-line snapshot exists to preserve.

**The code carries no comments.** Reasoning that would have gone in a comment is in this README
instead, next to the decision it explains. Docblocks are kept only where they carry types a reader
or static analysis needs.

---

## Project layout

```
app/
  Console/Commands/PlaceOrder.php     Command-line sale; the concurrency test runs it in parallel
  Data/                               Readonly DTOs carrying a validated bill into the service
  Exceptions/                         InsufficientStock, OrderNotEditable — both render themselves
  Http/Controllers/Api/               JSON API: validate, delegate, return a resource
  Http/Controllers/                   Screens: query, hand to a view
  Http/Requests/                      Request shape only; stock is the service's business
  Http/Resources/                     API response shaping
  Jobs/SendOrderConfirmation.php      Queued, dispatched afterCommit
  Mail/OrderConfirmation.php          Markdown mailable of the bill
  Models/                             Product, Customer, Order, OrderItem, StockMovement
  Services/OrderService.php           Locking, stock reconciliation, the transaction boundary
  Services/OrderTotals.php            Line pricing and tax rounding
  Services/InventoryService.php       Restocking
  Services/DashboardMetrics.php       Dashboard queries, kept out of the controller
  Support/Money.php                   Integer-paise arithmetic
  Support/CashDrawer.php              Change split into notes and coins
resources/js/
  money.js                            Mirrors Money and CashDrawer so totals stay live on screen
  components/orderForm.js             Counter screen: customer, product cards, totals, validation
  components/customerForm.js          Customer add, edit and delete
  components/deleteOrder.js           Delete confirmation
  components/restockProduct.js        Restock dialog
  stores/toasts.js                    Shared success and failure notifications
resources/views/
  components/                         Blade UI kit: field, stat, stock-badge, empty-state,
                                      modal, pagination
  layouts/app.blade.php               Shell, navigation, toast outlet
  dashboard/ billing/ orders/ customers/ products/
tests/Feature/ConcurrentOrderTest.php Six real processes against one product
```

---

## AI assistance

AI tooling was used for this task, as the brief encourages. Screenshots of the prompts are in
[`prompts/`](prompts/) alongside a written log of what was asked at each step and where I changed
the direction the output was heading.
