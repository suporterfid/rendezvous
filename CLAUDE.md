# Claude Directives & Quick Reference — Rendezvous 📅

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

## ⚡ Quick Reference Commands

### Windows (PowerShell)
```powershell
.\scripts\rv.ps1 up          # Start all Docker containers
.\scripts\rv.ps1 down        # Stop containers
.\scripts\rv.ps1 test        # Run PHPUnit test suite
.\scripts\rv.ps1 artisan ... # Run Artisan command inside PHP container
.\scripts\rv.ps1 release     # Build release ZIP for Hostinger shared hosting
```

### Linux / macOS (Bash)
```bash
./scripts/rv.sh up          # Start all Docker containers
./scripts/rv.sh down        # Stop containers
./scripts/rv.sh test        # Run PHPUnit test suite
./scripts/rv.sh artisan ... # Run Artisan command inside PHP container
./scripts/rv.sh release     # Build release ZIP for Hostinger shared hosting
```

---

## 🎯 Architecture Summary

- **Backend:** Laravel 12 (PHP 8.2-FPM), `sabre/vobject` (iCalendar engine), `webklex/php-imap` (IMAP client).
- **Frontend:** Vue 3 (Composition API), TypeScript, Vite, Pinia, Vue I18n (`en`, `pt-BR`), Tailwind CSS.
- **Database:** Dual PDO support — **SQLite** (local zero-config) & **MySQL 8** (multi-tenant production).
- **Local Dev Stack:** Docker Compose (`compose.yaml`) with `web` (Nginx), `php` (PHP 8.2-FPM), `frontend` (Vite), `db` (MySQL 8), `mailpit` (IMAP/SMTP test double).
- **Production Target:** Hostinger Shared Hosting (PHP 8.2+, MySQL/SQLite, LiteSpeed/Apache, 1-minute system cron).

---

## 🛡️ Core Rules & Policies

1. **Strict Tenant Scoping:** All database operations must include `tenant_id` scoping.
2. **SSRF Guard Protection:** External feed fetches must be checked against `SsrfGuardService`.
3. **Secret Encryption:** Store secrets encrypted via `SecretEncryptionService` and redact in logs.
4. **Feed Conformance:** Outbound `webcal://` feeds must emit a single `VCALENDAR` block without top-level `METHOD`.
5. **No Remote CI/CD:** Local build script `rv.ps1 release` / `rv.sh release` produces deployment ZIP.
