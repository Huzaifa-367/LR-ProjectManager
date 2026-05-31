# LR-ProjectManager — TCM Command Centre

Laravel + Inertia + React application for the **TCM Command Centre** (executive project management / command centre).

## Stack

- Laravel 11+, Fortify, Spatie permissions (platform roles)
- Inertia.js + React 19, Vite, Wayfinder, Tailwind v4, shadcn/ui

## Documentation

- [`DOCs/TCM-Command-Centre-Technical-Spec.md`](DOCs/TCM-Command-Centre-Technical-Spec.md) — full technical design
- [`DOCs/TCM-Command-Centre-Implementation-Guide.md`](DOCs/TCM-Command-Centre-Implementation-Guide.md) — build order and milestones
- [`DOCs/prototypes/TCM-Group-Dashboard.html`](DOCs/prototypes/TCM-Group-Dashboard.html) — HTML prototype

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate:fresh --seed
```

Demo users (password `12345678`):

- `admin@tcm.test` — super_admin
- `platform@tcm.test` — platform_admin

After login you land on **Organizations** (`/organizations`). Create an organization to enter the command centre shell.

## Development

```bash
composer dev
```

Runs the Laravel server, queue worker, log tail, and Vite dev server together.

## Production operations

The command centre relies on a **queue worker** and **scheduler** for mail, notifications, and exports.

### Queue worker

Process queued jobs (mail sends, CSV exports, scheduled notifications):

```bash
php artisan queue:work --tries=3
```

Use `redis` (or `database`) for `QUEUE_CONNECTION` in production. Horizon is optional for monitoring failed jobs.

### Scheduler

Register Laravel’s scheduler on the host (cron every minute):

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks (see `routes/console.php`):

| Command | Frequency | Purpose |
|---------|-----------|---------|
| `notifications:dispatch-scheduled` | Every minute | Deadline reminders and digests |
| `exports:purge-expired` | Daily | Remove expired export files |
| `audit:purge-ai-logs` | Daily | AI audit log retention (default 90 days) |
| `audit:purge-activity-logs` | Weekly | Activity log retention (default 365 days) |

Retention defaults can be overridden via:

- `COMMAND_CENTRE_AI_AUDIT_RETENTION_DAYS`
- `COMMAND_CENTRE_ACTIVITY_LOG_RETENTION_DAYS`

### Admin reports

Organization owners and admins can open **Settings → Reports** (`/organizations/{org}/reports`) for export job summaries and failed mail delivery logs. Requires the `org.notification-deliveries.index` permission.
