# LR-ProjectManager

Laravel + React application (replica of [PPE-SiteGuard](https://github.com/Huzaifa-367/PPE-SiteGuard) without the `Mobile/` Flutter app).

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate
```

## Notes

- `Mobile/` and `node_modules/` are excluded from this repository.
- Copy `.env` from your environment or configure `.env.example` values locally.
