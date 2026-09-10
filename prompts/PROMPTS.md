# Prompt log

Every prompt I gave the assistant while building this, verbatim and in order.

They are written in Tanglish (Tamil in Latin script, mixed with English), which is how I type. Each
one has a short English gloss underneath so the intent is readable either way. The typos are left
in — this is what was actually sent, not a tidied-up version.

Screenshots of these same prompts as they appear in the editor panel are in
[`screenshots/`](screenshots/). The commentary on what came back, what I kept and what I sent back
for another pass is in [`README.md`](README.md).

Tool used: **Claude Code** (Opus 5) in the VS Code panel.

---

### 1 — The brief

> bro unnoda document read pandra skill ah use panni entha document la ennod erukko apdiyee pannu
> broo professtional and ai pannathu nu theriyaama pannanum bro

*Read the PDF brief and build exactly what it asks for. Make it professional, and don't let it look
AI-generated.*

The assistant read `Laravel_Developer_Mini_Task.pdf`, extracted the wireframe image out of it, and
came back with the scope plus the decisions the brief leaves open.

---

### 2 — Database choice

> bro avanga database nu ethavathu ketrukkangala bro
> apdi na ennoda suggetion vanthu supabase la erukkura database ha use pannikkalam bro

*Did they ask for a specific database? If not, my suggestion is to use the Supabase database.*

Answer: the brief names no database. It asks for migrations and seeders, nothing more.

---

### 3 — Hosting

> bro local bro db eppo avangalukku kudutha mudiyathu le local la thaane run panna mudiyum namma db
> oda vercel la deploye panni thaane kudukkanum bro ? apdina supabase la postgresSql thaane bro crt ?

*We can't hand them a local database — we'd have to deploy it on Vercel, right? And Supabase is
PostgreSQL, correct?*

Answer: the brief asks for a repo, a README, a prompt log and a recording — no deployment. The
reviewer runs `migrate --seed` on their own machine, so the migrations *are* the database. Supabase
is Postgres, yes, but Vercel does not run PHP natively and cannot hold a queue worker.

Settled on: MySQL for development, Postgres documented as working unchanged, SQLite for the tests.

---

### 4 — Connecting MySQL

> bro my sql ha connect pannu bro
>
> ```
> DB_CONNECTION=mysql
> DB_HOST=http://localhost:1414/
> DB_PORT=3306
> DB_DATABASE=Inventory_mini_system
> DB_USERNAME=root
> DB_PASSWORD=C0mplex
> ```

*Connect MySQL, here are the settings.*

`DB_HOST` was a URL, which is not what that field takes. The assistant probed the machine, found
MySQL running in Docker on 3306 with Adminer on 1414, and set the host to `127.0.0.1`.

---

### 5 — The interface

> bro ennaku ui super ah erukkanum bro
> vera lavel ah pannu bro tabs vai haeader and nav bar ellam professtion billing application maari
> podu bro
> ui la inputs ellame super pannu bro select option search and select entha maari ellame pannu bro
> and inpute validation ellame pannu properly errors and success ellame show pannu bro and
> entha project ha paathathu impressive ui and clean articute la erukkanum bro

*I want the UI to be excellent. Next level. Tabs, header, nav bar — like a professional billing
application. Make every input good: searchable select options and so on. Do the input validation
properly, showing errors and successes. Looking at this project it should be impressive UI and
clean architecture.*

Produced the shell, the navigation, the type-ahead product picker, live totals and two-sided
validation.

---

### 6 — Covering the whole brief

> bro bill edit and and delete also panniru bro first avanga document la sonna
> scop and functional requestment, what we're looking for, what to submit,
> ethula ellame antha application cover pannu bro and features knjom add pannikko bro super ahd new
> features new tabs antha maari ellame add pannikko bro and read me file thelivaa vum potru bro
> features ellame and code la //cmd ellame remove pannu bro entha full project la code kulla ni
>
> // cmd panni write panniruppa show athu ellame remove pannu bro and add the screen shots and videos

*Add bill edit and delete. Cover everything the document asks for — Scope, Functional Requirements,
What We're Looking For, What to Submit. Add some more features, new tabs. Write the README clearly
with all the features. And remove every `//` comment from the code across the whole project. Add
the screenshots and videos.*

This is where the comments came out and the reasoning moved into the README, and where editing and
deleting a bill were built.

---

### 7 — Naming, soft deletes and the counter screen

> bro voided na enna antha key name and antha function ellam venam bro and db la soft delete ku
> column podu bro
> customer ku create, and edit , delete options venum bro and
> new oredre page kjndo super ha pannu bro item section ha select card peruse pannu user friednly ya
> erukkanum bro
> order ha view and delelte button kudu bro and table buttons ellame super ha vai bro
> table la propelry pagination ellame podu bro new order la customr ha drop down la create panni
> vechu customer load pannu bro avanga thevai na new customer ha enter pannikkattum athu oru
> featire bro and
> antha tab ha
> la customer, items choose athukku keela payment ha kondu vaa and side column la low stock show
> pannikko bro

*What does "voided" mean? Drop that key name and those functions. Put a soft-delete column in the
database instead. Customers need create, edit and delete. Make the new order page much better — the
item section should use selectable cards, it has to be user friendly. Give orders view and delete
buttons, and make all the table buttons good. Put proper pagination on the tables. On the new order
page, load customers into a dropdown, and let them enter a new customer if they need to — that's a
feature. In that tab put customer, then choose items, then payment below it, and show low stock in
the side column.*

"Voided" is the accounting word; nobody at a counter says it. It became plain delete, with the soft
delete doing the work underneath. The counter screen was rebuilt around a product card grid.

---

### 8 — Soft deletes everywhere

> bro soft delte ellam table kum podu bro

*Put soft deletes on all the tables.*

Every table got a `deleted_at`. That flushed out a real problem with the unique index on
`order_items` — see [`README.md`](README.md).

---

### 9 — Dashboard and product CRUD

> bro dashboard ha ennum elebrate panni super ha show pannu bro
>
> and items oda CRUD pannu ui la podu bro
> and ella table kum pagintaion and ui la pagination podu bro proeprly ah bro

*Make the dashboard more elaborate, show it really well. Put product CRUD in the UI. And put
pagination on every table, done properly in the UI.*

---

### 10 — Shell, theme, setup, responsive

> bro header and footer ha perfect ah pannu bro project oda theme clr atractive ah pannu bro
> maathu bro and and ethu easy setup and responsive ah erukkanum bro web and mobile app ku

*Perfect the header and footer. Make the project's theme colour attractive — change it. And it
should be easy to set up and responsive, for web and mobile.*

Chose deep blue with a dark mode. Setup became one command.

---

### 11 — Light only, and a plainer footer

> bro okey always ligh ve erukkattumbro
> footer la erunthu links ellame eduthuru bro
> and clr theme mattum atractive ah change pannu bro

*Okay, let it always be light. Take all the links out of the footer. And change just the colour
theme to something attractive.*

Dark mode came out. The palette went violet on warm grey.

---

### 12 — Navy, and this log

> bro dark blur la change panniru bro theme and
>
> promts add pannu bro and screenshots um add pannu bro
> avanga eppadi sonnangalo apdiye

*Change the theme to dark blue. And add the prompts and the screenshots, exactly the way they
asked.*

"dark blur" was ambiguous between a dark blue palette and a full dark mode, so the assistant asked
rather than guessing. Dark blue it was. This file is the other half of that request.
