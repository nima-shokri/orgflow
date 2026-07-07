# Stage 05 - Operaton Integration

## Goal

This stage adds:

- an `operaton` runtime container
- a dedicated PostgreSQL container for the engine
- Laravel-side engine health visibility
- deployment of published BPMN definitions to the runtime engine

In this project, `Deploy to Operaton` means:

- take a published BPMN definition from Laravel
- send it to the Operaton runtime over REST
- make that process available to start and execute in the engine

To keep deployment friction low in the MVP, the app automatically injects a default
`historyTimeToLive` into the BPMN XML during deployment if the model does not already define one.
You can change that default with `OPERATON_DEFAULT_HISTORY_TTL`.

## Services

- `operaton-postgres`
- `operaton`

`operaton-postgres` is internal-only in this stage and does not expose a host port.
Only the Operaton web/API port is published to the host.

## Run

```bash
docker compose up -d postgres redis operaton-postgres operaton
php artisan migrate
php artisan serve
```

If the frontend assets are already built from the previous stage, you do not need to rebuild them for this stage.

## Default local URLs

- Laravel login: `http://127.0.0.1:8000/login`
- Laravel admin: `http://127.0.0.1:8000/admin`
- Laravel process library: `http://127.0.0.1:8000/process-definitions`
- Laravel Operaton dashboard: `http://127.0.0.1:8000/operaton`
- Operaton web app: `http://127.0.0.1:58080/operaton`
- Operaton REST API: `http://127.0.0.1:58080/engine-rest`

Use the exact host/port printed by `php artisan serve`. If Laravel is served on another port such as
`8011`, replace only the `8000` part in the Laravel URLs above.

If you copied an older config that used `/operaton/engine-rest`, update it to `/engine-rest`.

Default login for the Operaton Docker image in this setup:

- `demo` / `demo`

## Test

1. Open `http://127.0.0.1:8000/login`
2. Login with `admin@bpms.test` / `password`
3. Open `http://127.0.0.1:8000/operaton`
4. Confirm the engine status becomes `UP`
5. Confirm the page shows the reported engine version
6. Open `http://127.0.0.1:8000/process-definitions`
7. Open one published definition, for example `http://127.0.0.1:8000/process-definitions/1`
8. Click `Deploy to Operaton`
9. Return to `http://127.0.0.1:8000/operaton`
10. Confirm the runtime definitions table now includes your process key

## Route note

`Deploy to Operaton` is a form action, not a page URL.

That means:

- you open the detail page with a normal GET request
- then you click the button
- Laravel sends a POST request in the background to deploy the definition

## Expected result

- Laravel can reach Operaton over REST
- Published BPMN definitions can be deployed from the admin UI
- The local process definition keeps the runtime deployment ID and definition ID
- The Operaton dashboard shows deployed runtime definitions
