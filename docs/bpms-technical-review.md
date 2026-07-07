# بررسی فنی اولیه BPMS

## هدف

ساخت یک BPMS با این استک:

- PHP
- Laravel
- PostgreSQL
- پشتیبانی از BPMN 2.0

هدف این سند این است که قبل از شروع پیاده‌سازی، مسیر فنی کم‌ریسک و قابل توسعه را مشخص کند.

## جمع‌بندی سریع

پیشنهاد اصلی:

- `Laravel` برای پنل، API، احراز هویت، مجوزها، فرم‌ها، فایل‌ها و منطق کسب‌وکار
- `bpmn.io` برای طراحی و ویرایش BPMN داخل رابط وب
- `Operaton` به عنوان موتور اجرای BPMN
- `PostgreSQL` برای دیتابیس اصلی برنامه و همچنین دیتابیس/اسکیما جدا برای موتور فرآیند
- `Redis + Queue` برای jobها، اعلان‌ها و کارهای async
- `Docker Compose` برای اجرای local

## چرا bpmn.io؟

`bpmn.io` موتور اجرای فرآیند نیست. این ابزار برای این بخش‌ها عالی است:

- طراحی و ویرایش BPMN در مرورگر
- نمایش دیاگرام
- import/export فایل BPMN XML
- سفارشی‌سازی UI و properties panel

اگر بخواهیم طراح فرآیند داخل خود محصول داشته باشیم، `bpmn.io` بهترین انتخاب شروع است.

## چرا Camunda به تنهایی پاسخ مسئله نیست؟

بین `bpmn.io` و `Camunda` در واقع مقایسه مستقیم وجود ندارد:

- `bpmn.io` لایه طراحی است
- `Camunda` لایه اجرا و orchestration است

یعنی حتی اگر بعداً `Camunda` یا هر موتور دیگری داشته باشیم، باز هم می‌توانیم برای طراحی از `bpmn.io` استفاده کنیم.

## مقایسه گزینه‌های موتور اجرا

### گزینه 1: Camunda 8 Self-Managed

نقاط قوت:

- محصول شناخته‌شده و بالغ
- ابزارهای عملیاتی خوب
- امکان اجرای self-managed

نقاط ضعف برای پروژه ما:

- از نظر عملیاتی نسبتاً سنگین‌تر است
- برای معماری monolith مبتنی بر Laravel کمی بیش از حد پیچیده است
- برای استفاده production رایگان، انتخاب مطمئنی نیست

نتیجه:

برای PoC یا تیمی که بودجه لایسنس دارد خوب است، اما برای شروع BPMS رایگان و local-first پیشنهاد اول من نیست.

### گزینه 2: Flowable

نقاط قوت:

- متن‌باز و مناسب BPMN/CMMN/DMN
- REST API و قابلیت اجرای به صورت سرویس
- گزینه قابل اتکا برای سازمان‌ها

نقاط ضعف:

- همچنان یک موتور Java-based است
- برای تیمی که می‌خواهد هسته محصول Laravel-first باشد، integration آن نیازمند لایه هماهنگ‌کننده دقیق است

نتیجه:

گزینه دوم خوب و حرفه‌ای است. اگر بعداً روی DMN/CMMN خیلی تکیه کنیم، Flowable جدی‌تر می‌شود.

### گزینه 3: Operaton

نقاط قوت:

- متن‌باز
- سازگار با مدل‌های deployable مربوط به Camunda 7
- REST API، webappهای مدیریتی و دیتابیس‌های رابطه‌ای
- برای local و on-prem مناسب
- برای تیمی که Laravel را هسته سیستم نگه می‌دارد، integration ساده‌تر و قابل کنترل‌تری می‌دهد

نقاط ضعف:

- اکوسیستم آن از Camunda کوچک‌تر است
- بخشی از راه‌حل همچنان خارج از PHP اجرا می‌شود

نتیجه:

برای نیاز فعلی ما بهترین توازن بین رایگان بودن، اجرای local، سادگی استقرار و بلوغ فنی را دارد.

## چرا موتور BPMN را داخل خود Laravel از صفر نسازیم؟

از نظر فنی ممکن است، اما پیشنهاد نمی‌شود مگر اینکه بخواهیم فقط یک subset خیلی محدود از BPMN را پشتیبانی کنیم.

