# Changelog

All notable changes to the **Rendezvous** project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-08-10

### Added
- **Docker Compose Stack:** Containerized environment with PHP 8.2-FPM (`pdo_sqlite`, `pdo_mysql`, `imap`), Nginx, MySQL 8, Mailpit (IMAP/SMTP test double), and Node 20 Vite dev server (`compose.yaml`).
- **Utility CLI Scripts:** Helper scripts `.\scripts\rv.ps1` (PowerShell) and `./scripts/rv.sh` (Bash) supporting `up`, `down`, `test`, `artisan`, `composer`, `npm`, and `release`.
- **Database Migrations & Tenant Models:** Schema migration `2026_08_10_000001_create_rendezvous_tables.php` with dual PDO support (SQLite/MySQL) and Eloquent models (`Tenant`, `Calendar`, `CalendarSource`, `MailboxIngestConfig`, `Event`, `Secret`).
- **iCalendar Engine & Reconciliation:** `IcalendarParserService` using `sabre/vobject` for `.ics` / `text/calendar` MIME parsing and `EventReconciliationService` for `UID` + `SEQUENCE` idempotency and `METHOD:CANCEL` / `METHOD:REPLY` status handling.
- **Cron Commands:** `calendar:ingest-mailboxes` with atomic claim tokens for IMAP polling and `calendar:refresh-feeds` for external ICS feed refresh with `ETag` / `If-None-Match` caching.
- **Outlook & Google Feed Export:** `FeedExportService` exporting `webcal://` feeds in a single `VCALENDAR` block with `X-WR-CALNAME` and `X-WR-TIMEZONE` headers.
- **Security & SSRF Guard:** `SecretEncryptionService` for AES-256 encryption at rest with log redaction, and `SsrfGuardService` blocking private IP ranges (`127.0.0.1`, `10.x`, `192.168.x`, `169.254.169.254`).
- **Vue 3 SPA & i18n:** Responsive Tailwind CSS frontend with 1200px calendar grid measure, supporting Month, Week, Day, Agenda views and dual English (`en`) / Portuguese (`pt-BR`) locales.
- **OAuth 2.0 Ingestion:** `OauthIngestionService` supporting direct API calendar ingestion from Microsoft Graph API and Google Calendar API endpoints (Issue #1).
- **Bidirectional CalDAV Endpoint:** `CaldavServerService` and `CaldavServerController` exposing CalDAV `PUT`, `DELETE`, `PROPFIND`, and `REPORT` methods for native desktop/mobile clients (Issue #2).
- **Portfolio Integration Seams:** Optional integration seams with GrandpaSSOn (SSO / PKCE - Issue #3), TaskConnect (Auxiliary HMAC Webhooks - Issue #4), and Jotter (Markdown Meeting Notes via MCP JSON-RPC - Issue #5).
- **Outbound Webhooks:** Real-time push notification dispatcher `WebhookDispatchService` signed with HMAC SHA-256 (`X-Rendezvous-Signature`) for event changes (Issue #8).
- **Automated Test Suite & Conformance Docs:** Unit and feature test suite covering feed export, reconciliations, security, and manual subscription protocol (`docs/calendar-compatibility-verification.md` & `docs/security-audit-checklist.md`).
