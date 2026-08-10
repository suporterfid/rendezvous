# Functional and Technical Requirements — Rendezvous MVP

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

## 1. Environment & Infrastructure Requirements

### REQ-ENV-001: Docker-Only Development Loop
- **WHEN** a developer clones the repository or executes `./scripts/rv.sh up` (or `.\scripts\rv.ps1 up`),
- **THEN** the system MUST start all required containers (PHP 8.2-FPM, Nginx, MySQL 8, Mailpit, Node 20 Vite dev server) using Docker Compose without requiring host-installed PHP, Composer, Node, npm, or MySQL/SQLite.

### REQ-ENV-002: Shared-Hosting Runtime Target
- **WHEN** the application is deployed to production,
- **THEN** the system MUST execute on standard PHP 8.2+ with MySQL 8 or SQLite on Apache/LiteSpeed web hosting via 1-minute cron, without requiring persistent background workers, daemons, Node.js, or Redis.

---

## 2. Email-Based Meeting Invite Ingestion Requirements

### REQ-ING-001: IMAP Polling & Mailbox Config
- **WHEN** the cron command `php artisan calendar:ingest-mailboxes` is executed,
- **THEN** the system MUST poll configured tenant IMAP mailboxes, fetch unprocessed messages, and acquire an atomic claim token with expiration to prevent duplicate processing by overlapping cron jobs.

### REQ-ING-002: iCalendar MIME Parsing & Quarantine
- **WHEN** an email message is processed during mailbox polling,
- **THEN** the system MUST parse `text/calendar` MIME parts (`METHOD:REQUEST`, `CANCEL`, `REPLY`, `REFRESH`) and `.ics` attachments using `sabre/vobject`. If parsing fails, the message MUST be moved to a Quarantine folder/state with the error reason recorded.

### REQ-ING-003: UID / SEQUENCE Reconciliation & Idempotency
- **WHEN** an incoming event is processed,
- **THEN** the system MUST compare its `UID` and `SEQUENCE` against existing records for the tenant. If the incoming `SEQUENCE` is less than or equal to the stored `SEQUENCE`, the update MUST be ignored idempotently. If `METHOD:CANCEL` is received, the event MUST be marked as cancelled without deleting its historical record.

### REQ-ING-004: Deduplication Across Mailboxes
- **WHEN** the same invite is forwarded to multiple mailboxes within the same tenant,
- **THEN** the system MUST deduplicate the event using the composite key `(tenant_id, UID, ORGANIZER)`.

---

## 3. Calendar Mash-Up & Feed Export Requirements

### REQ-FED-001: Subscribed ICS & Read-Only CalDAV Feeds
- **WHEN** external ICS feed URLs or CalDAV sources are configured for a calendar,
- **THEN** the system MUST periodically refresh them via cron, applying strict SSRF validation (blocking private/loopback/link-local IPs and revalidating redirects).

### REQ-FED-002: Aggregated Feed Export (`webcal://`)
- **WHEN** a client requests an aggregated calendar feed via `webcal://` (HTTPS with token authentication),
- **THEN** the `FeedExport` service MUST generate an iCalendar response containing exactly one `VCALENDAR` block, omit any top-level `METHOD` property, include `X-WR-CALNAME` and `X-WR-TIMEZONE`, and set `Content-Type: text/calendar; charset=utf-8`.

---

## 4. Multi-Tenancy, Auth & UI Requirements

### REQ-TEN-001: Tenant Isolation
- **WHEN** any database query or API request is executed,
- **THEN** all data access MUST be explicitly scoped by `tenant_id`. Cross-tenant identifier substitution MUST be rejected with HTTP 403 / 404.

### REQ-UI-001: Responsive Calendar UI & i18n
- **WHEN** a user accesses the web application,
- **THEN** the system MUST render a responsive calendar (month, week, day, agenda views) supporting `en` and `pt-BR` languages, using the portfolio visual identity tokens with a 1200px measure for data views.