ریسک‌ها:

- اجرای واقعی BPMN 2.0 پیچیده است
- gatewayها، timer eventها، message eventها، subprocessها و compensation رفتارهای حساسی دارند
- audit trail، retry، incident handling و versioning زمان زیادی می‌گیرند

اگر بخواهیم "پشتیبانی واقعی BPMN" داشته باشیم، استفاده از موتور مستقل ریسک پروژه را خیلی کمتر می‌کند.

## معماری پیشنهادی

### 1. لایه Laravel

مسئول این بخش‌ها:

- احراز هویت و RBAC
- مدیریت tenant یا سازمان
- فرم‌ساز و ذخیره داده‌های فرم
- مدیریت فایل و ضمیمه
- API اصلی سیستم
- پنل مدیریت
- task inbox سفارشی
- audit سطح کسب‌وکار

### 2. لایه BPM Engine

مسئول این بخش‌ها:

- deploy کردن process definition
- versioning فرآیند
- start process instance
- نگهداری state و tokenها
- user taskها
- service task orchestration
- timer/message/signal handling
- history و runtime state

### 3. مرز بین دو لایه

Laravel نباید جای موتور فرآیند را بگیرد. بهتر است:

- business data در PostgreSQL برنامه بماند
- process variables فقط داده‌های مورد نیاز جریان را نگه دارند
- task completion، process start و event correlation از طریق adapter service انجام شود

## طراحی دیتابیس

پیشنهاد:

- یک دیتابیس PostgreSQL برای اپلیکیشن Laravel
- یک دیتابیس یا حداقل schema جدا برای موتور فرآیند

دلیل:

- جداسازی migrationها
- ساده‌تر شدن backup/restore
- کاهش coupling بین جداول runtime موتور و جداول دامنه کسب‌وکار

## پیشنهاد برای MVP

در فاز اول این قابلیت‌ها کافی است:

- طراحی، ذخیره و نسخه‌بندی BPMN
- انتشار فرآیند
- شروع فرآیند از UI یا API
- user task با فرم
- exclusive gateway
- parallel gateway
- timer event
- service task برای call به سرویس‌های داخلی Laravel
- inbox کارتابل
- history پایه
- audit log پایه

فعلاً این‌ها را عقب نگه می‌داریم:

- DMN
- multi-tenancy کامل
- event subprocess
- compensation
- call activityهای پیچیده
- process migration بین نسخه‌ها
- BPMN coverage کامل

## استقرار local پیشنهادی

برای توسعه local این سرویس‌ها کافی هستند:

- `app` برای Laravel
- `nginx` یا `caddy`
- `postgres`
- `redis`
- `queue`
- `operaton`
- `mailpit`
- `minio` برای فایل‌ها در صورت نیاز

## نکته مهم درباره این محیط

در این workspace این ابزارها آماده هستند:

- PHP
- Composer
- Docker
- Node.js

و این‌ها فعلاً local نصب نیستند:

- `psql`
- `java`

پس بهترین مسیر این است که PostgreSQL و موتور BPMN را containerized بالا بیاوریم و وابسته به نصب محلی Java نباشیم.

## تصمیم پیشنهادی نهایی

اگر بخواهیم همین حالا ساخت را شروع کنیم، پیشنهاد من این انتخاب است:

- `Laravel + PostgreSQL + Redis`
- `bpmn.io` برای مدل‌سازی
- `Operaton` برای اجرا
- `Docker Compose` برای محیط local

## ترتیب ساخت

### فاز 1

- scaffold پروژه Laravel
- Docker Compose
- PostgreSQL و Redis
- ساختار ماژولار اولیه
- auth و RBAC پایه

### فاز 2

- ادغام `bpmn.io`
- ذخیره BPMN XML
- نسخه‌بندی definitionها

### فاز 3

- ادغام با `Operaton`
- deploy/start/complete task
- inbox و process instance view

### فاز 4

- فرم‌ها
- service taskها
- timerها
- audit و history

## تصمیم باز

تنها تصمیمی که قبل از build باید قطعی شود این است:

- `Operaton` را به عنوان موتور پیش‌فرض جلو ببریم
- یا اگر ترجیح تو اکوسیستم Flowable است، معماری را همان اول روی Flowable ببندیم

پیشنهاد من: `Operaton`.
