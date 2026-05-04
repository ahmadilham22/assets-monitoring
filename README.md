# Monitoring TIK

A web-based asset monitoring application built with Laravel 10. It helps an organization's IT/TIK unit catalog its fixed and moved assets, track their physical location, generate QR codes for tagging, and produce reports that can be shared publicly via a scannable link.

## Features

- **Authentication** — local login plus Unila SSO integration.
- **Role-based access** — `super_admin` middleware gates all data-master configuration screens.
- **Data Master**
  - Categories & Sub Categories
  - Locations & Specific (Sub) Locations
  - Divisions, Units, Procurements
  - User management
- **Asset Management**
  - Fixed Assets: full CRUD, bulk delete, BMN/serial-number updates, persisted filter state
  - Moved Assets
  - Excel import with downloadable template
  - Per-asset and per-location QR code generation, plus bulk QR code download as ZIP
- **Monitoring** — dashboard with summary widgets and a monitoring workspace.
- **Reports**
  - Authenticated report listing & export
  - Public report pages (`/show-public/{id}`, `/list-public`) reachable by scanning an asset's QR code, no login required
- **History tracking** — asset changes are recorded in a `histories` table.

## Tech Stack

- **Framework:** Laravel 10 (PHP ^8.1)
- **Frontend build:** Vite
- **Database:** MySQL/MariaDB (any Laravel-supported driver works)
- **Key packages**
  - `yajra/laravel-datatables-oracle` + `-buttons` — server-side data tables
  - `maatwebsite/excel` — Excel import/export
  - `simplesoftwareio/simple-qrcode` — QR code generation
  - `realrashid/sweet-alert` — flash notifications
  - `laravel/sanctum` — API token auth
  - `unila/sso` — Unila Single Sign-On

## Requirements

- PHP 8.1 or higher
- Composer 2.x
- Node.js 18+ and npm
- A relational database (MySQL/MariaDB recommended)

## Getting Started

```bash
# 1. Clone and enter the project
git clone <repo-url> monitoring-tik
cd monitoring-tik

# 2. Install dependencies
composer install
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Configure DB credentials in .env, then run migrations
php artisan migrate

# (Optional) seed initial data if seeders are present
php artisan db:seed

# 5. Storage symlink (so uploaded files / QR codes are served publicly)
php artisan storage:link

# 6. Run the app
php artisan serve
npm run dev
```

The app will be available at `http://127.0.0.1:8000`.

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Auth/                  # Login, logout, SSO
│   ├── DataAssets/             # FixedAsset, MovedAsset
│   ├── DataMaster/             # Category, SubCategory, Location,
│   │                           # Division, Procurement, Unit, User
│   ├── Monitoring/             # Dashboard, Monitoring
│   └── Report/                 # Reports + public-facing views
└── Models/
    ├── DataAsset/              # FixedAsset
    └── DataMaster/             # Category, SubCategory, Location,
                                # SpecialLocation, Division, Unit,
                                # Procurement, User, History
database/migrations/            # Schema for all tables above
routes/web.php                  # All web routes
```

All business logic lives in controllers — this project intentionally avoids the Repository/Service pattern to keep the flow direct and easy to follow.

## Key Routes

| Area          | Path prefix        | Notes                                |
| ------------- | ------------------ | ------------------------------------ |
| Dashboard     | `/`                | Auth required                        |
| Data Master   | `/data-master/*`   | `super_admin` only                   |
| Fixed Assets  | `/data-assets/fixed` | Import, export template, QR download |
| Moved Assets  | `/data-assets/asset-moved` |                                |
| Monitoring    | `/monitoring`      |                                      |
| Reports       | `/report`          | Auth required                        |
| Public report | `/show-public/{id}`, `/list-public` | No auth — scanned via QR |

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
