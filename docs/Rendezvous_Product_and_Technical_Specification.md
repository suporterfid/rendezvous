# Rendezvous — Product and Technical Specification

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

## Document Metadata

- **Version:** 1.1
- **Status:** Implementation-ready MVP specification
- **Target audience:** AI coding agents (Claude Code, Codex, and equivalents), software architects, maintainers, and contributors
- **Primary deployment target:** PHP and MySQL/SQLite-compatible shared hosting with cron access
- **Reference deployment profile:** Hostinger-like shared hosting without a VPS, Redis, long-running workers, or process supervisors
- **Development environment:** Docker-only, no host PHP/Composer/Node/MySQL installation
- **Initial interface languages:** English (`en`) and Brazilian Portuguese (`pt-BR`)
- **Sibling projects:** [`suporterfid/jotter`](https://github.com/suporterfid/jotter), [`suporterfid/grandpasson`](https://github.com/suporterfid/grandpasson), [`suporterfid/taskconnect`](https://github.com/suporterfid/taskconnect)

***

## 1. Executive Summary

Rendezvous is an open-source, multi-tenant calendar mash-up platform. It aggregates events from multiple calendar sources — subscribed ICS feeds, read-only CalDAV subscriptions, and, most distinctively, meeting invites forwarded by email — into a single browsable calendar per tenant, and republishes the merged result as a subscribable ICS feed.

The product exists to solve a specific, common, and currently unsolved problem: a user's corporate email tenant refuses native calendar sharing (no federation, no external CalDAV/EWS delegation, restrictive ACLs), but the corporate mail client can still forward or copy invite emails. Rendezvous turns a dedicated inbox into a calendar source by parsing `text/calendar` MIME parts and `.ics` attachments from forwarded invites via IMAP polling, normalizing them into structured events, and merging them with everything else the user tracks.

No published open-source project in PHP + MySQL/SQLite combines calendar mash-up with email-based invite ingestion today. Adjacent pieces exist independently — WebCalendar (PHP/MySQL multi-user calendar with ICS feed subscription), Baïkal (PHP CalDAV/CardDAV server), MergeCal (Python/Django ICS feed merge service) — but none covers the mailbox → parser → unified calendar flow in PHP. This is the gap Rendezvous fills.

Rendezvous is designed as a sibling to three existing OSS projects by the same author, and MUST follow their established architectural conventions to minimize operational cost and maximize component reuse (authentication, cron scheduling, design system):

- [`suporterfid/jotter`](https://github.com/suporterfid/jotter) — self-hosted Markdown knowledge base, Laravel 12 + Vue 3 SPA, MySQL as rebuildable index over Markdown files, multi-workspace data model, `IdentityProvider` auth seam, MCP server.
- [`suporterfid/grandpasson`](https://github.com/suporterfid/grandpasson) — minimalist PHP + MySQL SSO broker for shared hosting, federates Google/Microsoft/GitHub identity, tenancy claims, service-client machine tokens, PATs, JWKS.
- [`suporterfid/taskconnect`](https://github.com/suporterfid/taskconnect) — open-source multi-tenant HTTP task scheduler for PHP/MySQL shared hosting, Laravel 12 + Vue 3, cron-driven at-least-once delivery with retries and SSRF protection.

***

## 2. Normative Language

The terms **MUST**, **MUST NOT**, **SHOULD**, **SHOULD NOT**, and **MAY** express requirement priority:

- **MUST / MUST NOT:** mandatory for the stated release.
- **SHOULD / SHOULD NOT:** strongly recommended unless a documented reason justifies a deviation.
- **MAY:** optional.

Unless explicitly marked as deferred, requirements in this document apply to the MVP.

***

## 3. Product Goals

### 3.1 Primary Goals

The product MUST:

- Consolidate events from multiple calendar sources (subscribed ICS feed URLs, read-only CalDAV subscriptions, and email-delivered invites) into a single browsable calendar per tenant.
- Ingest meeting invites (`METHOD:REQUEST`, `METHOD:CANCEL`, `METHOD:REPLY`, `METHOD:REFRESH`) delivered as `text/calendar` MIME parts or `.ics` attachments, via IMAP polling of a dedicated mailbox or folder fed by a forwarding rule on the user's corporate mail client.
- Deduplicate and version events by `UID` + `SEQUENCE`, correctly handling updates and cancellations.
- Publish an aggregated, subscribable ICS feed (`webcal://`) per logical calendar or tenant-selected combination of calendars, consumable by Google Calendar, Outlook, and Apple Calendar — with explicit conformance requirements against Outlook 365 and Google Workspace import behavior (see Section 27).
- Provide a native calendar UI (month/week/day/agenda views) built on the shared visual identity used across the sibling portfolio.
- Run on PHP/MySQL shared hosting with no daemons, workers, or long-running processes — cron only, one-minute cadence, matching TaskConnect's execution model.
- Support multiple tenants and multiple users per tenant from the first migration.
- Support `en`/`pt-BR` internationalization from the first release, matching TaskConnect's and Jotter's i18n architecture.
- Integrate optionally — never as a hard runtime dependency — with GrandpaSSOn for SSO and TaskConnect for scheduled auxiliary jobs, preserving Jotter's rule: *"Neighbor integrations are optional seams, not runtime dependencies."*
- Provide a fully Dockerized development environment and a Docker-based deployment/release pipeline, matching Jotter's and TaskConnect's "Docker-only development loop, no host PHP/Composer/Node/MySQL" convention, while remaining deployable to plain PHP/MySQL shared hosting in production.

### 3.2 Secondary Goals

The product SHOULD:

- Be installable by a technically capable user who is not an infrastructure specialist.
- Minimize operational dependencies and maintenance overhead.
- Use a code structure that AI coding agents can navigate and extend reliably.
- Allow agencies and integrators to operate isolated tenant workspaces from a single deployment.
- Make future infrastructure replacement (e.g., CalDAV write server, OAuth-based ingestion) possible without redesigning the public API or core domain model.

***

## 4. Non-Goals (MVP)

Rendezvous does not provide, in the MVP:

- Bidirectional CalDAV write support (organizing/publishing events to external calendars).
- Outbound invite composition or RSVP sending — Rendezvous consumes invites, it does not originate them.
- Direct OAuth against Microsoft Graph or Google Calendar API as the primary or sole ingestion path — IMAP-based ingestion MUST remain the guaranteed path, independent of corporate IT approval.
- Billing, usage metering, or public self-service tenant provisioning — tenant creation is platform-admin controlled in the MVP, mirroring TaskConnect's decision.
- A native mobile app — the MVP is a responsive web application.
- Attachment parsing for non-calendar MIME types.
- Real-time or near-real-time propagation into Google Workspace or Outlook 365 via the ICS subscription mechanism — this is a platform-side limitation outside Rendezvous's control (see Section 27.5).

These may be reconsidered after the MVP is stable.

***

## 5. Product Principles

1. Deployment simplicity over infrastructure sophistication.
2. A dedicated mailbox is a first-class calendar source, not an afterthought.
3. Safe defaults over unrestricted flexibility (SSRF protection, secret encryption, TLS verification).
4. Strong tenant isolation from the first migration.
5. Idempotent, at-least-once ingestion with explicit `UID`/`SEQUENCE` reconciliation.
6. Observable failures instead of silent failures (quarantine, not discard).
7. Clear domain boundaries instead of controller-driven business logic.
8. Portable core logic instead of framework-specific coupling.
9. Docker-only development and deployment tooling; PHP/MySQL-only production runtime.
10. Shared visual identity and integration seams across the sibling portfolio, never a hard dependency on any one sibling.
11. Explicit, honest limitations over implied guarantees — where a target platform (Google, Outlook) imposes a hard constraint, the product documents and designs around it rather than promising behavior it cannot deliver.

***

## 6. Target Users

### 6.1 Primary Users

- Professionals whose corporate email tenant blocks calendar sharing but allows email forwarding.
- Freelancers/consultants juggling invites from multiple distinct corporate domains.
- Agencies/integrators operating one Rendezvous instance for multiple clients, each with isolated sources and ingestion mailboxes — same multi-tenant pattern as TaskConnect.

### 6.2 Secondary Users

- Teams already running Jotter, GrandpaSSOn, or TaskConnect who want a calendar layer that shares identity and design language with their existing stack.
- Support engineers troubleshooting failed invite ingestion or feed subscription issues in Outlook/Google.

***

## 7. Core Domain Model

The implementation MUST distinguish the following concepts.

### 7.1 Tenant

A tenant is an isolated workspace. Tenant-owned records MUST NOT be visible or modifiable outside the active tenant context.

### 7.2 Calendar

A calendar is a tenant-scoped logical grouping of events with a display name, color, and default visibility (e.g., "Work — Corporate Inbox," "Personal," "Client X").

### 7.3 Calendar Source

A calendar source is a configured origin of events feeding into a calendar: an email mailbox (IMAP ingestion), a subscribed ICS feed URL, or a read-only CalDAV subscription.

### 7.4 Mailbox Ingest Config

A mailbox ingest config is the IMAP connection configuration (host, port, folder, encrypted credentials) bound to an email-type calendar source.

### 7.5 Event

An event is the normalized, structured representation of a `VEVENT`, uniquely identified within a calendar by `UID` (and `RECURRENCE-ID` for recurrence exceptions), versioned by `SEQUENCE`.

### 7.6 Ingest Log Entry

An ingest log entry records the outcome (processed, quarantined, duplicate) of one inbound email message during mailbox polling, for auditability without retaining the source email body.

### 7.7 Secret

A secret is encrypted sensitive data (IMAP credentials, CalDAV credentials) referenced by calendar sources. Secret plaintext MUST NOT be returned after creation, mirroring TaskConnect's `secrets` model.

***

## 8. Roles and Authorization

### 8.1 Platform Roles

- **Platform super admin:** manages the deployment and may access all tenants for support and administration.

### 8.2 Tenant Roles

- **Tenant admin:** manages tenant settings, members, calendars, calendar sources, mailbox ingest configs, and secrets.
- **Tenant member:** creates and manages calendars and sources within permitted scope.
- **Read-only viewer:** views calendars and events without changing configuration.

### 8.3 Authorization Requirements

- Every tenant-scoped request MUST resolve an authenticated tenant context.
- Authorization MUST be enforced in centralized policies or guards, not repeated ad hoc in controllers.
- Record lookup by identifier MUST include tenant scoping in the query or repository boundary.
- API keys and service tokens MUST have explicit tenant and permission scopes.

***

## 9. Functional Requirements

### 9.1 Email-Based Invite Ingestion (Core Differentiator)

- The system MUST provide, per tenant, one or more configurable IMAP ingestion mailboxes/folders via the UI.
- A cron command (`php artisan calendar:ingest-mailboxes`) MUST poll IMAP on every invocation, using a mature PHP IMAP client library (`webklex/php-imap` is the reference choice).
- The parser MUST recognize invites in two forms: MIME parts with `Content-Type: text/calendar; method=REQUEST|CANCEL|REPLY|REFRESH`, and `.ics` attachments — parsed and validated using `sabre/vobject`, the reference PHP iCalendar library also used by Baïkal and Nextcloud.
- The system MUST identify each event uniquely by `UID`, MUST compare incoming `SEQUENCE` against the stored value, and MUST silently discard sequences that are older than or equal to an already-processed sequence (idempotency).
- `METHOD:CANCEL` MUST mark the corresponding event (matched by `UID`, and by `RECURRENCE-ID` when present) as cancelled without deleting history.
- `METHOD:REPLY` MUST update only the responding attendee's `PARTSTAT`, never other event fields.
- Successfully processed emails MUST be flagged/moved (IMAP `\Seen` flag + a "Processed" folder) to support audit and manual reprocessing; parsing failures MUST move to a "Quarantine" folder/state with the failure reason recorded — never silently discarded.
- The system MUST deduplicate multiple forwards of the same invite (e.g., forwarded to two mailboxes of the same tenant) using the combination `UID` + `ORGANIZER` + `tenant_id`.
- Attachments, email body, and headers other than the extracted `VCALENDAR` MUST be discarded after extraction — only the normalized event is persisted, not the full email (minimizes PII surface at rest).
- IMAP credentials MUST be stored as encrypted secrets at rest, following the same pattern as TaskConnect's `secrets` table (never plaintext, never returned by read APIs after creation).

### 9.2 Event Normalization and Recurrence

- Every persisted event MUST store timestamps in UTC and the original `TZID` of the `VEVENT`, following the same "UTC on disk, local timezone at display" principle already adopted in TaskConnect's scheduling domain.
- The system MUST expand `RRULE` for date-range display, respecting `EXDATE` and `RECURRENCE-ID` overrides, using `sabre/vobject`'s recurrence components.
- Timezone conflicts from Exchange/Outlook invites (proprietary `VTIMEZONE`) MUST be normalized to an IANA timezone before persistence; mapping failures MUST fall back to UTC with a visible "ambiguous timezone" flag in the UI, never fail silently.
- Every event MUST carry an explicit source type: `email_invite`, `ics_feed_subscription`, `caldav_subscription`, or `manual`. The UI MUST distinguish origin visually without relying on color alone, per the shared visual identity specification's rule that status must never be communicated by color alone.

### 9.3 Additional Sources and Mash-Up

- The system MUST support subscribing to external `ICS`/`webcal://` feed URLs, with periodic cron-driven polling and local caching.
- The system SHOULD support read-only CalDAV subscriptions (e.g., against Baïkal, Google, iCloud) using an `sabre/dav` client.
- Each source MUST belong to a logical calendar within the tenant, with independent display color/label and visibility toggle, without affecting other sources.

### 9.4 Output and Consumption

- The system MUST expose, per logical calendar or per tenant-selected combination of calendars, an aggregated `ICS` feed subscribable via `webcal://` with a non-guessable access token.
- This feed MUST conform to the Outlook 365 and Google Workspace compatibility requirements defined in Section 27 — general RFC 5545 validity is necessary but not sufficient.
- The system MUST provide a native calendar UI (month/week/day/agenda views) built on the shared portfolio visual identity.
- The system SHOULD expose a versioned REST API (`/api/v1`) for integrations, following TaskConnect's established conventions: cursor-based pagination, standardized error envelope, `Idempotency-Key` support, non-sequential public IDs.
- The system MAY expose a read-only CalDAV endpoint post-MVP for native client consumption without relying on `webcal://`.

### 9.5 Multi-Tenancy

- The system MUST use a shared database with tenant-ownership columns (`tenant_id`) on every tenant-owned table, and `calendar_id` where applicable — the same model used in TaskConnect and Jotter.
- Tenant isolation MUST be covered by automated security tests (cross-tenant identifier substitution), replicating TaskConnect's security test suite.
- Resource uniqueness (calendar slug, ingestion address) MUST be scoped by tenant, never globally unique without that scoping.
- SQLite MUST be supported as an alternative database for low-cost single-tenant/personal installs; MySQL 8 is the reference database for multi-tenant operation.

***

## 10. Non-Functional Requirements

- **Hosting:** the production runtime MUST work on PHP 8.2+/MySQL 8 (or compatible MariaDB)/SQLite, Apache or LiteSpeed, one-minute cron, with no Node.js, Docker, Redis, persistent workers, or shell access required after installation — the same shared-hosting constraint adopted by TaskConnect and Jotter.
- **Secret security:** IMAP credentials and CalDAV/ICS feed tokens MUST follow TaskConnect's encryption-at-rest and audit-log redaction pattern (`secrets` never plaintext, never returned after creation, redacted in audit trails).
- **SSRF protection:** when fetching user-supplied ICS/CalDAV feed URLs, the system MUST apply TaskConnect's SSRF validation policy (block private/loopback/link-local IPs, revalidate redirects, support domain allowlists) since the same risk vector applies here.
- **Executor reliability:** IMAP polling and feed refresh MUST use atomic claiming with a token and expiry, mirroring TaskConnect's claiming model, to tolerate overlapping or interrupted cron processes without duplicating ingestion.
- **Observability:** the system MUST expose a scheduler heartbeat (`last calendar:ingest-mailboxes run`, `last calendar:refresh-feeds run`) and an ingestion-failure dashboard (quarantined emails, feeds in error), in the same spirit as TaskConnect's scheduler heartbeat and dead-run dashboard.
- **Retention:** raw email bodies MUST be discarded after extraction; ingestion metadata (sender, timestamp, result, failure reason) MAY be retained for a configurable period (e.g., 30–90 days) for audit, without retaining invite content beyond its structured, persisted event representation.
- **Accessibility:** the UI MUST follow WCAG 2.2 AA per the shared visual identity specification — contrast, visible focus, 44×44px touch targets, no status conveyed by color alone.
- **Internationalization:** every user-facing string MUST come from translation keys; date/time/timezone formatting MUST be locale-aware; `en` and `pt-BR` MUST be available from the first release, following TaskConnect's `frontend/src/i18n/locales/{en,pt-BR}/*.json` architecture.
- **Containerization:** the entire local development loop (application, database, mail test server, worker/cron simulation) MUST run via Docker Compose with zero host-installed PHP/Composer/Node/MySQL, matching Jotter's `./scripts/jt.sh up` and TaskConnect's `./scripts/tc.sh up` pattern. Deployment tooling MUST also be containerized — see Section 15.
- **Cross-platform compatibility:** outbound calendar data MUST be validated against the documented import behavior of Outlook 365 and Google Workspace, not only against generic RFC 5545 validators — see Section 27.

***

## 11. Visual Identity and Design System

Rendezvous MUST reuse, without reinterpretation, the visual identity specification already adopted by Jotter and referenced as the portfolio-wide standard. This includes:

- The public semantic CSS token contract (`--color-bg-canvas`, `--color-action-primary`, `--color-text-link`, etc.) in light and dark mode, with no literal color in any component.
- The typography stack (`Inter` for UI, `Source Serif 4` optionally for long-form reading, `IBM Plex Mono` for fixed-width data), with Noto font coverage per script to support internationalized tenants.
- The responsive shell with sidebar, breakpoints at `480/768/1024/1280px`, a `720px` reading measure for textual content and a `1200px` measure for data views (the weekly/monthly calendar grid is a "data view" and MUST use the `1200px` measure).
- The `light | dark | system` theme, persisted only on explicit user choice, resolved via `prefers-color-scheme` otherwise.
- Structural RTL/i18n support: logical CSS properties, mirroring only for directional icons, `dir="auto"` for mixed-script data (e.g., organizer names in different scripts within one invite).
- Component states (hover/active/selected/focus/disabled/error) applied consistently to the calendar grid, day cells, event chips, and source-origin badges (`email_invite`/`ics_feed`/`caldav`), reusing the already-normative "tags and callouts" and "cards, list rows" patterns.

No new design system is required: portfolio integration is achieved by adopting the same visual-identity token/documentation source used by Jotter (`docs/visual-identity.md` + `assets/brand/`) as a reference dependency or documentation submodule.

***

## 12. Integration with the Existing OSS Portfolio

| Sibling repository | Role in Rendezvous | Coupling |
|---|---|---|
| [`grandpasson`](https://github.com/suporterfid/grandpasson) | Identity provider/SSO via `POST /session/exchange` (authorization code flow) or service-client machine tokens (`client_credentials`) for automation; tenant/role claims from the exchange response are reused as Rendezvous's tenant context | Optional seam, not a runtime dependency — same rule Jotter applies |
| [`taskconnect`](https://github.com/suporterfid/taskconnect) | Executor for auxiliary scheduled jobs (e.g., event-reminder dispatch, push-preferring third-party feed sync) via HTTP tasks targeting Rendezvous-internal endpoints | Optional — Rendezvous MUST retain its own internal cron as the guaranteed path; TaskConnect is a reinforcement, never a prerequisite |
| [`jotter`](https://github.com/suporterfid/jotter) | Optional cross-context source: notes linked to an event (`[[wikilink]]` from a meeting to its prep note), consumed via Jotter's read-only MCP server (`list_notes`, `read_note`, `search_notes`, `get_backlinks`) | Optional — added-value integration, never blocking |

This integration is technically low-risk because all three sibling projects already share the same architectural convention: modular-monolith Laravel, Vue 3 + TypeScript + Vite + Pinia + Tailwind + Vue I18n frontend, MySQL with `tenant_id` scoping, cron as the sole scheduling mechanism, zip-based shared-hosting deployment, and the same product-prefixed OAuth scope convention in the GrandpaSSOn broker (e.g., `tasks:*`, `kb:*`, `status:*`, `analytics:*`).

Following that established pattern, Rendezvous MUST register its own scope vocabulary in GrandpaSSOn, exactly as TaskConnect, StatusConnect, and TallyMark already did:

```bash
php cron/admin.php client:create-service "Rendezvous" \
  --scopes=calendar:read,calendar:write,calendar:callback \
  --aud=workspace/env_abc123
```

Client integration for the exchange flow, service tokens, and PATs MUST follow GrandpaSSOn's documented client-integration contract exactly (`/session/exchange` response shape, `tenant`/`tenants`/`groups`/`locale` fields, `/oauth/token` for public clients with PKCE, `/oauth/introspect`, `/oauth/revoke`) — see `docs/client-integration.md` in the `grandpasson` repository for the authoritative reference.

***

## 13. Data Model (MVP)

Core tables, following the same naming and tenant-scoping discipline as TaskConnect:

- `tenants`, `tenant_memberships`, `users`, `user_preferences`
- `calendars` (`id`, `tenant_id`, `name`, `color`, `visibility_default`)
- `calendar_sources` (`id`, `tenant_id`, `calendar_id`, `source_type` [`email_mailbox`|`ics_feed`|`caldav`], `config_json`, `secret_ref`, `last_synced_at`, `last_sync_state`)
- `mailbox_ingest_configs` (`id`, `tenant_id`, `source_id`, `imap_host`, `imap_port`, `folder`, `secret_ref`, `claim_token`, `claimed_at`, `claim_expires_at`)
- `events` (`id`, `public_id`, `tenant_id`, `calendar_id`, `uid`, `sequence`, `recurrence_id` nullable, `method_origin`, `organizer_json`, `attendees_json`, `summary`, `description`, `location`, `dtstart_utc`, `dtend_utc`, `tzid_original`, `rrule` nullable, `exdates_json` nullable, `status` [`confirmed`|`cancelled`|`tentative`], `source_type`, `raw_vevent_hash`, `created_at`, `updated_at`)
- `event_attendee_responses` (`id`, `event_id`, `attendee_email`, `partstat`, `updated_at`)
- `ingest_log` (`id`, `tenant_id`, `source_id`, `message_id`, `result` [`processed`|`quarantined`|`duplicate`], `reason` nullable, `created_at`)
- `audit_logs` (same actor/tenant/action/resource/timestamp/request_id pattern as TaskConnect)

Recommended constraints and indexes:

```text
UNIQUE events(tenant_id, calendar_id, uid, recurrence_id)
INDEX events(tenant_id, calendar_id, dtstart_utc)
INDEX mailbox_ingest_configs(claim_expires_at)
INDEX calendar_sources(tenant_id, source_type, last_synced_at)
INDEX ingest_log(tenant_id, created_at)
```

***

## 14. Application Architecture

### 14.1 Architectural Style

The MVP MUST be a modular monolith, matching TaskConnect's and Jotter's structure.

Suggested modules:

```text
Auth
PlatformAdministration
Tenancy
Memberships
Calendars
CalendarSources
MailboxIngestion
IcsFeedSync
CaldavSync
Events
Recurrence
FeedExport            (Outlook/Google conformance layer)
Secrets
ApiKeys
Audit
Notifications
Localization
Health
Retention
IdentityProviderIntegration   (GrandpaSSOn seam)
TaskConnectIntegration        (TaskConnect seam)
JotterIntegration             (Jotter MCP seam)
```

### 14.2 Layering

```text
Interface Layer
  HTTP controllers
  CLI commands
  request validation

Application Layer
  use cases
  authorization orchestration
  transactions

Domain Layer
  iCalendar parsing and normalization
  recurrence expansion
  UID/SEQUENCE reconciliation
  timezone normalization
  Outlook/Google feed-conformance rules

Infrastructure Layer
  database persistence
  encryption
  IMAP client
  HTTP transport (feed fetch, SSRF-guarded)
  CalDAV client
  framework adapters
```

### 14.3 Coding Rules

- Controllers MUST NOT contain core business logic.
- iCalendar parsing and recurrence expansion MUST be isolated and testable without HTTP, IMAP, or database access.
- `UID`/`SEQUENCE` reconciliation MUST be isolated and deterministic.
- Outbound feed fetch construction and SSRF validation MUST be handled by a dedicated, reusable service (shared by feed sync, feed test, and production execution).
- Outbound aggregated-feed generation (Section 27) MUST be isolated in a dedicated `FeedExport` service, independently unit-testable against Outlook/Google conformance rules without HTTP or database access.
- Tenant scoping MUST NOT depend on developers remembering to add filters manually.
- Framework models MUST NOT become the only representation of domain rules.
- User-facing frontend text MUST NOT be hard-coded in components.
- Secret redaction MUST be centralized.
- IMAP mailbox claiming MUST be reusable by both the ingestion command and its tests.

### 14.4 Suggested Repository Structure

```text
project-root/
  app/
    Application/
    Domain/
    Infrastructure/
    Http/
    Console/
    Policies/
  bootstrap/
  config/
  database/
    factories/
    migrations/
    seeders/
  docker/
    php/
    nginx/
    mailpit/
  frontend/
    src/
      components/
      pages/
      router/
      stores/
      services/
      i18n/
      utils/
  public/
  resources/
  routes/
  scripts/
    rv.sh
    rv.ps1
  storage/
  tests/
    Unit/
    Integration/
    Feature/
    EndToEnd/
  docs/
    rendezvous-spec.md
    architecture.md
    visual-identity.md
    client-integration-grandpasson.md
    calendar-compatibility.md
    deployment.md
  compose.yaml
  compose.ci.yaml
  Makefile
```

***

## 15. Dockerized Development and Deployment

This section is normative and mandatory for the MVP, extending the pattern already established by Jotter and TaskConnect.

### 15.1 Development Environment

Requirements:

- Docker with Docker Compose V2.
- No host PHP, Composer, Node, npm, or MySQL/SQLite installation.
- A single bootstrap command MUST bring up the full stack: application container (PHP-FPM), web server (Nginx or equivalent), MySQL container, and a mail-testing container (Mailpit, matching TaskConnect's use of Mailpit) to simulate the corporate-forward → IMAP ingestion mailbox locally without any external mail provider.
- Frontend dependencies MUST run from a Docker named volume, so `rv` commands do not depend on a host `node_modules` directory, matching Jotter's approach.

Reference commands (mirroring `jt.sh`/`tc.sh`):

```bash
cp .env.example .env
./scripts/rv.sh up       # bootstrap: generates dev credentials, installs deps, builds Vue app, migrates DB, serves at http://localhost:8080
./scripts/rv.sh down     # stop containers
./scripts/rv.sh test     # backend + frontend unit tests
./scripts/rv.sh e2e      # Playwright smoke test
./scripts/rv.sh artisan platform:bootstrap-admin admin@example.com 'use-a-long-random-password'
./scripts/rv.sh artisan calendar:ingest-mailboxes   # manual trigger for local testing
./scripts/rv.sh composer …
./scripts/rv.sh npm …
./scripts/rv.sh release  # produces dist/rendezvous-release.zip + SHA-256 checksum
```

Windows PowerShell equivalent: `.\scripts\rv.ps1 up`.

Local compose services (minimum):

```yaml
services:
  app:        # PHP-FPM + Laravel
  web:        # Nginx, document root = app/public
  db:         # MySQL 8 (default) — SQLite selectable via .env for single-tenant profile
  mailpit:    # SMTP/IMAP test double — simulates the corporate-forward mailbox
  node:       # Vite dev server / frontend build, named volume for node_modules
```

Mailpit (or an equivalent lightweight IMAP+SMTP test double) MUST be wired so that a developer can simulate the full corporate-forward flow end-to-end locally: send a `.ics`-bearing test email into the container's mailbox, run `calendar:ingest-mailboxes`, and observe the resulting event — without any dependency on a real corporate mail server. This is a first-class local development requirement, not an optional nicety, because it is the only reliable way to test the product's core differentiator without a live corporate account.

### 15.2 CI

- A `compose.ci.yaml` MUST exist, mirroring TaskConnect's and Jotter's CI compose profile, running the same containers non-interactively for automated test execution in GitHub Actions or equivalent.
- CI MUST run backend unit/integration tests, frontend unit tests, the security test suite (tenant isolation, SSRF blocking), and the Outlook/Google feed-conformance test suite (Section 27.4) inside containers — never against a host-installed PHP/MySQL.

### 15.3 Deployment / Release

- Production deployment target remains plain PHP 8.2+/MySQL 8 (or SQLite)/Apache-or-LiteSpeed shared hosting, with **no Docker, Node.js, Redis, or persistent workers required at runtime** — Docker is a build and development tool only, exactly as stated in Jotter's and TaskConnect's architecture docs ("Docker is a development and build tool only").
- The Docker-based release pipeline (`rv release` / `make release`) MUST run entirely inside containers and MUST produce a deployable zip artifact containing:
  - the `app/` tree with production Composer dependencies (`composer install --no-dev --optimize-autoloader`);
  - built frontend static assets (`public/build`);
  - a SHA-256 checksum file, matching Jotter's and TaskConnect's release contract.
- The deployment document (`docs/deployment.md`) MUST describe: pointing the hosting document root at `app/public/`, running migrations, configuring the one-minute cron entries, and a post-install security checklist — mirroring the structure of TaskConnect's and GrandpaSSOn's deployment docs.
- An optional containerized deployment profile (Docker Compose or a single Dockerfile) MAY be documented for users deploying to a VPS/container host instead of shared hosting, but this MUST NOT be the only documented deployment path — shared-hosting zip deployment MUST remain first-class.

### 15.4 Cron in Both Environments

```cron
* * * * * /usr/bin/php /path/to/app/artisan calendar:ingest-mailboxes >/dev/null 2>&1
*/10 * * * * /usr/bin/php /path/to/app/artisan calendar:refresh-feeds >/dev/null 2>&1
17 * * * * /usr/bin/php /path/to/app/artisan calendar:maintenance >/dev/null 2>&1
```

In Docker development, these commands SHOULD be simulated via a lightweight cron container or a `make cron` target (matching GrandpaSSOn's `make cron` convention) rather than relying on the developer to manually re-run commands.

***

## 16. REST API Design (Summary)

Follows TaskConnect's established conventions:

- Base path: `/api/v1`
- Media type: `application/json`
- Timestamps: ISO 8601 UTC (`Z` suffix)
- Authentication: session cookie for the frontend, API key or GrandpaSSOn-issued token for automation clients
- Pagination: cursor-based
- Errors: stable machine-readable error codes in a standard envelope
- Resource identifiers: public, non-sequential IDs

Core resources:

```text
/auth
/tenants
/tenant-memberships
/calendars
/calendar-sources
/mailbox-ingest-configs
/events
/ingest-log
/api-keys
/audit-logs
/user-preferences
/platform/health
```

Error envelope (identical shape to TaskConnect's, for consistency across the portfolio):

```json
{
  "error": {
    "code": "validation_error",
    "message": "The request is invalid.",
    "details": {
      "imap_host": ["The IMAP host field is required."]
    },
    "request_id": "req_01JXYZ..."
  }
}
```

***

## 17. Internationalization

### 17.1 Initial Locales

- English: `en`
- Brazilian Portuguese: `pt-BR`

### 17.2 Translation Architecture

Recommended structure, mirroring TaskConnect's:

```text
frontend/src/i18n/
  index.ts
  locales/
    en/
      common.json
      auth.json
      dashboard.json
      calendars.json
      calendarSources.json
      mailboxIngest.json
      events.json
      settings.json
      validation.json
    pt-BR/
      common.json
      auth.json
      dashboard.json
      calendars.json
      calendarSources.json
      mailboxIngest.json
      events.json
      settings.json
      validation.json
```

Rules:

- Use stable semantic keys (e.g., `events.status.cancelled`).
- Do not use full English sentences as translation keys.
- Do not concatenate translated sentence fragments.
- Support interpolation and pluralization.
- Keep technical identifiers and stable API error codes untranslated.
- The frontend MUST localize dates, times, weekdays, relative timestamps, timezones, and durations.

***

## 18. Observability and Diagnostics

### 18.1 User Diagnostics

Users MUST be able to determine:

- whether a mailbox poll ran, and when;
- whether an inbound email was processed, quarantined, or treated as a duplicate, and why;
- which feed sources are failing to refresh, and the last error;
- whether an event's timezone mapping is ambiguous;
- the expected propagation delay of the aggregated feed into Outlook 365 and Google Workspace (Section 27.3).

### 18.2 Platform Diagnostics

The platform MUST record:

- last successful `calendar:ingest-mailboxes` cycle;
- last successful `calendar:refresh-feeds` cycle;
- last successful `calendar:maintenance` cycle;
- number of emails processed/quarantined/deduplicated per cycle;
- stale claims recovered;
- application version.

### 18.3 Health Endpoint

```json
{
  "status": "healthy",
  "database": "ok",
  "mailbox_ingest_last_seen_at": "2026-08-10T11:00:00Z",
  "feed_refresh_last_seen_at": "2026-08-10T10:50:00Z",
  "stale_claims": 0,
  "version": "0.1.0"
}
```

The endpoint MUST NOT expose credentials, filesystem paths, private URLs, or tenant data.

### 18.4 Audit Events

Audit logging MUST include: login and failed-login events; tenant/calendar creation and update; mailbox ingest config creation, update, and secret rotation; calendar source creation and deletion; API key creation and revocation; and platform-admin support access to tenant data. Audit records MUST store actor, tenant, action, resource, timestamp, request identifier, and a redacted change summary.

***

## 19. Testing Strategy

### 19.1 Unit Tests MUST Cover

- `UID`/`SEQUENCE` reconciliation logic;
- `METHOD:CANCEL` and `METHOD:REPLY` handling;
- `RRULE` expansion, `EXDATE`, `RECURRENCE-ID` overrides;
- daylight-saving transitions;
- Exchange/Outlook `VTIMEZONE` mapping and its UTC fallback;
- deduplication by `UID` + `ORGANIZER` + `tenant_id`;
- secret redaction;
- SSRF URL/IP validation for feed sources;
- outbound feed structural conformance rules (Section 27.2).

### 19.2 Integration Tests MUST Cover

- tenant isolation across calendars, sources, and events;
- mailbox claiming and stale-claim recovery;
- end-to-end ingestion using a test double mailbox (Mailpit) with representative `.ics` fixtures (Google, Outlook, Apple, generic RFC 5545 samples);
- feed refresh against a mocked ICS/CalDAV endpoint;
- audit logging;
- retention cleanup.

### 19.3 End-to-End Tests MUST Verify That a User Can

1. log in;
2. select a tenant;
3. configure a mailbox ingest source;
4. send a test invite email into the Dockerized Mailpit mailbox;
5. run `calendar:ingest-mailboxes` and see the event appear;
6. send an updated invite (`SEQUENCE` incremented) and see the event update, not duplicate;
7. send a cancellation and see the event marked cancelled;
8. subscribe to the aggregated `webcal://` feed and verify its contents;
9. configure and refresh an external ICS feed source.

### 19.4 Security Tests MUST Include

- cross-tenant identifier substitution;
- private-IP/localhost/link-local blocking for feed URLs;
- redirect revalidation for feed fetches;
- IMAP credential encryption and redaction in logs and API responses;
- unauthorized calendar/source access attempts.

### 19.5 Compatibility Tests MUST Include

See Section 27.4 for the full Outlook 365 / Google Workspace conformance suite, including structural `VCALENDAR`/`VEVENT` assertions and a manual, release-gated subscription pass against both live platforms.

***

## 20. MVP Scope

### 20.1 Included

- Platform-admin bootstrap; tenant creation by platform admins; tenant memberships and roles.
- Calendars, calendar sources (email mailbox, ICS feed, read-only CalDAV).
- Email-based invite ingestion with `UID`/`SEQUENCE` reconciliation, cancellation and reply handling, deduplication, quarantine.
- Recurrence expansion, timezone normalization.
- Aggregated `webcal://` ICS feed output, conformant with Outlook 365 and Google Workspace import behavior (Section 27).
- Native calendar UI on the shared visual identity.
- REST API with API keys.
- Audit logging, retention cleanup, scheduler heartbeat.
- `en`/`pt-BR` internationalization.
- Fully Dockerized development environment and Docker-based release pipeline.
- Shared-hosting installation documentation.

### 20.2 Deferred

- Bidirectional/write CalDAV.
- Outbound invite composition/RSVP.
- Direct OAuth against Microsoft Graph/Google Calendar API as a primary ingestion path.
- Public self-registration and billing.
- Multipart non-calendar attachment handling.
- Native mobile app.
- Per-tenant custom retention beyond platform caps.
- Any mechanism promising faster-than-platform-native propagation into Google/Outlook (not achievable via ICS subscription; see Section 27.5).

***

## 21. MVP Acceptance Criteria

The MVP is complete only when all of the following are true:

### 21.1 User Workflow

A tenant user can log in, switch between `en` and `pt-BR`, configure a mailbox ingest source and an ICS feed source, receive and see a forwarded corporate invite appear on the aggregated calendar, observe correct handling of an update and a cancellation of that invite, and subscribe to the resulting `webcal://` feed from an external calendar client.

### 21.2 Reliability

- Overlapping ingestion cron processes do not create duplicate events for the same `UID`/`SEQUENCE`.
- A delayed or interrupted mailbox poll is recoverable via claim expiry without data loss.
- Deduplication of multi-forwarded invites works within and across configured mailboxes of the same tenant.

### 21.3 Security

- Cross-tenant access tests pass.
- IMAP and feed credentials are encrypted at rest and redacted from logs/audit trails.
- Private/local network destinations for feed URLs are blocked by default.
- Raw email bodies and unrelated attachments are not retained after extraction.

### 21.4 Operability

- The ingestion and feed-refresh scheduler heartbeats are visible.
- Quarantined emails and failing feed sources are discoverable from the dashboard.
- Development environment starts from a clean clone with a single Docker command, with no host PHP/Composer/Node/MySQL installation required.
- The Docker-based release process produces a deployable, checksummed zip for shared hosting.

### 21.5 Cross-Platform Compatibility

- The live aggregated feed has been manually subscribed and verified in both Outlook 365 (web) and Google Calendar per Section 27.4, with correct calendar name, timezone, and recurring-event display.
- The feed-generation conformance test suite (Section 27.4) passes in CI.

### 21.6 Quality Gate

- Required automated tests pass inside CI containers.
- Database migrations run cleanly on a clean database (MySQL and SQLite).
- No user-facing string is hard-coded outside the i18n system.
- No critical or high-severity dependency vulnerability remains without a documented mitigation.

***

## 22. Delivery Roadmap

### Phase 0: Foundation

- Repository and coding standards; Docker Compose dev stack (app, web, db, mailpit, node); Laravel application bootstrap; Vue + i18n bootstrap; authentication; tenant context; authorization policies; core migrations; CI compose profile and pipeline.

### Phase 1: Core Ingestion

- Calendars and calendar sources; mailbox ingest configs and secrets; `calendar:ingest-mailboxes`; `sabre/vobject` parsing; `UID`/`SEQUENCE` reconciliation; cancellation/reply handling; deduplication; quarantine flow; Mailpit-based local test harness.

### Phase 2: Mash-Up and Output

- ICS feed subscriptions; `calendar:refresh-feeds`; SSRF-guarded feed fetch; read-only CalDAV subscription support; aggregated `webcal://` feed with Outlook/Google conformance rules (Section 27); native calendar UI on the shared visual identity.

### Phase 3: Operability and Hardening

- Dashboard, heartbeat, audit logs, retention cleanup, security test suite, feed-conformance test suite, accessibility checks, shared-hosting deployment guide, Docker-based release pipeline (`rv release`).

### Phase 4: Portfolio Integration

- GrandpaSSOn SSO seam (`calendar:*` scopes); TaskConnect auxiliary scheduling seam; Jotter MCP read-only cross-linking seam.

### Phase 5: Post-MVP

- Read-only CalDAV endpoint; OAuth-based ingestion as an alternative source; per-tenant fairness controls; advanced retention policies.

***

## 23. Resolved MVP Decisions

| Topic | Decision |
|---|---|
| Primary ingestion path | IMAP polling of a dedicated mailbox/folder — guaranteed, independent of corporate IT approval |
| Secondary ingestion paths | Subscribed ICS feed URLs; read-only CalDAV subscriptions |
| Tenant creation | Platform-admin controlled; no public self-registration in the MVP |
| Delivery guarantee for feed refresh | At-least-once, idempotent by `UID`/`SEQUENCE` |
| CalDAV | Read-only consumption only in the MVP; write/publish is deferred |
| Backend framework | Laravel, matching Jotter and TaskConnect, unless an equivalent alternative is justified |
| Frontend | Vue 3 with TypeScript, Vite, Tailwind, and Vue I18n |
| Development environment | Docker Compose only; no host PHP/Composer/Node/MySQL |
| Production runtime | Plain PHP/MySQL/SQLite shared hosting; Docker is a dev/build tool only, not a runtime requirement |
| Visual identity | Reused unmodified from the shared portfolio specification |
| Sibling integrations | Optional seams (GrandpaSSOn, TaskConnect, Jotter), never hard runtime dependencies |
| Outlook/Google propagation latency | Documented as a platform-side limitation; no attempt to promise faster-than-native refresh |

***

## 24. Deferred Design Questions

- Should Rendezvous support OAuth-based ingestion (Microsoft Graph, Google Calendar API) as an alternative to IMAP once corporate IT grants access, and how should the two coexist for the same tenant?
- Should a future write-capable CalDAV endpoint allow Rendezvous to become a true two-way calendar hub rather than an aggregator?
- Should per-tenant retention for ingest logs be configurable beyond platform-defined caps?
- Should the Jotter MCP integration support write-back (e.g., auto-creating a prep note from an event) or remain strictly read-only?
- Should mailbox ingestion support multiple IMAP providers concurrently per tenant with per-source rate limiting?
- Should Rendezvous offer a push-based notification channel (email/webhook) as a documented complement for users who need faster-than-platform-refresh awareness of new/changed events, without claiming to fix Google/Outlook's own subscription latency?

***

## 25. Guidance for AI Coding Agents (Claude Code, Codex, and Equivalents)

This section exists specifically to make the specification directly actionable by autonomous or semi-autonomous coding agents.

### 25.1 Required Working Order

Agents SHOULD implement in this sequence:

1. Establish project conventions, Docker Compose dev stack, and automated test scaffolding.
2. Implement authentication and tenant context.
3. Implement tenant-isolated persistence (migrations for Section 13's data model).
4. Implement iCalendar parsing, `UID`/`SEQUENCE` reconciliation, and recurrence expansion as pure, unit-tested domain services — no HTTP/IMAP/DB coupling.
5. Implement SSRF validation and secret redaction as reusable services.
6. Implement the mailbox ingestion command with atomic claiming and stale-claim recovery, validated against the Mailpit-based local test harness.
7. Implement ICS feed and CalDAV read-only sync.
8. Implement the aggregated `webcal://` feed output as a dedicated `FeedExport` service, conformant with Section 27's Outlook/Google rules from the first implementation — do not treat conformance as a later hardening pass.
9. Implement the REST API per Section 16.
10. Implement the frontend flows on top of the shared visual identity tokens (do not invent new tokens or components without checking Jotter's `docs/visual-identity.md` first).
11. Add observability, retention, and deployment documentation, including the Docker-based release pipeline.
12. Wire the optional GrandpaSSOn/TaskConnect/Jotter integration seams behind feature flags, verifying the application remains fully functional with all three disabled.
13. Complete the end-to-end acceptance suite (Section 21), including the manual Outlook/Google subscription pass (Section 27.4).

### 25.2 Agent Constraints

Agents MUST NOT:

- weaken tenant scoping to simplify a query;
- fetch a user-supplied feed URL before SSRF validation;
- store IMAP or CalDAV credentials in plaintext, in task/config JSON, or in logs;
- treat email body/attachments as canonical event content — only the extracted `VCALENDAR` is canonical;
- discard a quarantined or failed ingestion silently — every failure MUST be observable;
- introduce Redis or long-running workers into the MVP;
- make GrandpaSSOn, TaskConnect, or Jotter a hard runtime dependency of core Rendezvous functionality;
- require host-installed PHP, Composer, Node, or MySQL for development — Docker Compose is the only supported development path;
- add framework shortcuts that bypass authorization policies;
- emit an aggregated feed with more than one `VCALENDAR` component, or with a top-level `METHOD` that would trigger Outlook's single-object interpretation (Section 27.2);
- claim or imply that the ICS subscription mechanism delivers near-real-time updates in Google Workspace or Outlook 365 (Section 27.5).

### 25.3 Change Discipline

For every implementation change, an agent SHOULD:

- identify the affected requirement section in this document;
- add or update tests first when practical;
- document any deliberate deviation from this specification;
- update API examples when contracts change;
- update migrations and seed data consistently;
- verify tenant isolation;
- run the relevant unit, integration, and frontend tests inside the Docker Compose stack before declaring completion.

### 25.4 Cross-Repository Reference Map for Agents

When an agent needs authoritative behavior for an integration point, it MUST consult the cited sibling repository rather than inferring behavior:

| Need | Authoritative source |
|---|---|
| SSO exchange payload shape, tenant/locale claims, PKCE flow | [`grandpasson`](https://github.com/suporterfid/grandpasson) → `docs/client-integration.md` |
| Service-client / machine-token creation and scopes | [`grandpasson`](https://github.com/suporterfid/grandpasson) → `docs/client-integration.md`, §5 |
| Cron executor claiming, retry policy, SSRF validation pattern | [`taskconnect`](https://github.com/suporterfid/taskconnect) → `docs/http-task-scheduler-spec.md` |
| REST API conventions, error envelope, idempotency headers | [`taskconnect`](https://github.com/suporterfid/taskconnect) → `docs/http-task-scheduler-spec.md`, §17 |
| Docker-only dev loop, release/zip packaging | [`jotter`](https://github.com/suporterfid/jotter) and [`taskconnect`](https://github.com/suporterfid/taskconnect) → root `README.md`, `scripts/` |
| Visual identity tokens, states, RTL/i18n structural rules | [`jotter`](https://github.com/suporterfid/jotter) → `docs/visual-identity.md`, `assets/brand/` |
| MCP read-only tool contract for note cross-linking | [`jotter`](https://github.com/suporterfid/jotter) → `docs/mcp.md` |

***

## 26. Outlook 365 and Google Workspace Import/Subscription Compatibility

This section is normative. It exists because the aggregated `webcal://` feed (Section 9.4) is only useful if Outlook 365 and Google Workspace can actually consume it correctly. Both platforms are strict, idiosyncratic, and undocumented in places, so compliance MUST be treated as a first-class requirement, not an assumption that "valid iCalendar is enough."

### 26.1 Why This Requires Explicit Requirements

Google Calendar and Outlook 365 do not implement RFC 5545 identically, and neither publishes a complete compatibility guarantee. Independent testing and Microsoft's own interoperability notes document concrete divergences that Rendezvous MUST account for at the feed-generation layer:

- Google Calendar polls subscribed `From URL` feeds on its own server-side schedule, commonly observed between roughly 8 and 24 hours, with no user-facing or publisher-facing way to force a refresh. Removing and re-adding the subscription is the only reliable way to force one immediate re-fetch.[^1][^2][^3][^4][^5]
- Outlook 365 (web and desktop) is comparatively faster but still not real-time, typically observed in the 1–4 hour range, occasionally up to 24 hours.[^6][^5]
- Both platforms treat subscribed calendars as strictly read-only, one-way mirrors — users cannot edit or delete individual instances from the subscribing side.[^7][^1]
- Outlook's iCalendar import logic (`MS-STANOICAL`, Microsoft's documented divergence from RFC 5545) applies specific first-32KB heuristics to decide whether a file is a calendar *folder* subscription or a single calendar *object* (invite), based on the presence of `VEVENT`, `X-WR-CALNAME`, and the `METHOD` property. Files with `METHOD: REQUEST|REPLY|CANCEL` in the first 32KB are treated as single invite objects, not folder subscriptions — this MUST NOT collide with the aggregated feed's own bulk export[^8].
- Outlook only imports `text/calendar` MIME parts whose `method` parameter is exactly `REQUEST`, `REPLY`, `CANCEL`, or `COUNTER` (case-insensitive); any other or missing `method` value causes Outlook to silently skip import of that MIME part.[^8]
- Outlook exports and expects exactly one `VCALENDAR` component per file/MIME part; on import, if multiple `VCALENDAR` components exist in one file, Outlook only uses the properties of the first one.[^9]
- `webcal://` is not a real protocol — it is a client-side convention that a calendar app rewrites to `https://` (or `http://`) before making an ordinary GET request. Google's "From URL" field only accepts `http`/`https` directly, not `webcal://`.[^7]
- Google Calendar subscription reliably requires the source calendar/feed to be publicly reachable without authentication; if the source requires a login, subscription fails in both Outlook and Google.[^10]
- `X-PUBLISHED-TTL` (a de facto standard honored by classic Outlook for Windows) and `REFRESH-INTERVAL;VALUE=DURATION` (the RFC 7986 standardized equivalent) are hints a feed can emit to request a faster poll cadence; Outlook enforces a floor around one hour regardless of the requested value, and Google Calendar does not honor either hint at all.[^7]

### 26.2 Functional Requirements — Feed Generation

The aggregated feed generator (Section 9.4) MUST:

- Emit exactly one `VCALENDAR` component per feed response, containing all `VEVENT` entries for the requested calendar/tenant combination — never multiple sequential `VCALENDAR` blocks, since Outlook only honors the first one.[^9]
- Omit any top-level `METHOD` property on the aggregated feed's `VCALENDAR` (i.e., do not set `METHOD:PUBLISH` unless explicitly required by a target integration), because Outlook's folder-vs-object heuristic treats a present, recognized `METHOD` value as a signal that the file is a single calendar object rather than a subscribable calendar folder.[^8]
- Include `X-WR-CALNAME` (calendar display name) and `X-WR-TIMEZONE` on the `VCALENDAR`, both because Outlook's own folder-detection heuristic checks for `X-WR-CALNAME`'s absence as one folder-vs-object signal, and because both Google and Outlook use these properties to label the subscribed calendar in their UI.[^8]
- Serve the feed exclusively over HTTPS at a stable, non-guessable, token-authenticated URL that requires no interactive login — since both Google and Outlook subscription flows fail or silently produce an empty calendar against a URL that redirects to a login page.[^10][^7]
- Return the feed with `Content-Type: text/calendar; charset=utf-8` and, where the hosting stack allows it, accurate `Last-Modified`/`ETag` HTTP headers reflecting the true last-change time of the underlying calendar, since this is the one lever documented to help Google's fetcher recognize a changed feed.[^11]
- Emit both `X-PUBLISHED-TTL` and the RFC 7986 `REFRESH-INTERVAL;VALUE=DURATION` property (e.g., `PT1H` for hourly) on the `VCALENDAR`, understanding and documenting that Outlook enforces its own floor (~1 hour) and Google ignores both hints entirely — this is a best-effort signal, not a guarantee.[^7]
- Keep the emitted feed reasonably bounded in size (e.g., excluding events far in the past beyond a configurable retention window) since very large feeds are reported to cause Google to skip or delay updates altogether.[^1]
- Validate every emitted `VEVENT` against baseline RFC 5545 structural requirements (mandatory `UID`, `DTSTAMP`, valid `DTSTART`; `DTEND` or `DURATION` but not both) before inclusion, and MUST exclude/quarantine (never emit) any internally malformed event rather than risk corrupting the entire feed for both target platforms.
- Provide the subscription URL in both forms — `webcal://` (for one-click "Add calendar" flows that support the scheme) and the equivalent `https://` URL (mandatory fallback, since `webcal://` is not itself fetchable and Google's "From URL" field requires `https://` or `http://` directly).[^7]
- Provide a documented one-click Google Calendar deep link using the `https://calendar.google.com/calendar/render?cid=<url-encoded-webcal-url>` pattern as a convenience UI affordance, while treating it as an unofficial, undocumented mechanism that MAY change without notice — never the only subscribe path offered.[^12]

### 26.3 Functional Requirements — UI and User Expectations

The UI (Section 9.4, Section 11) MUST:

- Explicitly disclose, at the point where the user copies the subscription URL, that Google Calendar typically takes 8–24 hours and Outlook 365 typically takes 1–4 hours (up to 24 hours in worst cases) to reflect changes, and that neither platform offers a manual "refresh now" control the user can rely on. This disclosure MUST NOT be buried in documentation only — it MUST appear inline in the subscription/share UI.[^5][^6][^1]
- Clearly label subscribed/exported calendars as read-only from the destination platform's perspective, setting the expectation that edits made in Google/Outlook to a subscribed event do not propagate back to Rendezvous or to the original corporate invite.[^1][^7]
- Warn the user, when an ingested event's timezone mapping is flagged ambiguous (Section 9.2), that this specific event is more likely to display incorrectly once re-imported into Outlook or Google, since both platforms are strict about IANA-vs-Windows timezone identifiers on import.
- Provide a "force re-subscribe" instruction (unsubscribe and re-add the feed URL) as the documented workaround for a stale Google Calendar subscription, since this is the only mechanism confirmed to force an immediate re-fetch.[^2][^4]

### 26.4 Additional Testing Requirements (extends Section 19)

Automated and manual test coverage MUST additionally include:

- A conformance test asserting the aggregated feed always contains exactly one `VCALENDAR` component regardless of how many source events/sources are merged.
- A conformance test asserting the aggregated feed never sets a top-level `METHOD` property that would trigger Outlook's calendar-object (rather than calendar-folder) interpretation, unless a future feature explicitly requires exporting a single-invite object.
- A conformance test asserting every emitted `VEVENT` has a mandatory `UID`, `DTSTAMP`, and valid `DTSTART`, and either `DTEND` or `DURATION` but never both.
- A manual acceptance pass, repeated before each release, that subscribes the live feed URL in both Outlook 365 (web) and Google Calendar ("Other calendars → From URL"), confirming the calendar name (`X-WR-CALNAME`), timezone, and at least one representative recurring event display correctly in each platform.
- A regression test verifying the feed endpoint rejects/redirects unauthenticated or expired-token requests instead of returning an HTML login page with a `200` status, since an HTML response silently breaks both platforms' subscription flow.[^10]

### 26.5 Explicit Non-Guarantee

Rendezvous MUST document, in user-facing help content and in `docs/deployment.md`, that near-real-time propagation into Google Workspace or Outlook 365 via the `webcal://`/ICS subscription mechanism is not achievable — this is a platform-side limitation of Google and Microsoft, not a defect in Rendezvous, and no client-side configuration can change it. Teams requiring faster propagation SHOULD be directed toward the deferred, post-MVP CalDAV or OAuth-based push integrations (Section 20.2, Section 24) rather than expecting the ICS subscription path to deliver low-latency updates.[^5][^7]

***

## 27. Conclusion of Feasibility

Building Rendezvous is technically viable and coherent with the existing portfolio: the stack, multi-tenancy pattern, cron execution model, design system, and identity broker are already validated in production across the three sibling projects. The competitive differentiator — email-based invite ingestion as a guaranteed entry path, independent of corporate IT permissions — has no published PHP/MySQL/SQLite equivalent today, making this a genuine niche gap in the OSS calendar ecosystem.

Full Dockerization of the development loop and release pipeline, while keeping the production runtime a plain PHP/MySQL/SQLite shared-hosting target, follows the exact precedent already set by Jotter and TaskConnect and requires no architectural risk-taking to adopt. The added Outlook 365/Google Workspace conformance requirements (Section 26) close the remaining gap between "technically valid iCalendar" and "actually importable by the two target corporate ecosystems," at the cost of a small, well-scoped `FeedExport` service and a release-gated manual verification step — a reasonable and necessary trade-off given the product's core value proposition.

---

## References

1. [Subscribe to an ICS Calendar in Google + Fix the Refresh Lag - Hetk](https://www.hetk.io/blog/how-to-subscribe-ics-calendar-google/) - Add any ICS or webcal URL to Google Calendar, step by step — plus the catches the docs skip: feeds l...

2. [Why Subscribed Calendars Don't Update Instantly (and ...](https://nocal.app/help/ics-and-subscriptions/why-subscribed-calendars-dont-update-instantly) - Learn why subscribed calendars don't update instantly, how refresh intervals differ for Google, Appl...

3. [How often does Google Calendar refresh ICS subscriptions?](https://usemooncal.com/en/guides/google-calendar-ics-refresh) - Google Calendar pulls ICS subscription feeds roughly every 8–24 hours and there is no manual refresh...

4. [My google calendar doesn't sync events from outlook (added by url).](https://support.google.com/calendar/thread/107999821/my-google-calendar-doesn-t-sync-events-from-outlook-added-by-url?hl=en-au)

5. [How often Apple and Google refresh subscribed calendars](https://calfeed.ai/learn/ics-refresh-rate-apple-google) - Google Calendar updates subscribed calendars every 12 to 24 hours. Apple checks about hourly, Outloo...

6. [Publishing Public Calendars to Be Subscribed in Google and ...](https://isuruiup.medium.com/publishing-public-calendars-to-be-subscribed-in-google-and-outlook-calendars-3e2f508a7260) - Keeping customers informed about upcoming bookings or events is crucial for businesses. One effectiv...

7. [What Is webcal? Subscription Calendar URLs Explained](https://addcal.co/blog/what-is-webcal-subscription-calendar-urls)

8. [[MS-STANOICAL]: [RFC5545] Section 8.1 iCalendar Media Type Registration](https://learn.microsoft.com/en-us/openspecs/exchange_standards/ms-stanoical/2637212b-2ddc-4706-8202-c796fb3f748b) - E0001: The specification states, The Calendaring and Scheduling Core Object Specification is intende...

9. [[MS-STANOICAL]: [RFC5545] Section 3.4, iCalendar Object](https://learn.microsoft.com/en-us/openspecs/exchange_standards/ms-stanoical/e3b4da2c-6f49-4e07-96c7-f8239b0bf35a) - V0051: The specification allows multiple iCalendar objects to be sequentially grouped together. Micr...

10. [How to Subscribe to a Web (ICS) Calendar in Outlook ...](https://www.onecal.io/blog/how-to-subscribe-to-a-web-ics-calendar-in-outlook) - Learn how to subscribe to web calendars and .ics calendars in the Outlook Calendar web client, the O...

11. [Google Calendar ICS Subscription Refresh Rate](https://www.usecarly.com/blog/google-calendar-ics-refresh-rate/) - Google Calendar refreshes subscribed ICS calendars roughly every 12–24 hours. Here's what that means...

12. [til/ics/google-calendar-ics-subscribe-link.md at main · simonw/til](https://github.com/simonw/til/blob/main/ics/google-calendar-ics-subscribe-link.md) - Today I Learned. Contribute to simonw/til development by creating an account on GitHub.

