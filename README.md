# OrgFlow

A BPMS (Business Process Management System) built with **Laravel**, **PostgreSQL** and **BPMN 2.0**. Laravel owns the UI, authentication, roles and business logic; [Operaton](https://operaton.org) is the BPMN execution engine, and [bpmn.io](https://bpmn.io) (`bpmn-js`) is the in-browser process modeler.

## Features

- **Auth & RBAC** – login, register, logout, and `admin` / `operator` roles.
- **Process definitions** – create, version and publish BPMN processes.
- **BPMN modeler** – design and edit diagrams in the browser with `bpmn-js`.
- **Operaton integration** – deploy published definitions to the engine over its REST API.
- **Process runtime** – start process instances and inspect them (variables, activity history).
- **Task inbox** – list, claim, release and complete user tasks.
- **Task forms** – forms generated from engine variables (string, boolean, integer, double, enum, date) with a JSON fallback.

## Architecture

| Component | Role |
|-----------|------|
| Laravel 13 (PHP 8.3+) | Web UI, auth, RBAC, orchestration |
| PostgreSQL 17 | Application database |
| Redis 7 | Queues |
| Operaton + PostgreSQL 15 | BPMN engine and its own database |
| Vite, Tailwind CSS 4, bpmn-js | Frontend build and modeler |

## Requirements

- PHP 8.3+ with the `phpredis` and `pdo_pgsql` extensions
- Composer
- Node.js and npm
- Docker and Docker Compose

## Getting started

```bash
git clone git@github.com:nima-shokri/orgflow.git
cd orgflow

# Start PostgreSQL, Redis, and Operaton
docker compose up -d

# Install and configure the app
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Frontend assets
npm install
npm run build

# Run
php artisan serve
```

Or run `composer setup` for the install steps, and `composer dev` to start the server, queue worker, log viewer and Vite together.

### Local ports

| Service | Host port |
|---------|-----------|
| Laravel (`artisan serve`) | 8000 |
| PostgreSQL (app) | 55432 |
| Redis | 56379 |
| Operaton (REST: `/engine-rest`, web: `/operaton`) | 58080 |

### Default users (from the seeder)

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@bpms.test` | `password` |
| Operator | `operator@bpms.test` | `password` |

> Change or remove these accounts before any non-local deployment.

### Configuration

Settings live in `.env` (see [.env.example](.env.example)). The Operaton connection is controlled by `OPERATON_BASE_URL`, `OPERATON_WEB_URL`, `OPERATON_USERNAME`, `OPERATON_PASSWORD`, `OPERATON_TIMEOUT` and `OPERATON_DEFAULT_HISTORY_TTL`.

## Usage

1. Sign in as `admin@bpms.test`.
2. Go to **Process definitions**, create a definition and model it in the BPMN editor.
3. **Publish** it, then **Deploy** it to Operaton.
4. **Start** an instance and follow it under **Runtime → Instances**.
5. Work user tasks from the **Tasks** inbox: claim, fill in the form, complete.

Health checks: `GET /` and `GET /health`.

## Testing

```bash
composer test
```

## Project structure

```
app/Http/Controllers/     Auth, process definitions, runtime, tasks, Operaton
app/Services/Operaton/    REST client for the Operaton engine
resources/views/          Blade views (auth, process-definitions, runtime, tasks)
database/                 Migrations and seeders
docs/                     Technical review and per-stage build notes (stage-01 … stage-10)
```

## Documentation

The project was built in stages; see [docs/](docs/) for the [technical review](docs/bpms-technical-review.md) and the notes for each stage (bootstrap, auth/RBAC, process definitions, BPMN modeler, Operaton integration, runtime, task inbox, ownership, forms, history).

## License

Licensed under the [Apache License 2.0](LICENSE).
