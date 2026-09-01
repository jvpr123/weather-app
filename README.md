# WeatherLens

WeatherLens is a weather dashboard built as a Laravel 13 modular monolith with Inertia, Vue 3, TypeScript, Tailwind CSS, PostgreSQL, and Redis.

## Requirements

- Docker Desktop (or another Docker engine with Compose)
- Node.js 22+
- npm 11+

PHP and Composer are provided by Laravel Sail for normal development.

## First-time setup

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
npm install
npm run dev
```

Open `http://localhost` after the containers and Vite development server are running.

## Daily development

```bash
./vendor/bin/sail up -d
npm run dev
```

Useful Sail commands:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan tinker
./vendor/bin/sail artisan test
./vendor/bin/sail down
```

PostgreSQL is available to the application at `pgsql:5432`; Redis is available at `redis:6379`. The default cache store is Redis.

## Quality checks

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
npm run lint
npm run type-check
npm run build
```

The PHP tests use Pest. They are isolated from the Sail database through an in-memory SQLite connection.
