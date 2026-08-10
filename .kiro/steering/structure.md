# Structure & Conventions Steering — Rendezvous

## Repository Layout
```text
rendezvous/
├── app/                        # Application / Backend (Laravel 12)
│   ├── Application/            # Use Cases, Orchestration
│   ├── Domain/                 # Pure Domain (Events, Recurrence, Reconciliation, FeedExport)
│   ├── Http/                   # Controllers, Middleware, Requests
│   ├── Infrastructure/         # Repositories, IMAP Client, SSRF HTTP Client, Encryption
│   ├── Console/                # Artisan Commands (ingest-mailboxes, refresh-feeds)
│   └── Policies/               # Tenant & Resource Authorization Policies
├── config/                     # Laravel configuration
├── database/                   # Migrations, Seeders, Factories
├── docker/                     # Docker environment configurations
│   ├── php/                    # Dockerfile (PHP 8.2-FPM + pdo_sqlite + pdo_mysql + imap + zip)
│   └── nginx/                  # Nginx default configuration
├── docs/                       # Project documentation & spec
├── frontend/                   # Frontend SPA (Vue 3 + Vite + TypeScript + Tailwind)
│   ├── src/
│   │   ├── components/         # Reusable UI components
│   │   ├── pages/              # Views / Page routes
│   │   ├── stores/             # Pinia stores
│   │   ├── services/           # API Client (Axios / Fetch)
│   │   ├── i18n/               # Locales (en, pt-BR)
│   │   └── utils/              # Helpers
├── public/                     # Document root
├── routes/                     # API & Web routes
├── scripts/                    # Containerized utility scripts (rv.sh, rv.ps1)
├── compose.yaml                # Docker Compose dev environment
├── compose.ci.yaml             # Docker Compose CI environment
└── Makefile                    # Make targets
```

## Naming & Code Conventions
- Class names: PascalCase (e.g. `MailboxIngestionService`, `EventReconciliationDomainService`).
- Database tables: snake_case plural (e.g. `tenants`, `calendar_sources`, `mailbox_ingest_configs`, `events`).
- Frontend components: PascalCase (e.g. `CalendarGrid.vue`, `EventModal.vue`).
- i18n translation keys: dot.separated.semantic (e.g. `events.status.cancelled`, `mailbox.ingest.success`).
- CLI tool script: `./scripts/rv.sh` (Linux/macOS) and `.\scripts\rv.ps1` (Windows PowerShell).
