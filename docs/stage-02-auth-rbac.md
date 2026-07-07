# Stage 02 - Auth and Basic RBAC

## Goal

This stage adds:

- login
- register
- logout
- user roles
- an admin-only route

## Roles

- `admin`
- `operator`

New registrations are created as `operator`.

## Seeded users

- `admin@bpms.test` / `password`
- `operator@bpms.test` / `password`

## Run

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Use the same port shown by `php artisan serve`.

## Test

1. Open `/login`
2. Login with `operator@bpms.test`
3. Confirm `/dashboard` works
4. Open `/admin` and confirm access is denied for operator
5. Logout
6. Login with `admin@bpms.test`
7. Confirm `/admin` opens successfully
8. Optionally open `/register` and create a new operator account

## Expected result

- Operators can sign in and access `/dashboard`
- Operators cannot access `/admin`
- Admins can access `/admin`
