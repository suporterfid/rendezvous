🇧🇷 [Leia em Português](README.pt-BR.md) | 🇺🇸 [English](README.md) (this page)

# Rendezvous 📅

> **Open-Source Multi-Tenant Calendar Mash-up Platform**  
> Aggregates events from multiple sources (subscribed ICS feeds, read-only CalDAV, OAuth 2.0 Graph APIs, and forwarded email meeting invites via IMAP) into a single unified calendar per tenant and republishes as a subscribable `webcal://` feed.

---

> 🍵 **Part of the IROH Initiative** (*It Runs On Hostinger*)  
> This project is open-source, self-hosted software built for traditional shared hosting.  
> Learn more at the main hub: [suporterfid/iroh](https://github.com/suporterfid/iroh) | [Manifesto](https://github.com/suporterfid/iroh/blob/main/MANIFESTO.md) | [Projects Index](https://github.com/suporterfid/iroh/blob/main/PROJECTS.md) | [Branding Spec](https://github.com/suporterfid/iroh/blob/main/BRANDING.md)

---

## 🌟 Overview

**Rendezvous** is a self-hosted, lightweight calendar consolidation platform designed to run smoothly on traditional shared web hosting (such as Hostinger, cPanel, Apache/Nginx, and PHP 8.2+). It solves a widespread corporate calendar problem: when an organization restricts native calendar federation or delegation, users can simply forward meeting invite emails to a dedicated inbox. Rendezvous parses `text/calendar` MIME attachments via IMAP, normalizes the events, merges them with external feeds, and outputs a clean, subscribable `webcal://` feed.

As a core application in the **IROH Initiative** ("It Runs On Hostinger"), Rendezvous embraces the philosophy that sophisticated software does not require expensive VPS clusters, complex Kubernetes setups, or memory-heavy background daemons. With SQLite or MySQL, a 1-minute cron job, and standard PHP, Rendezvous provides enterprise-grade calendar aggregation with minimal operational overhead and zero monthly infrastructure costs.

---

## 🍵 Part of the IROH Initiative

Rendezvous is part of the **IROH Initiative** — an ecosystem of self-hosted, lightweight tools tailored for traditional shared web hosting environments. 

- **Central Meta-Repository:** [suporterfid/iroh](https://github.com/suporterfid/iroh)
- **Manifesto:** Read our core philosophy at [MANIFESTO.md](https://github.com/suporterfid/iroh/blob/main/MANIFESTO.md).
- **Project Index:** Discover all sibling projects at [PROJECTS.md](https://github.com/suporterfid/iroh/blob/main/PROJECTS.md).
- **Branding & Visual Identity:** Explore visual design guidelines at [BRANDING.md](https://github.com/suporterfid/iroh/blob/main/BRANDING.md).

---

## 🌟 Key Features

- **📧 Forwarded Email Meeting Invite Ingestion:** Polls IMAP mailboxes, extracts `.ics` / `text/calendar` attachments via `sabre/vobject`, and reconciles meeting updates automatically.
- **🔄 Event Reconciliation & Idempotency:** Deduplicates events based on `UID` + `SEQUENCE` numbers. Handles `METHOD:CANCEL` without deleting history and updates participant statuses on `METHOD:REPLY`.
- **🌐 Outlook 365 & Google Workspace Conformance:** Exports clean `webcal://` feeds formatted as a single `VCALENDAR` block with `X-WR-CALNAME` and `X-WR-TIMEZONE` headers.
- **🛡️ Multi-Tenant Security & SSRF Protection:** Strict `tenant_id` database isolation, AES-256 secret encryption at rest, automatic log redaction, and SSRF guard protection blocking private IP ranges (`127.0.0.1`, `10.x`, `192.168.x`, `169.254.169.254`).
- **📱 Responsive UI & Dual i18n:** Modern Vue 3 + Tailwind CSS frontend supporting Month, Week, Day, and Agenda views with full English (`en`) and Portuguese (`pt-BR`) internationalization.
- **🔌 Portfolio Seams:** Optional integration seams with GrandpaSSOn (SSO / PKCE), TaskConnect (Auxiliary HMAC Webhooks), and Jotter (Markdown Meeting Notes via MCP JSON-RPC).
- **🚀 100% Dockerized Local Environment:** Complete local dev loop with PHP 8.2-FPM, Nginx, MySQL 8, Mailpit (IMAP/SMTP test double), and Node 20 Vite dev server.

---

## 🛠️ Technology Stack & Requirements

### System Requirements
- **PHP Runtime:** PHP 8.2+ with PDO (`pdo_sqlite`, `pdo_mysql`, `imap`, `mbstring`, `zip`).
- **Dual Database Support:** 
  - **SQLite:** Zero-config personal installs and single-tenant local testing.
  - **MySQL 8 / MariaDB:** Production shared hosting multi-tenant deployments.
- **Web Server:** Apache (with `mod_rewrite`), LiteSpeed, or Nginx.
- **Background Dispatch:** Standard 1-minute system cron (no background daemons or Supervisors required).

### Docker Containerized Development (Local DX)
Rendezvous features a 100% containerized local development environment (`compose.yaml`):
- `web`: Nginx web server
- `php`: PHP 8.2-FPM runtime with PDO & IMAP modules
- `frontend`: Node 20 / Vite dev server
- `db`: MySQL 8 database
- `mailpit`: IMAP/SMTP test double for simulating email ingestion locally

### Windows & Linux Cross-Platform Parity
All CLI operations offer complete symmetry across Windows PowerShell (`.\scripts\rv.ps1`) and Linux/macOS Bash (`./scripts/rv.sh`):

| Action | Windows PowerShell | Linux / macOS Bash |
| :--- | :--- | :--- |
| **Start Containers** | `.\scripts\rv.ps1 up` | `./scripts/rv.sh up` |
| **Stop Containers** | `.\scripts\rv.ps1 down` | `./scripts/rv.sh down` |
| **Run Test Suite** | `.\scripts\rv.ps1 test` | `./scripts/rv.sh test` |
| **Artisan Commands** | `.\scripts\rv.ps1 artisan ...` | `./scripts/rv.sh artisan ...` |
| **Package Release ZIP** | `.\scripts\rv.ps1 release` | `./scripts/rv.sh release` |

---

## 🎨 Frontend & UI Design System

Rendezvous strictly complies with the IROH Notion-inspired, content-first visual identity specification:
- **Semantic CSS Token Contract:** Built with CSS variables (`:root`, `[data-theme='light']`, `[data-theme='dark']`) ensuring seamless theme switching without hardcoded color values.
- **Typography & Layout:** Uses `Inter` typography, structured sidebar navigation, 480/768/1024/1280px breakpoints, and a 1200px measure for data views (calendar grid).
- **Accessibility & Contrast:** Conforms to WCAG 2.2 AA standards with explicit focus states, minimum touch target sizes (44x44px), and visual status indicators that never rely solely on color.
- **Branding Specification:** For details on visual identity rules, refer to [BRANDING.md](https://github.com/suporterfid/iroh/blob/main/BRANDING.md) on the main IROH meta-repo.

---

## 📦 Shared Hosting Installation (Hostinger Target)

Deploying Rendezvous to Hostinger or any cPanel shared hosting requires no remote CI/CD pipelines:

1. **Package the Application Locally:**
   Inside your Docker environment, run:
   ```bash
   # Windows PowerShell
   .\scripts\rv.ps1 release

   # Linux / macOS
   ./scripts/rv.sh release
   ```
   This generates `dist_release/rendezvous-release.zip` containing pre-compiled frontend assets, production PHP dependencies, and `.htaccess` URL rewriting rules.

2. **Upload to Hosting:**
   - Upload `rendezvous-release.zip` to your web server via FTP, SFTP, or Hostinger File Manager.
   - Extract the contents into your target directory (e.g., `/public_html/` or a subdomain folder `/public_html/calendar/`).

3. **Configure Environment (`.env`):**
   - Copy `.env.example` to `.env`.
   - Update database credentials (`DB_CONNECTION=mysql`, `DB_HOST=localhost`, `DB_DATABASE=...`, `DB_USERNAME=...`, `DB_PASSWORD=...`).
   - Set `APP_URL` to your domain URL.

4. **Run Database Migrations:**
   Run migrations via SSH or web terminal:
   ```bash
   php artisan migrate --force
   ```

5. **Set Up Cron Job:**
   Add a 1-minute cron job in Hostinger hPanel or cPanel:
   ```cron
   * * * * * /usr/bin/php /path/to/public_html/artisan calendar:ingest-mailboxes >/dev/null 2>&1
   */10 * * * * /usr/bin/php /path/to/public_html/artisan calendar:refresh-feeds >/dev/null 2>&1
   ```

---

## 💻 Usage & CLI Commands

Once installed, manage the platform using the built-in Artisan commands or container scripts:

```bash
# Ingest meeting invites from IMAP mailboxes
php artisan calendar:ingest-mailboxes

# Refresh subscribed external ICS/CalDAV feeds
php artisan calendar:refresh-feeds

# Run automated test suite inside Docker
.\scripts\rv.ps1 test
```

### Available Endpoints
- **Web Application:** `http://localhost:8080`
- **Health Check API:** `http://localhost:8080/api/v1/platform/health`
- **Mailpit UI (IMAP/SMTP Double):** `http://localhost:8025`

---

## 🔐 Authentication & Single Sign-On (SSO)

Rendezvous integrates seamlessly with **GrandpaSSOn** as its central Single Sign-On IdentityProvider:
- **Authentication Protocols:** OpenID Connect (OIDC), OAuth2 Authorization Code Flow with PKCE, and signed JWT tokens.
- **Tenant Context:** Automatically inherits tenant membership and authorization claims from GrandpaSSOn tokens.
- **Central SSO Hub:** Learn more or deploy identity services at [suporterfid/grandpasson-ce](https://github.com/suporterfid/grandpasson-ce).

---

## 🔗 Integration with Other IROH Projects

Rendezvous collaborates with other tools in the IROH Initiative ecosystem via optional, decoupled integration seams:

* 🔐 **[grandpasson](https://github.com/suporterfid/grandpasson-ce)** — Central Single Sign-On (SSO) IdentityProvider for seamless multi-tenant authentication.
* 📝 **[jotter](https://github.com/suporterfid/jotter)** — Self-hosted Markdown knowledge base; links meeting notes to calendar events via read-only MCP JSON-RPC.
* 📄 **[ConvertDocToMd-PHP](https://github.com/suporterfid/ConvertDocToMd-PHP)** — Lightweight PHP DOC/DOCX/PDF to Markdown converter for attachment preprocessing.
* ⚡ **[taskconnect](https://github.com/suporterfid/taskconnect)** — Multi-tenant HTTP task scheduler for auxiliary cron job triggers and HMAC webhooks.
* 🟢 **[statusconnect](https://github.com/suporterfid/statusconnect)** — Uptime monitoring and public status page for platform availability tracking.
* 🔢 **[tallymark](https://github.com/suporterfid/tallymark)** — Minimalist event counter for tracking invite ingestion metrics.

---

## 🤝 Contributing

We welcome contributions! Please review our local [`CONTRIBUTING.md`](CONTRIBUTING.md) guide before submitting pull requests.

Initiative-wide governance, architectural guidelines, and community rules are coordinated at the main meta-repository: [suporterfid/iroh CONTRIBUTING.md](https://github.com/suporterfid/iroh/blob/main/CONTRIBUTING.md).

---

## 📜 License

Rendezvous is open-source software licensed under the [MIT License](LICENSE).