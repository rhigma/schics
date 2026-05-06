# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

SchiCs ("Schulinterne Curricula") — small PHP webapp for managing versioned school-internal curriculum entries. UI and database column names are German (umlauts included). No build system, no tests, no git. Deployed by uploading the directory to a shared-hosting account.

See `README.md` for the deployment guide intended for non-technical school admins.

## Running locally

```
php -S localhost:8000
```

Apache features (`.htaccess` deny-rules, `mod_authz_core`) are skipped by the built-in server, so the security boundaries can only be verified on a real Apache. PHP 8.0+ is required (uses `match`, `str_starts_with`, named arguments).

`config.php` must exist (copy `config.example.php`). On first request, `db.php` auto-creates `data/curricula.db` with the schema.

## Architecture

Single SQLite table `curricula` in `data/curricula.db`. Every row is a **versioned snapshot** of one curriculum entry. `schic_id` groups versions of the same curriculum; `id` is the per-row autoincrement.

**"Latest version" is always `MAX(id)` per `schic_id`.** This is consistent across `ajax_suche.php`, `detail.php`, `alle_versionen.php`. Don't introduce a `version DESC` ordering — version strings sort lexically and would disagree.

### Module layout

- `db.php` — `schics_config()` (loads `config.php` once), `schics_db()` (PDO connection + schema bootstrap on first run), `schics_content_fields()` (whitelist for column names that come from user input), `schics_faecher()`, `schics_quote_ident()`. Every page that touches the database goes through `schics_db()` — no inline PDO construction.
- `auth.php` — three-level access model. Levels are `SCHICS_LEVEL_NONE < READ < EDIT < ADMIN` and **hierarchical**: a higher password also grants lower-level access. `schics_require_level($n)` is the gatekeeper at the top of every page; on AJAX/JSON requests it returns 401 instead of redirecting to `login.php`. Read access is open when `read_password` is `''` in config.
- `nav.php` — shared navigation. Edit/Admin links appear only for matching levels. Include from inside `<body>` after the `require_level` call.

### Page → required level

| Page | Level |
|---|---|
| `index.php`, `ajax_suche.php`, `detail.php`, `alle_versionen.php`, `dashboard.php`, `dashboard_data.php` | READ |
| `neue_version.php`, `sortieren.php`, `update_reihenfolge.php` | EDIT |
| `admin.php` | ADMIN |

### Conventions

- Output is always rendered through `htmlspecialchars()` and (for multi-line content) `nl2br(htmlspecialchars())`. Preserve this when adding rendering.
- Database column names contain umlauts (`fächerverbindung`, `heterogenität`, `übergreifende_themen`, `änderungskommentar`). Quote them in raw SQL with `schics_quote_ident()` or hand-quote with `"…"` in SQLite. Named PDO parameters with umlaut keys work fine.
- The `Fächer` list is centralised in `schics_faecher()`. Do not re-declare it inline.
- Content-column names that come from `$_GET`/`$_POST` (only `dashboard_data.php`) MUST be validated against `schics_content_fields()` before being interpolated into SQL.
- `bearbeitet_von` is **optional**. Forms must not mark it `required`; views must guard with `if (!empty(...))` before rendering.
- Write endpoints validate IDs as integers (`(int)` cast or `filter_var(... FILTER_VALIDATE_INT)`).
- `admin.php` computes `nextSchicId = MAX(schic_id) + 1` inside an explicit `beginTransaction()` to avoid the read-then-insert race.

### Things not to do

- Don't add a separate `db_*.php` connection helper or hardcode credentials anywhere — `schics_db()` is the only way.
- Don't `session_start()` directly; use `schics_session_start()` so cookie params stay consistent.
- Don't include `nav.php` before calling `schics_require_level()` — the redirect uses `header()` and won't work after output.
- Don't store passwords hashed in `config.php`. They're shared deployment secrets, not user accounts; plain strings + `hash_equals()` is the deliberate choice for "edit one file and deploy" simplicity.
- Don't link `sortieren.php` from non-edit users' nav, and don't expose admin links to editors. `nav.php` already gates these — keep it that way.
