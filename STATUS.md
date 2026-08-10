# Status — Rendezvous 📅

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

## Current Project Status: `1.0.0` (Production Ready / MVP Complete)

Rendezvous is fully implemented, containerized, and tested. The platform provides end-to-end multi-tenant calendar mash-up, IMAP email invite parsing via `sabre/vobject`, `UID` + `SEQUENCE` reconciliation, aggregated `webcal://` feed generation conformant with Outlook 365 and Google Workspace, and a responsive Vue 3 UI.

### Completed Milestones

- ✅ **Docker Compose Local DX:** 100% containerized stack with PHP 8.2-FPM, Nginx, MySQL 8, Mailpit (IMAP/SMTP test double), and Node 20 Vite dev server (`compose.yaml`).
- ✅ **Cross-Platform Utility CLI:** Parity across Windows PowerShell (`.\scripts\rv.ps1`) and Linux/macOS Bash (`./scripts/rv.sh`) for all container commands (`up`, `down`, `test`, `artisan`, `release`).
- ✅ **Database & Tenant Models:** Dual PDO support (SQLite for personal local use / MySQL 8 for multi-tenant production) with migrations (`2026_08_10_000001_create_rendezvous_tables.php`).
- ✅ **IMAP Ingestion & Sabre/Vobject Parser:** Mailbox polling command `calendar:ingest-mailboxes` with atomic claim tokens, MIME attachment extraction, quarantine handling, and `UID`/`SEQUENCE` idempotency.
- ✅ **Feed Export Conformance:** `FeedExportService` serving single `VCALENDAR` blocks with `X-WR-CALNAME` and `X-WR-TIMEZONE` headers, passing Outlook 365 & Google Workspace verification protocols.
- ✅ **Security & SSRF Guard:** `SecretEncryptionService` (AES-256 for IMAP credentials), log secret redaction, and `SsrfGuardService` blocking loopback, private, and AWS metadata IP ranges.
- ✅ **Vue 3 SPA & Dual i18n:** Responsive Tailwind CSS frontend supporting Month, Week, Day, and Agenda views with 1200px data view measure and full English (`en`) / Portuguese (`pt-BR`) locales.
- ✅ **Shared Hosting Release Pipeline:** Containerized release command packaging `dist_release/rendezvous-release.zip` with `.htaccess` ready for Hostinger deployment.

### System Verification Matrix

| Component | Automated Test File | Status |
| :--- | :--- | :--- |
| **Tenant Data Isolation** | `tests/Feature/Security/TenantIsolationTest.php` | ✅ PASS |
| **SSRF Protection Guard** | `tests/Feature/Security/SsrfProtectionTest.php` | ✅ PASS |
| **Secret Redaction** | `tests/Feature/Security/SecretRedactionTest.php` | ✅ PASS |
| **Feed Export Conformance** | `tests/Unit/Domain/Calendar/FeedExportServiceTest.php` | ✅ PASS |
| **Reconciliation Engine** | `tests/Unit/Domain/Calendar/EventReconciliationServiceTest.php` | ✅ PASS |
