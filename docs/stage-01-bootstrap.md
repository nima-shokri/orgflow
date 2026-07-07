# Stage 01 - Bootstrap

## هدف

بالا آمدن اسکلت اولیه پروژه با این موارد:

- Laravel
- PostgreSQL
- Redis
- یک خروجی ساده برای تست

## سرویس‌های این مرحله

- `postgres`
- `redis`

## Run

```bash
docker compose up -d
php artisan key:generate
php artisan migrate
php artisan serve
```

## Test

پس از اجرا از همان پورتی استفاده کن که `php artisan serve` در ترمینال نشان می‌دهد.

مثلاً اگر خروجی این بود:

```text
Server running on [http://127.0.0.1:8011]
```

باید این آدرس‌ها را تست کنی:

- `http://127.0.0.1:8011/`
- `http://127.0.0.1:8011/health`

اگر سرور روی `8000` بالا آمد، همان `8000` را استفاده کن.

## خروجی مورد انتظار

در `/` یک JSON با وضعیت `ok` برمی‌گردد.

در `/health` یک JSON با اطلاعات پایه اپلیکیشن برمی‌گردد که باید شامل این مقادیر باشد:

- `database = pgsql`
- `queue = redis`
- `status = healthy`

## Ports

برای جلوگیری از تداخل با سرویس‌های موجود سیستم:

- PostgreSQL host port: `55432`
- Redis host port: `56379`
