# Roadmap / Backlog — Rendezvous 📅

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

## 🚀 Post-MVP Feature Backlog

The following capabilities are planned for post-1.0.0 iterations, prioritizing self-hosted simplicity and seamless shared hosting deployment.

### 1. Protocols & API Extensions
- [ ] **Bidirectional CalDAV Write Endpoint:** Expose a full CalDAV server endpoint allowing native desktop (Apple Calendar, Thunderbird) and mobile clients to create and edit events directly via CalDAV.
- [ ] **Direct OAuth 2.0 Graph & Google Ingestion:** Add direct API authorization for Microsoft Graph API and Google Calendar API endpoints as alternative non-IMAP ingestion paths.
- [ ] **Public Webhook Subscription API:** Allow external services to register HMAC SHA-256 signed webhooks (`X-Rendezvous-Signature`) for real-time calendar modification events.

### 2. Tenant & Administration Enhancements
- [ ] **Per-Tenant Ingestion Fairness Controls:** Implement rate limits and maximum email payload sizes per tenant to prevent mailbox flooding.
- [ ] **Advanced Audit Log Export:** Allow tenant admins to export audit logs in JSON/CSV formats for compliance auditing.
- [ ] **Custom Ingest Retention Rules:** Allow per-tenant configuration of raw email metadata retention periods (30 to 365 days).

### 3. Sibling Integrations
- [ ] **Jotter Deep Link Expansion:** Provide direct visual badges in Vue 3 calendar cards for linked Jotter meeting prep notes.
- [ ] **TaskConnect Automated Reminders:** Automatically schedule TaskConnect HTTP tasks for upcoming calendar events with customizable notification lead times.
