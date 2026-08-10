# Outlook 365 & Google Workspace Live Feed Conformance Protocol

This document defines the release-gated verification protocol required before every production release of Rendezvous, enforcing strict conformance with Outlook 365 and Google Calendar feed import behavior.

---

## 1. Automated Conformance Suite

Before initiating manual testing, run the automated test suite inside the Docker container:

```bash
.\scripts\rv.ps1 test
# Or in Linux/macOS: ./scripts/rv.sh test
```

Expected output:
- `FeedExportServiceTest`: Passes single `VCALENDAR` block check, `X-WR-CALNAME` presence, and top-level `METHOD` omission check.
- `IcalendarParserServiceTest`: Passes iCalendar parsing and `RRULE` expansion.
- `EventReconciliationServiceTest`: Passes `UID` + `SEQUENCE` idempotency and `METHOD:CANCEL` status handling.

---

## 2. Outlook 365 (Web) Live Subscription Protocol

1. Open Outlook Web (`https://outlook.office.com/calendar/`).
2. Click **Add Calendar** > **Subscribe from web**.
3. Paste the generated Rendezvous HTTPS feed URL (e.g. `https://your-domain.com/feed/export?token=xyz...`).
4. Set Calendar Name to `Rendezvous Test`.
5. **Assertions to Verify:**
   - [ ] Outlook accepts the URL without error (rejects if `401`/`403`/HTML login is returned).
   - [ ] The calendar appears under "Subscribed Calendars" as a **folder**, NOT a single event invite object (proves top-level `METHOD` was omitted).
   - [ ] Events display with correct summary, location, UTC-to-local timezone conversion, and recurrence rules.

---

## 3. Google Calendar Live Subscription Protocol

1. Open Google Calendar (`https://calendar.google.com/`).
2. Next to **Other calendars**, click **+** > **From URL**.
3. Paste the HTTPS feed URL or use the `https://calendar.google.com/calendar/render?cid=webcal%3A%2F%2F...` affordance.
4. **Assertions to Verify:**
   - [ ] Google Calendar fetches and adds the feed.
   - [ ] Calendar title matches `X-WR-CALNAME` value (`Rendezvous Test`).
   - [ ] Recurring events expand properly across dates.
   - [ ] Status disclosure note appears in UI warning users that Google Calendar refresh cadence is 8–24 hours.
