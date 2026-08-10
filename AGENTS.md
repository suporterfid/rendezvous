# Agent Directives — Rendezvous 📅

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

## 🤖 AI Agent & Developer Execution Rules

When working on **Rendezvous**, all AI coding agents (Claude, Antigravity, Copilot, Codex) and human contributors MUST adhere to the following directives:

### 1. Environment & Execution Mode
- **100% Docker-Only Development:** Do NOT attempt to run host-level `php`, `composer`, `npm`, or `mysql` commands directly on the host machine.
- All testing, database migrations, asset builds, and Artisan commands MUST execute inside Docker containers using the cross-platform CLI scripts:
  - **Windows (PowerShell):** `.\scripts\rv.ps1 <command>`
  - **Linux / macOS (Bash):** `./scripts/rv.sh <command>`

### 2. Mandatory Testing Verification
- Before declaring any task complete or committing code changes, run the automated test suite inside Docker:
  ```powershell
  .\scripts\rv.ps1 test
  ```
- All tests in `tests/Unit` and `tests/Feature` must pass with zero failures.

### 3. Architecture & Domain Integrity
- **Modular Monolith Layering:** Keep business logic inside pure domain services (`app/Domain/Calendar/Services/`). Controllers and Artisan commands must only orchestrate requests.
- **Tenant Data Scoping:** Every database query MUST be scoped by `tenant_id`. Never bypass tenant scoping or hardcode tenant identifiers.
- **iCalendar Engine (`sabre/vobject`):** Use `sabre/vobject` for all iCalendar parsing, recurrence expansion, and feed generation. Do not write regex-based ICS parsers.
- **SSRF Protection:** Outbound HTTP fetches for external ICS or CalDAV feeds MUST use `SsrfGuardService` to prevent requests to loopback (`127.0.0.1`), private subnets, or cloud metadata endpoints (`169.254.169.254`).
- **Secret Redaction:** IMAP credentials and API tokens stored in the `secrets` table MUST be encrypted via `SecretEncryptionService` (AES-256) and redacted from all log tracebacks.

### 4. UI & Internationalization (i18n)
- **Shared Visual Identity:** The frontend (`frontend/src/`) MUST conform to the IROH visual identity specification using semantic CSS tokens (`:root`, `[data-theme='light']`, `[data-theme='dark']`).
- **Data View Measure:** The calendar grid component MUST use a `1200px` container measure.
- **Dual Locales:** All user-facing strings MUST be localized in `en` and `pt-BR` via Vue I18n (`frontend/src/i18n/locales/{en,pt-BR}/*.json`). Never hardcode text strings in `.vue` files.

### 5. Deployment & Licensing
- Production runtime is targeted at standard shared web hosting (PHP 8.2+, MySQL/SQLite, Apache/LiteSpeed, 1-minute cron). No Node.js, Docker, or background daemons are required in production.
- Do NOT introduce CI/CD remote pipelines (e.g. GitHub Actions workflows for deployment) unless explicitly requested. Local packaging via `.\scripts\rv.ps1 release` is mandatory.
- Code is released under the **MIT License**.
