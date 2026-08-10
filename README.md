# Rendezvous 📅

> **Open-Source Multi-Tenant Calendar Mash-up Platform**
> Aggregates events from multiple sources (subscribed ICS feeds, read-only CalDAV, OAuth 2.0 Graph APIs, and forwarded email meeting invites via IMAP) into a single unified calendar per tenant and republishes as a subscribable `webcal://` feed.

---

## 🌟 Key Features

- **📧 Forwarded Email Meeting Invite Ingestion:** Polls IMAP mailboxes, extracts `.ics` / `text/calendar` attachments via `sabre/vobject`, and reconciles meeting updates.
- **🔄 Event Reconciliation & Idempotency:** Deduplicates events based on `UID` + `SEQUENCE` numbers. Handles `METHOD:CANCEL` without losing history and updates participant responses on `METHOD:REPLY`.
- **🌐 Outlook 365 & Google Workspace Conformance:** Exports clean `webcal://` feeds formatted as a single `VCALENDAR` block with `X-WR-CALNAME` and `X-WR-TIMEZONE` headers.
- **🛡️ Multi-Tenant Security & SSRF Protection:** Strict `tenant_id` database isolation, AES-256 secret encryption at rest, automatic log redaction, and SSRF guard protection blocking private IP ranges (`127.0.0.1`, `10.x`, `192.168.x`, `169.254.169.254`).
- **📱 Responsive UI & Dual i18n:** Modern Vue 3 + Tailwind CSS frontend supporting Month, Week, Day, and Agenda views with full English (`en`) and Portuguese (`pt-BR`) internationalization.
- **🔌 Portfolio Seams:** Optional integration seams with GrandpaSSOn (SSO / PKCE), TaskConnect (Auxiliary HMAC Webhooks), and Jotter (Markdown Meeting Notes via MCP JSON-RPC).
- **🚀 100% Dockerized Local Environment:** Complete local dev loop with PHP 8.2-FPM, Nginx, MySQL 8, Mailpit (IMAP/SMTP test double), and Node 20 Vite dev server.

---

## 🛠️ Technology Stack

| Tier | Component | Technology |
| :--- | :--- | :--- |
| **Backend** | Framework & Engine | PHP 8.2-FPM, Laravel 12, `sabre/vobject`, `webklex/php-imap` |
| **Frontend** | UI & Locales | Vue 3 (Composition API), Vite, TypeScript, Tailwind CSS, Vue I18n |
| **Database** | Dual PDO | SQLite (local zero-config) & MySQL 8 (production) |
| **Containers**| Local Stack | Docker & Docker Compose V2 (`compose.yaml`) |
| **Hosting Target** | Shared Web Hosting | Hostinger Shared Hosting (FTP/SFTP/rsync via local build script) |

---

## 🚀 Quick Start (Local Docker Setup)

### Prerequisites
- [Docker Desktop](https://www.docker.com/) (with Docker Compose V2)
- Git & PowerShell / Bash

### 1. Clone & Start Containers
```bash
git clone https://github.com/suporterfid/rendezvous.git
cd rendezvous

# Start local Docker environment (Windows PowerShell)
.\scripts\rv.ps1 up

# Or in Linux/macOS:
./scripts/rv.sh up
```

### 2. Available Endpoints
- **Web Application:** `http://localhost:8080`
- **Health Check API:** `http://localhost:8080/api/v1/platform/health`
- **Mailpit Web UI (IMAP/SMTP Test Double):** `http://localhost:8025`

---

## 💻 Utility CLI Commands

Use the helper script `.\scripts\rv.ps1` (PowerShell) or `./scripts/rv.sh` (Bash) to manage the containerized environment:

```powershell
.\scripts\rv.ps1 up          # Start all Docker containers
.\scripts\rv.ps1 down        # Stop containers
.\scripts\rv.ps1 test        # Run PHPUnit test suite inside container
.\scripts\rv.ps1 artisan ... # Run Artisan command inside PHP container
.\scripts\rv.ps1 release     # Package release ZIP for Hostinger shared hosting
```

---

## 🔐 Security & Audit

Rendezvous implements strict security controls audited against OWASP standards:
- **Tenant Isolation:** All database queries are explicitly scoped by `tenant_id`.
- **SSRF Guard:** Outbound ICS feed URLs are validated against private, loopback, and cloud metadata IP ranges (`169.254.169.254`).
- **Secret Encryption:** Credentials stored encrypted in repouso (`secrets` table) and redacted in log tracebacks.

Run security feature tests:
```powershell
.\scripts\rv.ps1 test --filter=TenantIsolationTest
.\scripts\rv.ps1 test --filter=SsrfProtectionTest
```

---

## 📦 Shared Hosting Deployment (Hostinger Target)

To build and package Rendezvous for Hostinger shared hosting deployment without using remote CI/CD pipelines:

```powershell
.\scripts\rv.ps1 release
```
This generates `dist_release/rendezvous-release.zip` and `SHA256SUMS` containing compiled frontend assets, optimized PHP backend, and `.htaccess` routing rules ready for upload to `/public_html/`.

---

## 📜 License

Rendezvous is open-source software licensed under the **MIT License**.