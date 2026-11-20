# Product Steering — Rendezvous

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

## Overview
Rendezvous is an open-source, multi-tenant calendar mash-up platform. It aggregates events from multiple calendar sources — subscribed ICS feeds, read-only CalDAV subscriptions, and email-delivered meeting invites — into a single browsable calendar per tenant, and republishes the merged result as a subscribable ICS feed (`webcal://`).

## Primary Differentiator
Turns a dedicated corporate inbox or folder into a first-class calendar source by parsing `text/calendar` MIME parts and `.ics` attachments via IMAP polling, normalizing them into structured events, and merging them with the tenant's unified calendar.

## Sibling Portfolio Alignment
Rendezvous is designed as a sibling to three existing open-source projects by the same author:
- `suporterfid/jotter` (Markdown KB, Laravel + Vue 3, shared visual identity, MCP)
- `suporterfid/grandpasson-ce` (SSO broker for shared hosting, OAuth token exchange)
- `suporterfid/taskconnect` (Multi-tenant HTTP task scheduler, cron execution, SSRF protection)

Neighbor integrations (GrandpaSSOn SSO, TaskConnect scheduling, Jotter notes) are **optional seams, never hard runtime dependencies**.

## Core Product Guarantees
1. **Deployment simplicity over infrastructure sophistication:** Runs on PHP 8.2+ / MySQL / SQLite shared hosting with cron. No daemons, Redis, workers, or supervisors.
2. **Dedicated mailbox is a first-class calendar source:** IMAP polling + `sabre/vobject` iCalendar parsing + `UID`/`SEQUENCE` idempotency reconciliation.
3. **Safe defaults:** SSRF protection on feed fetches, encryption-at-rest for IMAP/CalDAV credentials, TLS verification.
4. **Strong tenant isolation:** Shared database with `tenant_id` scoping on every tenant table.
5. **Observable failures:** Quarantine folder/state for malformed invites; never discard failures silently.
6. **Outlook 365 & Google Workspace Conformance:** Aggregated `webcal://` feed MUST conform strictly to Outlook and Google import rules (single `VCALENDAR` block, no top-level `METHOD`, `X-WR-CALNAME` presence, etc.).
