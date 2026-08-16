# Medical Laboratory Management System (LIMS)

A Laravel-based Laboratory Information Management System (LIMS) designed to manage the complete laboratory workflow from patient registration and test requests to sample tracking, result approval, medical reports, billing, insurance, inventory, notifications, and audit logging.

Security and deployment references:

- [API Security and Operations Contract](docs/API_SECURITY_AND_OPERATIONS.md)
- [Operations Runbook](docs/OPERATIONS_RUNBOOK.md)
- [Security Acceptance Tests](docs/SECURITY_ACCEPTANCE_TESTS.md)
- [UAT Plan](docs/UAT_PLAN.md)
- [UAT Sign-off](docs/UAT_SIGN_OFF.md)
- [Go-Live Checklist](docs/GO_LIVE_CHECKLIST.md)
- [Handover and Closure](docs/HANDOVER_AND_CLOSURE.md)
- [Release Manifest](docs/RELEASE_MANIFEST.md)
- [Release-critical OpenAPI Contract](docs/openapi.yaml)

## Overview

The system is organized around the laboratory's main business workflows:

- Authentication and user management
- Patient management
- Test catalog management
- Test request and test item management
- Sample registration, QR/barcode tracking, rejection, cancellation, and status updates
- Test result entry, editing, approval, and validation
- Automatic reagent consumption after result approval
- Medical report generation and PDF export
- Patient result notifications
- Insurance companies and coverage rules
- Invoices, payments, refunds, and patient balances
- Financial dashboards and reports
- Reagent inventory and stock movement management
- Low-stock and expiry alerts
- Audit logs
- Laboratory system settings

## Main Workflow

```text
Patient
   |
   v
Test Request
   |
   +---- Test Request Items ----> Tests
   |
   +---- Samples ----> Test Results
   |                    |
   |                    +---- Approve Result
   |                              |
   |                              +---- Consume Reagents
   |                              +---- Generate Medical Report
   |                              +---- Notify Patient
   |
   +---- Invoice ----> Payments / Refunds
   |
   +---- Insurance / Coverage Rules
```

## Architecture

The backend follows a service-oriented Laravel structure with clear separation of responsibilities:

```text
HTTP Request
    |
    v
Controller
    |
    v
Form Request / Validation
    |
    v
Service
    |
    v
Eloquent Models
    |
    v
Database
```

API responses are exposed through Laravel API Resources where applicable.

Business logic is kept in Services rather than Controllers. Controllers are responsible for receiving requests, calling the appropriate service, and returning API responses.

## Project Modules

### Authentication

- Login
- Logout
- Registration
- OTP verification
- Forgot password
- Reset password
- Profile management
- Sanctum API authentication

### Patients

Patients contain the core patient information used by test requests, reports, payments, and notifications.

### Tests

The test catalog contains:

- Test name
- Price
- Unit
- Reference range

### Test Requests

A test request belongs to one patient and may contain multiple test request items.

Each test request can also have:

- Optional insurance company
- Samples
- One invoice
- One medical report

### Samples

Samples support:

- Sample number
- Barcode
- Sample type
- QR code
- Status tracking
- Received timestamp
- Rejection and cancellation

### Test Results

A test result belongs to a sample and a test request item.

Result data includes:

- Result number
- Value
- Unit
- Reference range
- Flag
- Status
- Approval state
- Approving user
- Approval timestamp

### Medical Reports

Medical reports are generated for a complete test request rather than for a single result.

A report is linked to one test request and contains the patient's completed laboratory results.

Supported operations include:

- Generate report
- View report details
- Export PDF
- Notify patient
- Export report data as CSV

### Insurance

Insurance management contains:

- Insurance companies
- Default coverage percentage
- Coverage rules
- Test-specific coverage codes
- Maximum covered amount
- Applying insurance to a test request
- Coverage calculation

### Financial Management

The financial workflow is:

```text
Test Request
    |
    v
Invoice
    |
    +---- Invoice Items
    |
    +---- Payments
    |
    +---- Refunds
```

Each test request has one invoice.

Payment processing resolves the invoice through the test request rather than requiring the client to submit an invoice ID.

### Inventory

Reagents contain:

- Code
- Name
- Category
- Current stock
- Minimum stock
- Expiry date
- Unit price

Each test can use one or more reagents through the `reagent_test` relationship.

The default reagent consumption per test is `1`.

After a result is approved, the system automatically consumes the configured reagents and records stock transactions.

### Notifications

Notifications support:

- Result-ready patient notifications
- Low-stock alerts
- Expiry warnings
- In-app notifications
- Email notifications
- Read state tracking

### Audit Logs

Sensitive operations can be recorded with:

- User
- Entity type
- Entity ID
- Action
- Old values
- New values
- Reason
- IP address
- Timestamp

## Database Structure

The main business tables are:

```text
users
user_otps
patients
tests
insurance_companies
coverage_rules
test_requests
test_request_items
samples
test_results
medical_reports
invoices
invoice_items
payments
refunds
reagents
reagent_test
stock_transactions
notifications
audit_logs
system_settings
```

Laravel infrastructure tables such as `failed_jobs`, `password_reset_tokens`, and `personal_access_tokens` are also included.

## Requirements

Make sure the following are installed before starting the project:

- PHP
- Composer
- MySQL or another supported database configured by the project
- Laravel-compatible PHP extensions
- Git

## Installation

### 1. Clone the project

