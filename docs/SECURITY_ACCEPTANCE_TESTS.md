# Security Acceptance Tests

Run these tests after applying the migration and before approving the release candidate.

## Automated checks

```bash
php artisan optimize:clear
```

```bash
php artisan migrate
```

Expected new migration:

```text
2026_08_15_000008_harden_otp_storage ... DONE
```

```bash
php artisan test --filter=SecurityHardeningTest
```

```bash
php artisan test
```

## Manual acceptance scenarios

### 1. Health and request correlation

1. Open `GET /api/health/live` without authentication.
2. Confirm HTTP 200 and `status=ok`.
3. Confirm response headers contain `X-Request-ID`, `X-Content-Type-Options: nosniff`, and
   `X-Frame-Options: DENY`.
4. Open `GET /api/health/ready` and confirm HTTP 200 with database and storage checks set to `ok`.

### 2. Removed authentication bypasses

1. Send `POST /api/user/login-test`.
2. Send `POST /api/user/verifyOTPTest`.
3. Confirm both endpoints return HTTP 404.

### 3. Password recovery

1. From the frontend login page, select **Forgot password**.
2. Submit an active user's email and confirm the UI advances to OTP entry.
3. Submit a wrong OTP five times and confirm it can no longer be used.
4. Request a fresh OTP, verify it, and choose a password with at least eight characters, letters,
   and numbers.
5. Confirm the old password fails, the new password works, and any previous bearer token is no
   longer accepted.
6. Attempt to call the reset endpoint without verifying an OTP and confirm it is rejected.
7. Submit an unknown email and confirm the initial response does not reveal whether the account
   exists.

### 4. Role boundaries

Use one account for each role and verify both the visible controls and direct API access:

| Scenario | Expected result |
| --- | --- |
| Patient opens `/patients` or calls `/api/user/patients` | Redirect/HTTP 403; denied audit event created. |
| Technician reads `/tests` | Allowed. |
| Technician creates, edits, or deletes a test | Controls hidden and API returns 403. |
| Technician opens inventory and adjusts stock | Allowed. |
| Technician adds/deletes a reagent or edits consumption rules | Controls hidden and API returns 403. |
| Receptionist manages patients and test requests | Allowed. |
| Receptionist opens audit, settings, finance reports, or inventory | Redirect/HTTP 403. |
| Administrator performs each operation | Allowed subject to business validation. |

### 5. Notification privacy

1. Authenticate as a patient and list notifications.
2. Confirm only notifications linked to that patient's record are returned.
3. Try to mark another patient's notification as read by ID and confirm HTTP 403.
4. Authenticate as a lab technician and confirm patient-specific notifications are not returned.

### 6. Production frontend behavior

1. Run `npm run build` in the frontend project.
2. Serve the production build.
3. Confirm demo accounts and their shared password are not displayed on the login page.
4. Confirm a 403 response shows a permission error without logging the user out.
5. Confirm a 401 response clears the expired session and returns the user to login.

