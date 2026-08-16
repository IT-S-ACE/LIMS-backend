# Go-Live Checklist

## Go/No-Go gates

- [ ] UAT sign-off completed.
- [ ] Zero open Critical/High defects.
- [ ] Backend and frontend release commits recorded.
- [ ] Production secrets are configured outside source control.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, and testing OTP is disabled.
- [ ] Exact HTTPS CORS origin and frontend API URL are configured.
- [ ] Pre-deployment database backup completed and restoration tested separately.
- [ ] Migration status reviewed and rollback decision owner available.
- [ ] Maintenance window and stakeholder communication confirmed.

## Deployment evidence

| Evidence | Value |
| --- | --- |
| Backend commit |  |
| Frontend commit |  |
| Release tag |  |
| Backup file/reference |  |
| Backup restore result |  |
| Migration output |  |
| `lims:release-check --strict` output |  |
| Frontend `check:release` output |  |
| Smoke test output |  |
| Deployment owner |  |
| Deployment timestamp |  |

## Production verification

- [ ] `/api/health/live` returns HTTP 200.
- [ ] `/api/health/ready` returns HTTP 200.
- [ ] Login and OTP succeed with a real authorized account.
- [ ] Dashboard loads for Administrator and hides revenue from Lab Technician.
- [ ] One read-only patient/test/sample lookup succeeds.
- [ ] One role-denied request returns 403 and is audited.
- [ ] Report generation/download works without exposing another patient's data.
- [ ] Logs contain no stack trace or secret in API responses.

## Decision

- [ ] GO — all gates passed.
- [ ] NO-GO — restore service and follow the rollback procedure.

Decision owner: ____________________  Date/time: ____________________

