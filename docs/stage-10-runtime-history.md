# Stage 10 - Runtime History

## Goal

This stage extends the runtime detail screen so Laravel can show more than just the instance header and active tasks:

- load process variables for one Operaton process instance
- load activity history for the same instance
- keep the page usable even if one of the history panels fails

At the end of this stage, the runtime detail page becomes the first practical execution trace screen inside Laravel.

## Run

```bash
docker compose up -d postgres redis operaton-postgres operaton
php artisan migrate
php artisan serve
```

If Stage 09 is already working in your environment, no extra infrastructure is required.

## Local URLs

Use the exact host/port printed by `php artisan serve`.

If Laravel prints:

```bash
Server running on [http://127.0.0.1:8000]
```

then the main test URLs for this stage are:

- Login: `http://127.0.0.1:8000/login`
- Runtime explorer: `http://127.0.0.1:8000/runtime/instances`
- Runtime detail page: `http://127.0.0.1:8000/runtime/instances/{instanceId}`
- Task inbox: `http://127.0.0.1:8000/tasks`

If Laravel is running on another port such as `8011`, replace only the `8000` part in the Laravel URLs above.

## Test

1. Open `http://127.0.0.1:8000/login`
2. Login with an admin user
3. Open `http://127.0.0.1:8000/runtime/instances`
4. Start a deployed process definition
5. Confirm Laravel redirects to `http://127.0.0.1:8000/runtime/instances/{instanceId}`
6. On that page confirm you still see the instance summary and active tasks section
7. Confirm a new `Process variables` section is visible
8. Confirm a new `Activity timeline` section is visible
9. If the process is waiting on a user task, open the task and complete it with either generated form inputs or the advanced JSON box
10. Return to the runtime detail page and refresh
11. Confirm the variables panel now shows values such as `approved`, `comment`, `amount`, or whatever your process produced
12. Confirm the activity timeline shows BPMN steps such as start events and user tasks in time order

## Notes

- If Operaton returns no variables yet, the variables table can legitimately stay empty.
- If one history endpoint fails, the runtime detail page should still load and show a warning instead of crashing the whole screen.
- This stage does not add a new URL. It upgrades the existing runtime detail page.

## Expected result

- Laravel can read per-instance process variables from Operaton
- Laravel can read per-instance activity history from Operaton
- the runtime detail page now gives you a much more useful execution trace for manual debugging
