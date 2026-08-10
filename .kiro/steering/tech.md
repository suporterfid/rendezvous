# Technical Steering — Rendezvous

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

## Tech Stack
- **Backend Framework:** Laravel 12 (PHP 8.2+)
- **Frontend Framework:** Vue 3 (Composition API, `<script setup>`), TypeScript, Vite, Pinia, Vue I18n (`en` and `pt-BR`), Tailwind CSS.
- **Database Support:** Dual PDO support — **SQLite** for zero-config local development and single-tenant testing; **MySQL 8** / MariaDB for multi-tenant production.
- **iCalendar Engine:** `sabre/vobject` (parsing, recurrence expansion, timezone normalization, feed generation).
- **IMAP Client:** `webklex/php-imap` (mailbox polling, folder management, MIME attachment extraction).
- **Development Environment:** 100% Docker-only (`compose.yaml` with PHP-FPM 8.2, Nginx, MySQL 8, Mailpit, Node 20). No host PHP, Composer, Node, or MySQL required.
- **Production Environment:** Shared web hosting (e.g. Hostinger), LiteSpeed/Apache, PHP 8.2+, MySQL/SQLite, 1-minute cron. No persistent background workers or Redis required.

## Key Architectural Rules
1. **Modular Monolith Layering:**
   - `Interface`: HTTP Controllers, Console Commands, Request Validation.
   - `Application`: Use cases, transaction orchestration, policy checks.
   - `Domain`: Pure unit-tested domain models, iCalendar parsing, `UID`/`SEQUENCE` reconciliation, `RRULE` expansion, timezone normalization, feed conformance rules. No framework/DB/HTTP coupling.
   - `Infrastructure`: PDO repositories, IMAP client, HTTP client (SSRF guarded), encryption, adapters.
2. **Tenant Scoping:** Every tenant table MUST include `tenant_id`. Queries and repository boundaries MUST scope by `tenant_id`.
3. **Secret Security:** IMAP and CalDAV credentials MUST be encrypted at rest (`secrets` table) and redacted from API responses and audit trails.
4. **SSRF Guard:** Outbound ICS and CalDAV feed fetches MUST validate URLs/IPs against private/loopback/link-local blocks and revalidate redirects.
5. **Feed Conformance:** The `FeedExport` service MUST emit valid iCalendar with exactly one `VCALENDAR` block, omit top-level `METHOD`, include `X-WR-CALNAME` and `X-WR-TIMEZONE`, and set `Content-Type: text/calendar; charset=utf-8`.
