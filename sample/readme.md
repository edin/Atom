# Atom Sample App

This sample app lives inside the framework repository and consumes the local framework package through Composer's path repository support.

It demonstrates:

- `Application::services()`, `modules()`, `components()`, `pages()`, and `bootstrap()`
- page discovery through `Application::pages()`
- `.atom.html` page templates
- page layout composition through a component class
- native `.atom.php` component templates with `$component` and `$context`
- API controllers through router attributes
- SQLite database services
- model classes with `Model::query()`, `find()`, `save()`, and relations
- migrations and seeders through console commands

## Run

```powershell
composer install
copy .env.example .env
php atom migrate:fresh
php atom db:seed
php -S 127.0.0.1:8021 -t public public/server.php
```

Open:

```text
http://127.0.0.1:8021
```

## Useful Commands

```powershell
php atom help
php atom migrate:status
php atom migrate:fresh
php atom db:seed
php atom make:migration create_posts
php atom make:seeder seed_posts
```

Database settings are read from `.env`:

```env
DB_DRIVER=sqlite
DB_DATABASE=storage/atom_sample.sqlite
DB_HOST=localhost
DB_PORT=
DB_USERNAME=
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

## Structure

```text
app/
├── Application.php
├── Components/
│   ├── Layout.php
│   └── Layout.atom.php
├── Controllers/
│   └── ApiController.php
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Models/
└── Pages/
```

Browser pages are implemented as page classes plus adjacent `.atom.html` templates.
Controllers are used only for API-style endpoints.
