# Stage 03 - Process Definitions

## Goal

This stage adds:

- process definition storage
- BPMN XML persistence
- explicit versioning
- publish / archive behavior
- admin-only process library pages

## Routes

- `/process-definitions`
- `/process-definitions/create`
- `/process-definitions/{id}`
- `/process-definitions/{id}/versions/create`

Only admins should access these pages.

## Run

```bash
php artisan migrate
php artisan serve
```

Use the same port shown by `php artisan serve`.

## Test

1. Login with `admin@bpms.test` / `password`
2. Open `/process-definitions`
3. Create a process family with a key such as `invoice-approval`
4. Use `Publish version` to save version `v1`
5. Open the saved definition and confirm the BPMN XML is shown
6. Click `Create next version`
7. Save another version and confirm the new record becomes `v2`
8. If you publish `v2`, confirm the previously published version becomes `archived`

## Expected result

- Process definitions are stored in PostgreSQL
- Each process family keeps multiple versions
- Only one version per process key stays `published` at a time
- Operators should still be blocked from this admin-only area
