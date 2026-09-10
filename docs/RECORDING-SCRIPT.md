# Walkthrough script

A running order and a narration for the screen recording the brief asks for. Around eight minutes
at a normal pace, which sits inside the five-to-ten it wants.

Say it in your own words — reading it flat is worse than a few *ums*. The point of writing it out
is that you never have to stop and think about what comes next.

## Before you press record

```bash
php artisan app:install --fresh     # clean data
php artisan orders:place --email=thomas@example.com --item=7:6 --item=10:2 --tendered=1000
php artisan orders:place --email=divya@example.com --item=4:1 --item=15:2 --tendered=1500
php artisan orders:place --email=priya@example.com --name="Priya Nair" --item=11:4 --item=13:1 --tendered=2000
php artisan queue:work --stop-when-empty
```

That gives the dashboard something to show. Then:

- Two terminal tabs: one on `php artisan serve`, one free for `queue:work` and `test`.
- Browser at `http://localhost:8000`, zoom 100%, bookmarks bar hidden.
- Editor open on `app/Services/OrderService.php`.
- Close anything you would not want in the recording.

---

## 1 · What this is — 30s

**Dashboard.**

> This is the Store Order and Inventory mini-system from the Mallow brief. A retail counter records
> customer orders against a product catalogue and keeps stock in sync. It is Laravel 12 on MySQL,
> with Blade and Alpine on the front. The JSON API is the part the brief specifies; these screens
> are built on that same API.

Let the dashboard sit for a moment — today's takings against yesterday, the fourteen-day trend, what
needs reordering, and a live feed of every stock movement.

## 2 · Raise a bill — 90s

**New Order.** Point at the three numbered steps.

> The bill is built in three steps down one column: customer, items, payment.

Open the customer dropdown, type `thom`, pick Thomas.

> Customers come from a dropdown of everyone on file. A walk-in who isn't on file yet gets added
> inline with this button, without leaving the bill.

Tap three or four product cards. Bump one with the stepper.

> Products are a card grid rather than a dropdown — the whole catalogue is visible, search filters
> it live, and a card shows its price, what's left, and how many are on this bill.

Tap a quick-cash chip.

> Cash tendered, balance to return, and the notes and coins to hand back.

**Generate Bill.** Land on the invoice.

> The totals you just watched were computed the same way the server computes them — integer paise,
> tax rounded per line — so what was on screen is what got saved.

## 3 · What happened underneath — 60s

- Terminal: run `php artisan queue:work`, show the job being picked up.
- `storage/logs/laravel.log` — the rendered confirmation email.
- **Inventory** — the products are down by what was sold.
- Open one product — the ledger row for that sale, with the bill that caused it.

> Stock never just changes. Every movement is written down with the balance it left behind and the
> bill that caused it, which is why the shelf and the system can always be reconciled.

## 4 · Validation — 60s

**New Order.** Submit it empty.

> Three field errors and a toast. That's the client catching the obvious things.

Add Farm Eggs, set the quantity to 40 against 2 in stock, submit.

> This one is different. The row goes red, and the message says how many are actually left — that
> number came from the server, not the browser. Stock is only ever checked inside the transaction
> that holds the row lock. Anything else would be a guess, because availability can change between
> validating and writing.

Confirm nothing was saved.

## 5 · Edit and delete — 90s

**Orders** → open a bill → **Edit**.

Drop a line, change a quantity, save.

> Editing reconciles stock as a delta, not by putting it all back and taking it again. Only the
> difference moves.

**Inventory** — stock moved by the difference. Product page — a "bill edited" row in the ledger.

Back to the bill → **Delete**.

> Stock returns in full, and the bill stays.

Show it under the deleted filter.

> Every table soft deletes. Deleting a tax invoice outright throws away the record of something that
> really happened and leaves the ledger pointing at nothing. From the counter it's gone; from an
> auditor's point of view it's still there.

**Customers** — add one, rename it, delete it.

> A deleted customer's bills still print with their name on them.

## 6 · Concurrency — 120s

This is the requirement worth the most time. Open `app/Services/OrderService.php`.

> Requirement seven: if two bills for the same product arrive at once and one unit is left, exactly
> one should succeed.

Walk the four steps in `place()`:

> One. Every product in the order is locked with `lockForUpdate`, ordered by id. A second bill
> touching the same product blocks here. Ordering by id matters — two bills holding overlapping
> products in different orders would deadlock.
>
> Two. Stock is checked against the locked rows, and the whole bill is rejected if any line is
> short. All or nothing; a partial fill leaves the counter with a bill that doesn't match the bag.
>
> Three. The deduction runs as a conditional update and the affected row count is checked. The lock
> already serialises this — that's the second line of defence.
>
> Four. The confirmation job is dispatched after commit, so nothing goes out for a transaction that
> later rolls back.

Run it:

```bash
php artisan test --filter=ConcurrentOrderTest
```

> That test doesn't simulate concurrency. It launches six real processes at one product with two
> units in stock. Exactly two succeed, four are told the stock ran out, and stock lands on zero.

Worth doing if you have the nerve: comment out the guards, re-run, show it fail, put them back.

## 7 · Tests and README — 60s

```bash
php artisan test
```

> Eighty-four tests. Beyond the happy path they cover the cases I'd expect to break in production.

Scroll `EditOrderTest` and `DeleteOrderTest`; point at the "changes nothing" assertions.

> A rejected edit leaves both the bill and the shelf exactly as they were. Deleting twice returns a
> conflict and doesn't credit the stock a second time.

Show the README's assumptions section.

> The brief said to make a call on anything ambiguous and write down the reasoning rather than stop
> and ask. Every one of those is here — why money is in integer paise, why tax rounds per line, why
> the wireframe's own arithmetic didn't add up and how I read it.

## 8 · Close — 20s

> Setup is one command — `composer setup` — which creates the database if it's missing, migrates,
> seeds and builds the front end.
>
> With more time: authentication and per-user tills, returns as their own movement type, and a
> nightly reconciliation report off the ledger.

---

## Recording it

QuickTime → File → New Screen Recording, with the microphone picked from the arrow next to the
record button. Check the mic level on ten seconds of test footage before recording the whole thing.

If a take goes wrong, start that section again rather than the whole video — you can trim in
QuickTime, and nobody minds a cut.
