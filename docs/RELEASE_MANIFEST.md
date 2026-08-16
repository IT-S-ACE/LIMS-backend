# Release Manifest — 1.0.0-rc.2

This release candidate implements EP-12: UAT, Deployment & Project Closure.
Production Go-Live remains blocked until `UAT_SIGN_OFF.md` and
`GO_LIVE_CHECKLIST.md` are completed and approved.

## Delivery mapping

| Task | Delivery | Verification |
| --- | --- | --- |
| T-064 | Backend schema/release hardening | `php artisan test` and `php artisan lims:release-check` |
| T-065 | Frontend production and accessibility gate | `npm run check:release` |
| T-066 | Critical laboratory workflow fixture | `CriticalWorkflowIntegrityTest` |
| T-067 | Role-based UAT and acceptance | `UAT_PLAN.md` and `UAT_SIGN_OFF.md` |
| T-068 | Backup, restore, health, smoke and rollback | `OPERATIONS_RUNBOOK.md` and `GO_LIVE_CHECKLIST.md` |
| T-069 | Training, handover and closure | `HANDOVER_AND_CLOSURE.md` |

## Release gates

- Zero open Critical or High defects.
- All migrations applied and release check passes.
- Backup and restore rehearsal recorded.
- Admin, receptionist and lab technician UAT accepted.
- Health endpoints and frontend smoke test pass after deployment.
- Rollback owner, trigger and artifact are confirmed.

## Scope boundary

The release does not add external insurance APIs, analyzer/device integration,
WhatsApp delivery, multi-branch/store/cashbox operation, or external advanced
analytics. Payments and insurance remain the existing internal/manual flows.
