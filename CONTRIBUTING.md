# Contributing Guidelines — Rendezvous 📅

> *This project is part of the [IROH Initiative](https://github.com/suporterfid/iroh). See the main hub for manifesto, branding, and ecosystem index.*

---

Thank you for your interest in contributing to **Rendezvous**! As part of the **IROH Initiative** ("It Runs On Hostinger"), we welcome community contributions, bug reports, and feature enhancements.

## 🍵 IROH Initiative Governance

Centralized governance rules, architectural principles, visual identity specs, and community codes of conduct are defined at the main meta-repository:
👉 **[suporterfid/iroh CONTRIBUTING.md](https://github.com/suporterfid/iroh/blob/main/CONTRIBUTING.md)**

---

## 🛠️ Local Development Setup

To work on Rendezvous locally, ensure you have **Docker Desktop** installed. Host installations of PHP, Node, or MySQL are not required.

1. **Clone the repository:**
   ```bash
   git clone https://github.com/suporterfid/rendezvous.git
   cd rendezvous
   ```

2. **Start local Docker environment:**
   - **Windows (PowerShell):** `.\scripts\rv.ps1 up`
   - **Linux / macOS:** `./scripts/rv.sh up`

3. **Verify endpoints:**
   - **Web Application:** `http://localhost:8080`
   - **Mailpit Web UI:** `http://localhost:8025`

---

## 🧪 Testing Guidelines

Before opening a pull request, run the automated test suite inside Docker:

```powershell
# Windows PowerShell
.\scripts\rv.ps1 test

# Linux / macOS
./scripts/rv.sh test
```

### Mandatory Requirements for PR Approval:
- All unit and feature tests must pass with zero errors.
- Strict tenant data isolation must be preserved on all new models/endpoints.
- Any outbound feed fetch logic must enforce SSRF guard checks (`SsrfGuardService`).
- User-facing text must include translation keys in both `en` and `pt-BR` locale files (`frontend/src/i18n/locales/`).
- Code must adhere to the **MIT License**.

---

## 📜 Pull Request Process

1. Fork the repository and create your feature branch (`git checkout -b feature/my-new-feature`).
2. Make your changes adhering to project architectural rules.
3. Verify that all tests pass.
4. Commit using **Conventional Commits** (e.g. `feat: add CalDAV endpoint`, `fix: reconcile cancellation sequence`).
5. Push to your branch and open a Pull Request against `main`.
