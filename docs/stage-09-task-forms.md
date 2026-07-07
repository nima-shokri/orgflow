# Stage 09 - Task Forms

## Goal

This stage upgrades task completion from raw JSON entry to form-driven execution:

- load task form variables from Operaton
- render supported variable types as Laravel form inputs
- submit generated form values back to Operaton
- keep the advanced JSON box as an optional fallback for extra scalar variables

The current generated form supports these variable families:

- string
- boolean
- integer / long
- double
- enum
- date as plain text input

## Run

```bash
docker compose up -d postgres redis operaton-postgres operaton
php artisan migrate
php artisan serve
```

If Stage 08 is already working, no extra infrastructure is required.

## Local URLs

Use the exact host/port printed by `php artisan serve`.

If Laravel prints:

```bash
Server running on [http://127.0.0.1:8000]
```

then the main test URLs for this stage are:

- Login: `http://127.0.0.1:8000/login`
- Task inbox: `http://127.0.0.1:8000/tasks`
- Task detail page: `http://127.0.0.1:8000/tasks/{taskId}`
- Runtime explorer: `http://127.0.0.1:8000/runtime/instances`

If Laravel is running on another port such as `8011`, replace only the `8000` part in the Laravel URLs above.

## Test

1. Open `http://127.0.0.1:8000/login`
2. Login with an admin user
3. Start a process instance that pauses on a user task
4. Open the task detail page from the inbox or runtime instance page
5. Confirm the `Complete task` section now renders generated inputs when Operaton returns form variables
6. Fill the generated fields
7. Optionally add extra scalar values in the `Advanced completion variables JSON` box
8. Click `Complete task`
9. Confirm Laravel redirects back to the related runtime instance page
10. Confirm the task is no longer active

## Notes

- If Operaton returns no explicit form variables, the page falls back to the advanced JSON box.
- Nested arrays and nested objects are still not supported in this stage.
- `Start process instance` and `Complete task` remain form actions, not page URLs.

## Expected result

- Laravel can read task form variables from Operaton
- Laravel can render form inputs for common scalar field types
- Laravel can submit those generated form values back to the task
- the task detail page now behaves more like a real BPMS task form screen