```bash
git clone <YOUR_REPOSITORY_URL>
cd <PROJECT_DIRECTORY>
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the environment file

Linux / macOS:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Windows CMD:

```cmd
copy .env.example .env
```

### 4. Configure `.env`

Set the database connection according to your local environment.

Example for MySQL:

```env
APP_NAME="Medical Laboratory Management System"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lims
DB_USERNAME=root
DB_PASSWORD=
```

Configure mail settings as required if email notifications are enabled.

### 5. Generate the application key

```bash
php artisan key:generate
```

### 6. Create the database

Create the database specified in `.env` before running migrations.

For example, create:

```text
lims
```

### 7. Run migrations and seed the project

For a clean development database:

```bash
php artisan migrate:fresh --seed
```

This recreates the database and loads the development seed data for:

- Users
- Patients
- Tests
- Insurance companies
- Coverage rules
- Test requests
- Test request items
- Samples
- Test results
- Medical reports
- Invoices
- Invoice items
- Payments
- Refunds
- Reagents
- Reagent-test mappings
- Stock transactions
- Notifications
- Audit logs
- User OTPs
- System settings

### 8. Create the public storage link

```bash
php artisan storage:link
```

This is required for generated public PDF files and other files stored on the public disk.

### 9. Clear cached configuration

Recommended after changing `.env` or configuration:

```bash
php artisan optimize:clear
```

### 10. Start the application

```bash
php artisan serve
```

The default local URL is:

```text
http://127.0.0.1:8000
```

## Authentication

Protected API endpoints use Laravel Sanctum.

After obtaining an authentication token, send it with API requests:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
Content-Type: application/json
```

Do not expose production access tokens in source control.

## Example Development Accounts

The seeders create development users with the password:

```text
password
```

Typical seeded accounts include:

```text
admin
reception
technician
ahmad
```

Use the actual email/username values defined in `UserSeeder.php`.

Do not use these credentials in production.

## API Overview

The backend exposes API groups for the main modules, including:

```text
/user
/dashboard
/user/patients
/user/test-requests
/user/samples
/user/test-results
/user/medical-reports
/user/financial-reports
/user/payments
/user/inventory-reports
/user/inventory
/user/inventory-dashboard
/user/reagents
/user/insurance-companies
/user/coverage-rules
/user/audit-logs
/user/notifications
/user/tests
```

Check `routes/api.php` for the complete and current endpoint list.

## Important API Workflows

### Create a Test Request

```text
Patient -> Test Request -> Test Request Items
```

The request calculates its total from the selected tests.

### Create Invoice

When a test request is created, its invoice is created for the request.

```text
TestRequest -> Invoice
```

### Process Payment

The payment endpoint uses the test request to resolve the invoice:

```text
TestRequest -> Invoice -> Payment
```

The client does not need to send the invoice ID.

### Approve Result

The result approval workflow performs several operations:

```text
Approve TestResult
        |
        +--> Consume linked reagents
        |
        +--> Record stock transactions
        |
        +--> Generate medical report for the TestRequest
        |
        +--> Notify patient
        |
        +--> Record audit log
```

### Inventory Consumption

Every test can be linked to its reagent requirements through `reagent_test`.

By default:

```text
1 completed/approved test = 1 reagent unit consumed
```

The configured relationship remains data-driven, so the mapping can support additional reagents later.

## Postman

A Postman collection can be used to test the API.

Recommended environment variables:

```text
base_url = http://127.0.0.1:8000/api
 token = <SANCTUM_TOKEN>
```

For protected endpoints, use:

```http
Authorization: Bearer {{token}}
```

## File Storage

Generated medical reports are stored under Laravel's public filesystem disk, for example:

```text
storage/app/public/reports/
```

Run:

```bash
php artisan storage:link
```

to expose the public storage directory through `public/storage`.

## Useful Artisan Commands

Install dependencies:

```bash
composer install
```

Generate application key:

```bash
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

Resume local/testing demo data without duplicating populated modules:

```bash
php artisan db:seed
```

`migrate:fresh` destroys all tables and must never be used against an existing or production
database.

Run a specific seeder:

```bash
php artisan db:seed --class=TestSeeder
```

Clear Laravel caches:

```bash
php artisan optimize:clear
```

Create storage symlink:

```bash
php artisan storage:link
```

Start local server:

```bash
php artisan serve
```

## Development Notes

- Do not put business logic directly inside controllers.
- Validate incoming data through Form Request classes.
- Use Services for business workflows.
- Keep Eloquent relationships aligned with the actual database foreign keys.
- Use API Resources when a stable response contract is required.
- Keep sensitive operations authenticated and audited.
- Do not commit `.env`, API tokens, generated secrets, or production credentials.
- When the database schema changes, update the migrations, models, requests/resources, services, seeders, and ERD together.

## Common Setup Problems

### `Field 'unit' doesn't have a default value`

The `tests.unit` column is required. Include `unit` when creating a test.

### `Field 'code' doesn't have a default value`

The affected table has a required `code` column. Check the relevant seeder/request payload.

### `Field 'payment_number' doesn't have a default value`

If `payment_number` is required, either provide it in direct query-builder seed inserts or create payments through `Payment::create()` so the model event can generate it.

### `Undefined relationship`

Check that the Eloquent relationship name matches the actual model relationship and foreign key. For example, the system uses:

```php
TestRequest::invoice()
```

because each test request has one invoice.

### `Undefined variable` in a PDF view

Make sure the variable name passed to `Pdf::loadView()` matches the variable used in the Blade template exactly, including letter case.

## License

This project is developed for the Medical Laboratory Management System project. Add the final project-specific license here if required by your institution or organization.
