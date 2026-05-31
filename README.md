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

## Development

```bash
composer dev
```
