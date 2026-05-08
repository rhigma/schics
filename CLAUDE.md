# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

SchiCs ("Schulinterne Curricula") — small PHP webapp for managing versioned school-internal curriculum entries. UI and database column names are German (umlauts included). No build system, no tests. Deployed by uploading the directory to a shared-hosting account.

See `README.md` for the deployment guide intended for non-technical school admins.

## Running locally

```
php -c php.ini -S localhost:8000
```

Apache features (`.htaccess` deny-rules, `mod_authz_core`) are skipped by the built-in server, so the security boundaries can only be verified on a real Apache. PHP 8.0+ is required (uses `match`, `str_starts_with`, named arguments).

`config.php` is **optional** (copy `config.example.php` only if you need to override `db_path`). On first request, `db.php` auto-creates `data/curricula.db` with the schema, then `setup.php` runs the install wizard for school name and passwords.

## Architecture

Single SQLite database `data/curricula.db` with two tables:

- `curricula` — versioned snapshots of curriculum entries.
- `settings` — key/value store for `school_name`, the three passwords, `jahrgang_min`/`jahrgang_max`, and the per-school `faecher` list. **Configuration that used to live in `config.php` lives here now.** Only `db_path` is still file-config.

Every row in `curricula` is a **versioned snapshot** of one curriculum entry. `schic_id` groups versions of the same curriculum; `id` is the per-row autoincrement.

**"Latest version" is always `MAX(id)` per `schic_id`.** This is consistent across `ajax_suche.php`, `detail.php`, `alle_versionen.php`. Don't introduce a `version DESC` ordering — version strings sort lexically and would disagree.

### Module layout

- `db.php` — `schics_config()` (loads optional `config.php` once), `schics_db()` (PDO connection + schema bootstrap + idempotent migrations), `schics_setting()` / `schics_set_setting()` (key/value access to the `settings` table), `schics_setup_done()`, `schics_school_name()`, `schics_faecher()`, `schics_default_faecher()`, `schics_jahrgang_range()`, `schics_content_fields()` (whitelist for column names that come from user input), `schics_quote_ident()`. Every page that touches the database goes through `schics_db()` — no inline PDO construction.
- `auth.php` — three-level access model. Levels are `SCHICS_LEVEL_NONE < READ < EDIT < ADMIN` and **hierarchical**: a higher password also grants lower-level access. `schics_require_level($n)` is the gatekeeper at the top of every page; on AJAX/JSON requests it returns 401 instead of redirecting to `login.php`. Read access is open when `read_password` is `''` in settings. If the setup wizard hasn't been completed yet, `schics_require_level()` redirects to `setup.php`. Also exposes `schics_flash()` / `schics_consume_flash()` for one-shot status messages across redirects.
- `helpers.php` — small UI helpers shared across read pages: `schics_status_class()`, `schics_status_badge()`, `schics_field()` (label + value, optionally multi-line via `nl2br`).
- `nav.php` — shared navigation. Edit/Admin links appear only for matching levels. Include from inside `<body>` after the `require_level` call.
- `setup.php` — first-run wizard. Self-redirects to `einstellungen.php` once setup is done.
- `einstellungen.php` — admin page for school name, passwords, year range, and `Fächer` list.

### Page → required level

| Page | Level |
|---|---|
| `index.php`, `ajax_suche.php`, `detail.php`, `alle_versionen.php`, `dashboard.php`, `dashboard_data.php` | READ |
| `admin.php` (new SchiC entry), `neue_version.php`, `sortieren.php`, `update_reihenfolge.php` | EDIT |
| `einstellungen.php`, `update_status.php` | ADMIN |

Note: `admin.php` is the "new SchiC entry" form (EDIT-level), despite the filename. The genuine admin surface is `einstellungen.php`.

### Conventions

- Output is always rendered through `htmlspecialchars()` and (for multi-line content) `nl2br(htmlspecialchars())`. Preserve this when adding rendering.
- Database column names contain umlauts (`fächerverbindung`, `heterogenität`, `übergreifende_themen`, `änderungskommentar`). Quote them in raw SQL with `schics_quote_ident()` or hand-quote with `"…"` in SQLite. Named PDO parameters with umlaut keys work fine.
- The `Fächer` list and the year range are per-school, stored in `settings`. Always go through `schics_faecher()` and `schics_jahrgang_range()` — never hardcode either, and never re-declare the `Fächer` list inline.
- Content-column names that come from `$_GET`/`$_POST` (only `dashboard_data.php`) MUST be validated against `schics_content_fields()` before being interpolated into SQL.
- `bearbeitet_von` is **optional**. Forms must not mark it `required`; views must guard with `if (!empty(...))` before rendering.
- Write endpoints validate IDs as integers (`(int)` cast or `filter_var(... FILTER_VALIDATE_INT)`).
- `admin.php` (new entry) computes `nextSchicId = MAX(schic_id) + 1` inside an explicit `beginTransaction()` to avoid the read-then-insert race.
- The schema migration (`schics_db_migrate()` in `db.php`) runs on every connection — keep it idempotent. Currently it drops obsolete `gremium_*` columns and normalises stale `status` values to `Entwurf`.

### Things not to do

- Don't add a separate `db_*.php` connection helper or hardcode credentials anywhere — `schics_db()` is the only way.
- Don't `session_start()` directly; use `schics_session_start()` so cookie params stay consistent.
- Don't include `nav.php` before calling `schics_require_level()` — the redirect uses `header()` and won't work after output.
- Don't store passwords in `config.php`. They live in the `settings` table and are managed via the setup wizard / `einstellungen.php`. Plain strings + `hash_equals()` is the deliberate choice for "edit one form and save" simplicity — these are shared deployment secrets, not user accounts.
- Don't link `sortieren.php` or `admin.php` (new entry) from non-edit users' nav, and don't expose `einstellungen.php` to editors. `nav.php` already gates these — keep it that way.
