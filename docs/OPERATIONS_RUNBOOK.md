# Operations Runbook

## Production configuration checklist

Before deployment, confirm:

- `APP_ENV=production`, `APP_DEBUG=false`, and the public `APP_URL` uses HTTPS.
- `APP_KEY` is already set and is managed as a secret. Do not regenerate it during deployment.
- Database, mail, queue, and cache credentials are supplied from the deployment environment.
- `CORS_ALLOWED_ORIGINS` contains only the exact trusted HTTPS frontend origins.
- `SANCTUM_TOKEN_EXPIRATION` reflects the approved session lifetime.
- Testing OTP is disabled.
- The web server points to Laravel's `public` directory and blocks access to `.env`, logs, backups,
  and source-control metadata.

Run the non-strict readiness check in the release-candidate environment:

```bash
php artisan lims:release-check
```

Run the strict check only after production environment variables are loaded:

```bash
php artisan lims:release-check --strict
```

## Safe deployment sequence

Run each command separately from the backend project directory:

```bash
php artisan down --retry=30
```

```bash
php artisan optimize:clear
```

```bash
php artisan migrate --force
```

```bash
php artisan config:cache
```

```bash
php artisan route:cache
```

```bash
php artisan view:cache
```

```bash
php artisan up
```

Do not use `migrate:fresh`, `db:wipe`, `rollback`, or `key:generate` as part of a normal deployment.

## Post-deployment checks

```bash
php artisan migrate:status
```

```bash
php artisan route:list
```

```bash
curl --fail --show-error https://API_HOST/api/health/live
```

```bash
curl --fail --show-error https://API_HOST/api/health/ready
```

Then verify a real login with OTP, one role-restricted request, and one read-only dashboard request.
Record the returned `X-Request-ID` when investigating a failure.

Do not run demo seeders in production. They are restricted to `local` and `testing` environments.

## Workers and scheduler

If the selected queue driver is asynchronous, keep a supervised worker running and restart it after
deployment:

```bash
php artisan queue:restart
```

Run Laravel's scheduler once per minute through the platform scheduler:

```bash
php artisan schedule:run
```

## Backup and recovery

Take a verified database backup before migrations. For MySQL, use deployment-managed credentials
rather than embedding a password in shell history:

```bash
mysqldump --single-transaction --routines --triggers DB_NAME > lims-predeploy.sql
```

Restoration is a controlled incident action and should target an empty recovery database first:

```bash
mysql RECOVERY_DB_NAME < lims-predeploy.sql
```

Validate row counts, critical workflow records, audit-chain integrity, and application readiness in
the recovery environment before any production cutover.

## Incident triage

1. Capture the timestamp, authenticated role, endpoint, status code, and `X-Request-ID`.
2. Check the application and worker logs without exposing patient data in tickets or chat.
3. For readiness failures, verify database connectivity and `storage/framework` permissions.
4. For repeated 401 responses, verify token expiration and server clock synchronization.
5. For 403 responses, verify the route role matrix; do not work around it by weakening middleware.
6. If data integrity is uncertain, stop writes and escalate before attempting repair.
