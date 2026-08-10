# Security Audit & Penetration Testing Checklist — Rendezvous 📅

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

This document details the security controls, automated test coverage, and penetration checklist for the **Rendezvous** platform.

---

## 1. Automated Security Controls & Test Matrix

| Threat Vector | Control Implemented | Automated Test Location | Status |
| :--- | :--- | :--- | :--- |
| **Cross-Tenant Data Leakage** | Database queries scoped explicitly by `tenant_id` at repository/Eloquent model level. | `tests/Feature/Security/TenantIsolationTest.php` | ✅ PASS |
| **SSRF (Server-Side Request Forgery)** | `SsrfGuardService` validates URLs and blocks `127.0.0.1`, `10.0.0.0/8`, `192.168.0.0/16`, `169.254.169.254` (AWS Metadata), loopback and private ranges. | `tests/Feature/Security/SsrfProtectionTest.php` | ✅ PASS |
| **IMAP Credential & Secret Leaks** | Credentials encrypted at rest via AES-256 (`secrets` table) and redacted in log payloads via `SecretEncryptionService`. | `tests/Feature/Security/SecretRedactionTest.php` | ✅ PASS |
| **Feed URL Token Guessing** | `webcal://` feed URLs use non-guessable, high-entropy 32-character UUID v4 access tokens. | `tests/Unit/Domain/Calendar/FeedExportServiceTest.php` | ✅ PASS |
| **Unauthenticated API Access** | Non-public endpoints require session authentication or valid API keys (`X-Api-Key`). | `tests/Feature/Security/TenantIsolationTest.php` | ✅ PASS |

---

## 2. Penetration Testing Checklist

- [x] **Tenant Scoping:** Verify `WHERE tenant_id = ?` is present on all SELECT/UPDATE/DELETE queries.
- [x] **SSRF Guard:** Verify HTTP redirect revalidation prevents SSRF bypass via 301/302 redirects to internal IP ranges.
- [x] **Credential Redaction:** Confirm passwords and tokens do not appear in Exception tracebacks or Laravel logs.
- [x] **SQL Injection:** All database queries use PDO Prepared Statements via Eloquent / Query Builder.
- [x] **XSS (Cross-Site Scripting):** Vue 3 template auto-escaping prevents script execution from forwarded invite titles or summaries.
