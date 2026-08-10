# Implementation Tasks — Rendezvous MVP

## Phase 0: Foundation & Docker Stack

- [x] **Task 0.1: Docker Compose & Infrastructure Setup**
  - _Boundary:_ `compose.yaml`, `docker/php/Dockerfile`, `docker/nginx/default.conf`, `scripts/rv.sh`, `scripts/rv.ps1`
  - _Depends:_ None
  - _Details:_ Configure PHP 8.2-FPM with `pdo_sqlite`, `pdo_mysql`, `imap`, `mbstring`, `zip` extensions. Add Nginx, MySQL 8, Mailpit (IMAP/SMTP test double), and Node 20 Vite dev server. Implement `./scripts/rv.sh` and `.\scripts\rv.ps1` helper commands.

- [x] **Task 0.2: Laravel 12 Application & Vue 3 + i18n Scaffolding**
  - _Boundary:_ `app/`, `frontend/`, `config/`, `package.json`, `composer.json`
  - _Depends:_ Task 0.1
  - _Details:_ Initialize Laravel 12 structure, Vue 3 + TypeScript + Vite + Pinia + Tailwind setup in `frontend/`, and Vue I18n with `en` and `pt-BR` locale files.

---

## Phase 1: Core Domain & Data Model

- [x] **Task 1.1: Database Migrations & Models (Tenant Scoped)**
  - _Boundary:_ `database/migrations/`, `app/Domain/` models (`Tenant`, `Calendar`, `CalendarSource`, `MailboxIngestConfig`, `Event`, `Secret`)
  - _Depends:_ Task 0.2
  - _Details:_ Implement database migrations with dual PDO support (SQLite/MySQL) and strict `tenant_id` foreign keys and indices.

- [x] **Task 1.2: iCalendar Parsing & UID/SEQUENCE Reconciliation Engine**
  - _Boundary:_ `app/Domain/Calendar/Services/` (`IcalendarParserService`, `EventReconciliationService`)
  - _Depends:_ Task 1.1
  - _Details:_ Pure unit-tested domain service using `sabre/vobject` for parsing `.ics` / `text/calendar` MIME parts, expanding `RRULE`, handling `METHOD:CANCEL` / `METHOD:REPLY`, and enforcing `UID` + `SEQUENCE` idempotency.

- [x] **Task 1.3: Secret Encryption & SSRF Guard Services**
  - _Boundary:_ `app/Infrastructure/Security/` (`SecretEncryptionService`, `SsrfGuardService`)
  - _Depends:_ Task 1.1
  - _Details:_ Encrypt IMAP/CalDAV credentials at rest, redact secrets in logs, and validate outbound feed URLs/IPs against private/loopback/link-local ranges.

---

## Phase 2: Ingestion & Feed Refresh Commands

- [x] **Task 2.1: Mailbox Ingestion Command (`calendar:ingest-mailboxes`)**
  - _Boundary:_ `app/Console/Commands/IngestMailboxesCommand.php`, `app/Infrastructure/Imap/`
  - _Depends:_ Task 1.2, Task 1.3
  - _Details:_ Implement IMAP polling with `webklex/php-imap`, atomic mailbox claiming with expiration, MIME attachment extraction, message status flagging (`\Seen`), and quarantine logging for malformed emails. Test against Mailpit container.

- [x] **Task 2.2: ICS Feed Subscription & Refresh Command (`calendar:refresh-feeds`)**
  - _Boundary:_ `app/Console/Commands/RefreshFeedsCommand.php`, `app/Infrastructure/Http/`
  - _Depends:_ Task 1.3, Task 2.1
  - _Details:_ Implement cron-driven feed polling with SSRF validation, ETag/Last-Modified caching, and event sync.

---

## Phase 3: Feed Export & Outlook/Google Conformance

- [x] **Task 3.1: Aggregated Feed Export Service (`FeedExportService`)**
  - _Boundary:_ `app/Domain/Calendar/Services/FeedExportService.php`, `app/Http/Controllers/Api/FeedExportController.php`
  - _Depends:_ Task 1.2, Task 2.2
  - _Details:_ Implement `webcal://` / HTTPS feed export generating a single `VCALENDAR` block, omitting top-level `METHOD`, setting `X-WR-CALNAME` and `X-WR-TIMEZONE`, validating every `VEVENT` item, and serving with token authentication.

---

## Phase 4: REST API & Responsive Calendar UI

- [x] **Task 4.1: REST API Endpoints (`/api/v1`)**
  - _Boundary:_ `app/Http/Controllers/Api/`, `routes/api.php`
  - _Depends:_ Task 3.1
  - _Details:_ Implement CRUD endpoints for tenants, calendars, sources, mailbox configs, events, and health check (`/api/v1/platform/health`).

- [x] **Task 4.2: Responsive Calendar UI (Vue 3 + i18n)**
  - _Boundary:_ `frontend/src/` (`CalendarGrid.vue`, `SourceManager.vue`, `EventModal.vue`)
  - _Depends:_ Task 4.1
  - _Details:_ Build the responsive UI supporting month, week, day, agenda views, 1200px measure for grid views, source-type badges, and `en`/`pt-BR` language toggle.

---

## Phase 5: Release Packaging & Verification

- [x] **Task 5.1: Shared Hosting Release Script (`./scripts/rv.sh release`)**
  - _Boundary:_ `scripts/rv.sh`, `scripts/rv.ps1`, `docs/deployment.md`
  - _Depends:_ Task 4.2
  - _Details:_ Implement containerized build and packaging script generating `dist/rendezvous-release.zip` and `SHA256SUMS` with `.htaccess` for Hostinger shared hosting deployment.
