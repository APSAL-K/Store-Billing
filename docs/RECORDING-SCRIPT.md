# Walkthrough script

A running order for the screen recording the brief asks for. Roughly eight minutes at a normal
pace. Say it in your own words — this is a route through the app, not a script to read out.

## 1. What this is (30s)

The brief: a retail counter records customer orders against a product catalogue and keeps stock in
sync. Laravel 12, MySQL, Blade with Alpine on the front. Show the dashboard.

## 2. Raise a bill (90s)

New Order. Point out the three numbered steps: customer, items, payment.

- Open the customer dropdown, type `thom`, pick Thomas. Mention that a walk-in who is not on file
  is added inline with "+ New customer" without leaving the bill.
- Tap two or three product cards. Point out that the card shows price and stock, carries its
  quantity once it is on the bill, and that search filters the grid live.
- Raise a quantity with the stepper — totals update live.
- Tap one of the quick-cash chips — balance to return, plus the notes and coins to hand back.
- Generate Bill. Land on the invoice.

Say: the totals on screen are computed the same way the server computes them, in integer paise with
tax rounded per line, so what you saw is what was saved.

## 3. What happened underneath (60s)

- Terminal: `php artisan queue:work` picking the confirmation job up.
- `storage/logs/laravel.log` — the rendered email.
- Inventory: the two products are down by what was sold.
- Open one product: the stock ledger row for that sale, with the bill it came from.

## 4. Validation (60s)

Back to New Order.

- Submit empty: three field errors and a toast.
- Add Farm Eggs, set the quantity to 40 against 2 in stock, submit. The row goes red, the message
  says how many are actually left, and nothing was saved.

Say: the client checks the obvious things, but stock is only ever checked by the server, inside the
transaction that holds the row lock. Anything else would be a guess.

## 5. Edit and delete (90s)

Orders → open a bill → Edit.

- Drop a line, change a quantity, save.
- Inventory: stock has moved by the difference, not by the whole line.
- The product ledger now shows a "Bill edited" row.

Back to the bill → Delete.

- Stock returns in full.
- The bill is still there under the deleted filter, marked, and can no longer be edited.

Say: every table soft deletes. Removing a bill outright would throw away the record of a
transaction that really happened and leave the ledger pointing at nothing.

Then Customers → add one, rename it, delete it. Point out that a deleted customer's bills still
print with their name on them.

## 6. Concurrency (120s)

The requirement: if two bills for the same product arrive at once and one unit is left, exactly one
should succeed.

- Open `app/Services/OrderService.php`. Walk through the four steps: lock ordered by id, check
  against the locked rows, conditional decrement, queue after commit.
- Run `php artisan test --filter=ConcurrentOrderTest`. Say what it does: six real processes, one
  product, two units.
- Optional and worth doing: comment out the guards, re-run, show it fail, put them back.

## 7. Tests and README (60s)

- `php artisan test` — 67 passing.
- Scroll the edit and delete test files; point at the "changes nothing" assertions.
- Show the README's assumptions section and say that anything ambiguous in the brief was decided
  and written down there rather than left open.

## 8. Close (20s)

What you would do next with more time: authentication and per-user tills, returns as a first-class
movement type, and a nightly reconciliation report off the ledger.
