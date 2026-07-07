# Stage 06 - Process Runtime

## Goal

This stage adds the first runnable runtime layer on top of the Operaton integration:

- start a process instance from Laravel
- browse recent process instances
- inspect one process instance in detail
- show active tasks for that instance

At this stage, Laravel is still not completing user tasks yet. We are only proving that:

- the definition can be deployed
- the engine can start it
- Laravel can read back runtime and history data

## Run

```bash
docker compose up -d postgres redis operaton-postgres operaton
php artisan migrate
php artisan serve
```

If you are already running Stage 05 successfully, no extra infrastructure is required for Stage 06.

## Local URLs

Use the exact host/port printed by `php artisan serve`.

If Laravel prints:

```bash
Server running on [http://127.0.0.1:8000]
```

then the main test URLs for this stage are:

- Login: `http://127.0.0.1:8000/login`
- Process library: `http://127.0.0.1:8000/process-definitions`
- Example process detail page: `http://127.0.0.1:8000/process-definitions/1`
- Runtime explorer: `http://127.0.0.1:8000/runtime/instances`
- Runtime detail page after start: `http://127.0.0.1:8000/runtime/instances/{instanceId}`
- Operaton web app: `http://127.0.0.1:58080/operaton`
- Operaton REST API: `http://127.0.0.1:58080/engine-rest`

If Laravel is running on another port such as `8011`, replace only the `8000` part in the Laravel URLs above.

## Test

1. Open `http://127.0.0.1:8000/login`
2. Login with `admin@bpms.test` / `password`
3. Open `http://127.0.0.1:8000/process-definitions`
4. Open a published definition that is already deployed to Operaton, for example `http://127.0.0.1:8000/process-definitions/1`
5. In the `Runtime execution` panel, click `Start process instance`
6. Confirm you are redirected to an address like `http://127.0.0.1:8000/runtime/instances/{instanceId}`
7. Confirm the instance detail page shows:
   - instance ID
   - process definition details
   - active tasks if the BPMN currently waits on a user task
8. Open `http://127.0.0.1:8000/runtime/instances`
9. Confirm the recent instances table now includes the new instance

## Route note

`Start process instance` is a form action, not a page URL.

That means:

- you open the process definition detail page first
- then you click the button
- Laravel sends a POST request to start the instance

If the BPMN goes directly to the end event, the instance detail page may show no active task.
If the BPMN pauses on a user task, you should see that task listed in the `Active tasks` table.

## Expected result

- Laravel can start deployed BPMN definitions in Operaton
- Laravel can read recent runtime/history records from the engine
- one process instance detail page shows active task information
- the admin panel now has a basic runtime explorer
