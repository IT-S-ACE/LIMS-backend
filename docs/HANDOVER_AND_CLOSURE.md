# Handover and Project Closure

## Handover package

- Backend and frontend source bundles with complete Git history.
- Source ZIP archives and incremental patches.
- Environment-variable examples without credentials.
- Database migrations, backup/restore procedure, and operations runbook.
- API security contract, role matrix, UAT plan, sign-off, and Go-Live checklist.
- Automated test commands and manual critical-path test evidence.
- Administrator, Receptionist, Lab Technician, and Patient training scenarios.

## Training agenda

1. Secure login, OTP, password reset, and session handling.
2. Patient, request, sample, result, report, and payment workflow.
3. Reagent inventory, lots, consumption rules, and alerts.
4. Dashboard interpretation and role-specific visibility.
5. Audit search, request correlation, integrity status, and incident evidence.
6. Backup, readiness checks, deployment, smoke test, and escalation.

## Early support plan

- Support window begins immediately after Go-Live.
- Critical production issues trigger write suspension and the incident procedure.
- Every issue records severity, request ID, affected workflow, owner, and resolution.
- Medium/Low UAT exceptions retain the agreed owner and due date.

## Known scope exclusions

The following remain future work and are not release blockers: external Insurance API, laboratory
device integration, WhatsApp PDF delivery, multi-branch support, multi-store inventory, multiple
cashboxes, and advanced external analytics. Payment remains internal and insurance remains
internal/manual for this release.

## Closure record

- [ ] Release artifacts delivered and verified.
- [ ] UAT and Go-Live decisions signed.
- [ ] Administrator and operational training completed.
- [ ] Credentials transferred through an approved secret channel.
- [ ] Backup/restore ownership transferred.
- [ ] Known exceptions and support ownership accepted.
- [ ] Lessons learned recorded.
- [ ] 100% of agreed WBS scope completed or formally accepted as an exception.

Project closure date: **17 August 2026**

