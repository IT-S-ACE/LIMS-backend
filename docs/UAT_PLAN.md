# User Acceptance Test Plan

Release candidate: `1.0.0-rc.2`
UAT window: 15–17 August 2026  
Planned closure: 17 August 2026

## Entry criteria

- Backend migrations and `php artisan lims:release-check` pass.
- Backend automated tests pass with no Critical or High defect open.
- Frontend `npm run check:release` passes.
- UAT uses a separate database and the documented demo data only.
- A restorable pre-UAT backup exists.

## Test roles

| Role | Primary acceptance responsibility |
| --- | --- |
| Administrator | Settings, users, catalog, inventory, finance, audit, dashboard, final approval. |
| Receptionist | Patient registration, test request, sample registration, payment workflow. |
| Lab technician | Sample workflow, reagent stock adjustment, result entry and approval. |
| Patient | Own portal and published-result visibility only. |

## Critical end-to-end scenario

Record the request ID, sample number, report number, payment number, and relevant screenshots as
evidence.

1. Sign in as Receptionist using password and OTP.
2. Create a new patient with a unique email and phone.
3. Create a test request containing at least two catalog tests; apply internal/manual insurance if
   required by the scenario.
4. Register the required sample and record its barcode/QR identifier.
5. Sign out and sign in as Lab Technician.
6. Receive the sample, move it to In Progress, and confirm reagent consumption rules are available.
7. Enter valid results, include one boundary or critical-value case, and approve the results.
8. Confirm inventory was consumed once and the stock movement is traceable.
9. Generate the medical report and confirm the approved values, patient, request, and timestamps.
10. Sign in as Receptionist and record the internal payment; confirm the invoice balance/status.
11. Sign in as Administrator and confirm dashboard metrics reflect the workflow.
12. Confirm immutable audit events exist for the sensitive operations and pass integrity checking.
13. Sign in as the linked Patient and confirm only that patient's published information is visible.

## Role and negative tests

| Test | Expected result |
| --- | --- |
| Patient directly opens staff routes | Redirect or 403; no staff data disclosed. |
| Technician creates/deletes a catalog test | Control hidden and API returns 403. |
| Receptionist opens audit/settings | Redirect or 403. |
| Wrong OTP is submitted five times | OTP becomes unusable; new OTP required. |
| Password reset is attempted without verified OTP | Rejected. |
| Result approval is repeated | No duplicate inventory consumption. |
| Audit record update/delete is attempted | Rejected; audit remains unchanged. |
| Unknown password-reset email is submitted | Generic response; account existence not disclosed. |

## Non-functional acceptance

- Normal read operations complete within 3 seconds in the UAT environment.
- Critical operations complete within 5 seconds, excluding email-provider latency.
- Keyboard navigation exposes a visible focus indicator and reaches the skip-to-content link.
- Layout remains usable at 360 px width and at 200% browser zoom.
- A release build can be produced with LTR; an RTL direction smoke check does not break core layout.
- No Critical/High security or data-integrity defect remains open.
- Backup restoration is demonstrated in a separate recovery database.

## Defect severity and exit criteria

| Severity | Definition | UAT exit rule |
| --- | --- | --- |
| Critical | Data loss, security breach, system unavailable, incorrect medical report. | Zero open. |
| High | Critical workflow blocked or incorrect financial/inventory state. | Zero open. |
| Medium | Workaround exists; limited business impact. | Accepted owner and target date required. |
| Low | Cosmetic or minor usability issue. | May be deferred and documented. |

UAT passes only when every critical scenario passes, all evidence is attached, and the sign-off
template is approved.
