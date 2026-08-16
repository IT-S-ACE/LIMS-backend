# API Security and Operations Contract

This document defines the security-sensitive conventions for the MedLab LIMS API. The route
source of truth remains `routes/api.php`.

## Base URL and authentication

- API prefix: `/api`
- Protected endpoints use Laravel Sanctum bearer tokens.
- Tokens expire after `SANCTUM_TOKEN_EXPIRATION` minutes (480 by default).
- Authentication and password-recovery endpoints are limited to five requests per minute for the
  same normalized email and client IP combination.
- Passwords must contain at least eight characters, including letters and numbers.
- OTP values expire after ten minutes, are accepted at most five failed times, and are stored as
  keyed hashes rather than plaintext.

Send authenticated requests with:

```http
Authorization: Bearer <token>
Accept: application/json
```

## Request correlation and security headers

Clients may send a UUID in `X-Request-ID`. The API returns that value when valid, or generates a
new UUID when it is absent or invalid. The same value is available to the audit subsystem.

Every response includes defensive browser headers including `X-Content-Type-Options`,
`X-Frame-Options`, `Referrer-Policy`, and `Permissions-Policy`. API responses are marked
`Cache-Control: no-store, private`. HTTPS production responses also include HSTS.

## Health endpoints

| Method | Path | Authentication | Purpose |
| --- | --- | --- | --- |
| `GET` | `/api/health/live` | None | Confirms that the application process is alive. |
| `GET` | `/api/health/ready` | None | Checks the database and writable framework storage. Returns 503 if unavailable. |

Health responses intentionally expose no credentials, SQL, filesystem path, or exception detail.

## Authentication and password recovery

| Method | Path | Notes |
| --- | --- | --- |
| `POST` | `/api/user/login` | Validates credentials and sends a login OTP. |
| `POST` | `/api/user/verifyOTP` | Verifies the login OTP and issues a Sanctum token. |
| `POST` | `/api/user/resendOTP` | Invalidates the old OTP and sends a new one. |
| `POST` | `/api/user/forgot-password` | Always returns a generic response to prevent account discovery. |
| `POST` | `/api/user/verify-reset-password-otp` | Verifies the six-digit recovery OTP. |
| `POST` | `/api/user/reset-password` | Requires a previously verified, unexpired OTP; revokes all sessions after success. |
| `POST` | `/api/user/logout` | Revokes the current authenticated token. |

Test-only authentication bypass endpoints are not registered. A fixed OTP may be enabled only in
the `local` or `testing` environment for explicitly configured test email addresses.

## Role matrix

| Module | Administrator | Receptionist | Lab technician | Patient |
| --- | --- | --- | --- | --- |
| Operational dashboard | Read | Read | Read, no revenue | No |
| Patients | Manage | Manage | No | Own portal only |
| Test requests | Manage | Manage | Read | No |
| Samples | Manage | Create/read/workflow | Read/workflow | No |
| Test results | Manage/approve | No | Manage/approve | Own published results only |
| Test catalog | Manage | Read | Read | No |
| Reagent inventory | Manage | No | Read/adjust stock | No |
| Finance and insurance | Manage | Payment workflow where granted | No | No |
| Audit and system settings | Manage | No | No | No |

Authorization is enforced by API middleware. Hiding a frontend navigation item is not considered an
access-control mechanism. Denied role checks return HTTP 403 and create an immutable audit event.

## Response and error conventions

Most business endpoints use the following envelope:

```json
{
  "code": "S00",
  "message": "Operation completed successfully.",
  "server_time": "2026-08-15T10:00:00Z",
  "payload": {}
}
```

Common error codes:

| Code | Meaning | Typical HTTP status |
| --- | --- | --- |
| `E002` | Validation failed | 400 or 422 |
| `E004` | Authentication required or invalid | 401 |
| `E007` | Authenticated but forbidden | 403 |

Unexpected internal exceptions must be logged server-side and returned without stack traces,
database statements, credentials, or other implementation details.

## CORS configuration

Allowed frontend origins are configured through `CORS_ALLOWED_ORIGINS` as a comma-separated list.
Production must list exact HTTPS origins; wildcard origins are not part of the default contract.
