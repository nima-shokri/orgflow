# Stage 08 - Task Ownership

## Goal

This stage adds the first ownership controls around live Operaton user tasks:

- claim an unassigned task for the current Laravel user
- release a task that is currently assigned to you
- filter the inbox by `all`, `mine`, or `unassigned`
- block task completion when the task is assigned to someone else

The current assignee identifier is the Laravel user's email address.

## Run

```bash
docker compose up -d postgres redis operaton-postgres operaton
php artisan migrate
php artisan serve
```

If Stage 07 is already working, no extra infrastructure is required.

## Local URLs

Use the exact host/port printed by `php artisan serve`.

If Laravel prints:

```bash
Server running on [http://127.0.0.1:8000]
```

then the main test URLs for this stage are:

- Login: `http://127.0.0.1:8000/login`
- Task inbox: `http://127.0.0.1:8000/tasks`
- My tasks scope: `http://127.0.0.1:8000/tasks?scope=mine`
- Unassigned scope: `http://127.0.0.1:8000/tasks?scope=unassigned`
- Task detail page: `http://127.0.0.1:8000/tasks/{taskId}`
- Runtime explorer: `http://127.0.0.1:8000/runtime/instances`

If Laravel is running on another port such as `8011`, replace only the `8000` part in the Laravel URLs above.

## Test

1. Open `http://127.0.0.1:8000/login`
2. Login with an admin user
3. Start a process instance that pauses on a user task
4. Open `http://127.0.0.1:8000/tasks`
5. Confirm the task appears as `unassigned`
6. Click `Claim`
7. Confirm the page shows the task as assigned to your email address
8. Open `http://127.0.0.1:8000/tasks?scope=mine`
9. Confirm the claimed task appears in the `mine` scope
10. Open the task detail page
11. Confirm the ownership panel now shows `Owned by you`
12. Optionally click `Release task`
13. Confirm the task returns to the `unassigned` state
14. Claim it again
15. Complete the task
16. Confirm Laravel redirects back to the runtime instance page and the task is no longer active

## Route note

These actions are form actions, not page URLs:

- `Claim`
- `Release`
- `Complete task`

That means each action is sent to Laravel as a POST request after you open the relevant page first.

## Expected result

- Laravel can reserve an unassigned Operaton task for the current user
- Laravel can release a task owned by the current user
- the inbox can be focused to `mine` or `unassigned`
- a task assigned to another user cannot be completed from your current session
