# Prompt screenshots

The brief asks for screenshots of the actual prompts, taken from the chat or IDE panel. Drop them
in this folder.

Every prompt is written out in English in [`../PROMPTS.md`](../PROMPTS.md). These images are the
literal record — the prompts as they were typed, in Tanglish.

## What to capture

One image per prompt, twelve in all, matching the numbering in `PROMPTS.md`:

| File | Prompt |
| --- | --- |
| `01-brief.png` | Read the PDF and build what it asks for |
| `02-database.png` | Did they ask for a specific database? |
| `03-hosting.png` | Do we need to deploy it? Is Supabase Postgres? |
| `04-mysql.png` | Connect MySQL, with the `DB_` block |
| `05-ui.png` | Make the UI next level, tabs, header, validation |
| `06-scope.png` | Cover the whole brief, add edit and delete, strip comments |
| `07-counter.png` | Drop "voided", soft deletes, customer CRUD, card grid |
| `08-soft-deletes.png` | Soft deletes on every table |
| `09-dashboard.png` | Elaborate the dashboard, product CRUD, pagination |
| `10-shell.png` | Header, footer, theme, easy setup, responsive |
| `11-light.png` | Light only, plainer footer, change the palette |
| `12-navy.png` | Navy palette, and add this prompt log |

## Taking them

Scroll the Claude Code panel back to each prompt and capture the message bubble. Include enough of
the reply that it is obvious which prompt produced what, but there is no need to capture whole
answers — the reviewer wants to see what was asked.

- **macOS:** `Cmd + Shift + 4`, then drag over the message. `Cmd + Shift + 5` for a window capture.
- **Windows:** `Win + Shift + S`.

A few long scrolling captures covering several prompts each are fine too, as long as every prompt
in the table above appears somewhere and the filenames say which. If you do that, rename them
`01-04-brief-and-database.png` and so on, and this table still tells the reviewer where to look.

Once the files are here they need no wiring up — the README links to this folder, not to individual
images.
