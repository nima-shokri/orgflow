# Stage 07 - Task Inbox

## Goal

This stage adds the first Laravel-side user task handling flow:

- list active tasks from Operaton
- inspect one task in detail
- complete a task from Laravel
- optionally send simple completion variables as JSON

At this stage, the inbox is still intentionally simple:

- it is admin-only
- it shows live active tasks directly from Operaton
- it does not yet implement claim/unclaim or per-user queue ownership

## Run

```bash
docker compose up -d postgres redis operaton-postgres operaton
php artisan migrate
php artisan serve
```

If Stage 06 is already working in your environment, no extra infrastructure is required.

## Local URLs

Use the exact host/port printed by `php artisan serve`.

If Laravel prints:

```bash
Server running on [http://127.0.0.1:8000]
```

then the main test URLs for this stage are:

- Login: `http://127.0.0.1:8000/login`
- Admin dashboard: `http://127.0.0.1:8000/admin`
- Runtime explorer: `http://127.0.0.1:8000/runtime/instances`
- Task inbox: `http://127.0.0.1:8000/tasks`
- Task detail page: `http://127.0.0.1:8000/tasks/{taskId}`
- Operaton web app: `http://127.0.0.1:58080/operaton`

If Laravel is running on another port such as `8011`, replace only the `8000` part in the Laravel URLs above.

## Test

1. Open `http://127.0.0.1:8000/login`
2. Login with `admin@bpms.test` / `password`
3. Open a published and deployed process definition
4. Start a new process instance that pauses on a user task
5. After the redirect to the runtime instance detail page, confirm the `Active tasks` table shows at least one task
6. Click `Open task`
7. Confirm you arrive on `http://127.0.0.1:8000/tasks/{taskId}`
8. Review the task metadata and linked process information
9. Optionally enter completion variables such as:

```json
{"approved": true, "comment": "Looks good"}
```

10. Click `Complete task`
11. Confirm Laravel redirects back to the related runtime instance page
12. Confirm the previously active task is no longer active
13. Open `http://127.0.0.1:8000/tasks`
14. Confirm the inbox no longer shows the completed task

## Route note

`Complete task` is a form action, not a page URL.

That means:

- you first open the task detail page with a normal GET request
- then you click the button
- Laravel sends a POST request to complete the task in Operaton

## Variable note

Completion variables currently support:

- string
- number
- boolean
- null

Nested arrays and nested objects are not supported in this stage.

## Expected result

- Laravel can list active user tasks from Operaton
- Laravel can inspect one task in detail
- Laravel can complete a task and push simple variables into the process context
- the runtime instance view and the task inbox now connect the execution flow end-to-end
