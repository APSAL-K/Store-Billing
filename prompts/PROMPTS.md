# Prompt log

Every prompt I gave the assistant while building this, in order.

I type to the assistant in Tanglish — Tamil in Latin script, mixed with English — so the prompts in
the [screenshots](screenshots/) read that way. They are written out here in English, which is the
same request in a form a reader can follow. The screenshots are the literal record; this file is
the readable one.

Underneath each prompt is what came back and what I did with it. The commentary on where I sent
work back for another pass, and on the bugs the assistant wrote, is in [`README.md`](README.md).

Tool used: **Claude Code** (Opus 5) in the VS Code panel.

---

### 1 — The brief

> Read the attached document and build exactly what it asks for. It should look professional, and
> it should not look like it was generated.

The assistant read `Laravel_Developer_Mini_Task.pdf`, pulled the wireframe image out of it, and
came back with the scope plus the decisions the brief deliberately leaves open — database, whether
to build a UI, how to read the wireframe's arithmetic.

---

### 2 — Database choice

> Does the brief ask for a specific database? If it does not, I would like to use a hosted Postgres
> database on Supabase.

Answer: the brief names no database at all. It asks for a normalised schema, migrations and
seeders, and says nothing about where they run. So the choice is ours to make and to document.

---

### 3 — Hosting

> We cannot hand them our local database — surely we have to deploy it for them to see it? And
> Supabase is PostgreSQL, correct?

Answer: the brief asks for a repository, a README, a prompt log and a screen recording. There is no
live URL in the submission list. The reviewer clones the repo and runs `migrate --seed` against
their own server, which means the migrations *are* the database as far as the submission goes.

Supabase is Postgres, yes. But Vercel does not run PHP natively and cannot hold a long-lived queue
worker, so it is the wrong host for requirement 5.

Settled on MySQL for development, Postgres documented as working unchanged, SQLite for the tests.

---

### 4 — Connecting MySQL

> Connect the application to MySQL using these settings:
>
> ```
> DB_CONNECTION=mysql
> DB_HOST=http://localhost:1414/
> DB_PORT=3306
> DB_DATABASE=Inventory_mini_system
> DB_USERNAME=root
> DB_PASSWORD=C0mplex
> ```

`DB_HOST` had been given as a URL, which is not what that field takes. The assistant probed the
machine, found MySQL running in Docker on 3306 with Adminer on 1414, and corrected the host to
`127.0.0.1`.

---

### 5 — The interface

> The interface needs to be excellent — a level above the usual. Give it a proper header, navigation
> and tabs, the way a real billing application looks. Make every input good, including searchable
> select fields. Handle input validation properly, showing both errors and success states. Anyone
> looking at this project should find the interface impressive and the architecture clean.

Produced the application shell, the navigation, the type-ahead product picker, live totals and
validation on both the client and the server.

---

### 6 — Covering the whole brief

> Add editing and deleting a bill. Go back through the document and make sure the application covers
> everything it lists under Scope, Functional Requirements, What We're Looking For and What to
> Submit. Add some more features and screens beyond that. Write the README clearly, covering all of
> the features. Remove every `//` comment from the code across the whole project. Add the
> screenshots and the video.

This is where the comments came out of the code and the reasoning behind each decision moved into
the README, and where editing and deleting a bill were built.

---

### 7 — Naming, soft deletes and the counter screen

> "Voided" is not a word I want in this — drop that name and the functions built around it, and use
> a soft-delete column in the database instead. Customers need create, edit and delete. Rework the
> new order page: the item section should use selectable cards so it is genuinely easy to use. Give
> orders view and delete buttons, and make all the table buttons look right. Put proper pagination
> on the tables. On the new order page, load existing customers into a dropdown, and allow a new
> customer to be entered inline when one is needed — treat that as a feature. Order that screen as
> customer, then items, then payment below them, with low stock in the side column.

"Voided" is the accounting term; nobody standing at a counter says it. It became plain *delete*,
with the soft delete doing the work underneath. The counter screen was rebuilt around a product
card grid.

---

### 8 — Soft deletes everywhere

> Put soft deletes on every table, not just orders.

Every table got a `deleted_at`. That immediately surfaced a real problem with the unique index on
`order_items` — a soft-deleted row still occupies it, so replacing lines during an edit would fail
the second time the same product appeared. Documented in [`README.md`](README.md).

---

### 9 — Dashboard and product CRUD

> Make the dashboard considerably richer — it should show a lot more than it does. Add product
> create, edit and delete to the interface. And put pagination on every table, properly, in the UI
> as well as the query.

---

### 10 — Shell, theme, setup, responsive

> Get the header and footer right. The project's colour theme should be attractive — change it. It
> also needs to be easy to set up, and properly responsive for both desktop and mobile.

Chose a deep blue with a dark mode. Setup became a single command.

---

### 11 — Light only, and a plainer footer

> Keep it light at all times. Take the navigation links out of the footer. And change just the
> colour theme to something attractive.

Dark mode came out. The palette went to violet on warm grey.

---

### 12 — Navy, and this log

> Change the theme to dark blue. And add the prompt log and the screenshots, exactly as the brief
> asks for them.

"Dark blue" was ambiguous between a navy palette on a light interface and a full dark mode, so the
assistant asked rather than guessing. Navy on light it was. This file is the other half of the
request.
