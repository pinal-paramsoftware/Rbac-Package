# Laravel RBAC Suite

An enterprise-grade, modular Role-Based Access Control (RBAC) and Multi-Layer Authentication Pipeline package for Laravel 11, 12, and 13.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pinal-paramsoftware/laravel-rbac-suite.svg?style=flat-square)](https://packagist.org/packages/pinal-paramsoftware/laravel-rbac-suite)
[![Total Downloads](https://img.shields.io/packagist/dt/pinal-paramsoftware/laravel-rbac-suite.svg?style=flat-square)](https://packagist.org/packages/pinal-paramsoftware/laravel-rbac-suite)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

---

## Features

- **Granular RBAC Engine**: Fully integrated with `spatie/laravel-permission` using standard `{module}-{operation}` syntax (`role-view`, `role-create`, `user-view`, `permission-view`, etc.).
- **Admin Immunity**: Protected `Admin` role immune to deletion with automated core permission preservation.
- **Complete Permission Catalogue**: Default provision of all 20 granular permissions synchronized to the Admin role.
- **First-Run Login Flow**: Root URL (`/`) automatically redirects guests to `/login`, and authenticated users to the configured administrative dashboard.
- **Referential Integrity**: In-use roles and assigned permissions cannot be deleted while assigned to users.
- **User Master with Yajra DataTables**: Server-side user grid with instant search, real-time status toggle, admin password resets, and soft delete recovery.
- **6-Stage Secondary Authentication Pipeline**:
  - **GD Image Captcha**: Visual challenge with noise pixels, custom dimensions, and single-use challenge expiration.
  - **Email OTP**: 6-digit one-time passcodes dispatched via dynamic runtime SMTP reconfiguration with cooldown intervals.
  - **Mobile OTP**: External HTTP SMS Gateway integration with token and rate limits.
  - **WhatsApp OTP**: Official WhatsApp Business API / webhook integration.
  - **Two-Factor Authentication (TOTP)**: RFC 6238 Google Authenticator with QR code onboarding via `bacon/bacon-qr-code` and anti-replay protection.
  - **Hardware Device / MAC Authorization**: SHA-256 device token verification and hardware MAC inventory audit for native/managed environments.
- **Fully Publishable & Customizable**:
  - All core components (controllers, models, routes, views, configs, migrations, seeders, assets) can be published to conventional Laravel locations.
  - Smart automatic resolution: published classes and templates take precedence without requiring hacks in vendor files.
  - Upgrades remain 100% safe: non-destructive publishing protects existing application files.
- **Configurable UI Shell & Bundled Theme**: Bundled with a responsive Bootstrap 5 / Minia admin shell, with one-line configuration to inherit custom host application layouts.
- **Safe Additive Database Architecture**: Existence guards (`Schema::hasTable`, `Schema::hasColumn`) and manifest-tracked rollbacks (`SchemaTracker`) prevent dropping pre-existing host tables or columns.
- **Decoupled Architecture**: Drop-in `HasRbacUser` trait for host application User models with standard Spatie `HasRoles` fallback support.

---

## Requirements & Compatibility

| Component | Supported Versions | Tested Environment |
| :--- | :--- | :--- |
| **PHP** | `^8.2 \|\| ^8.3 \|\| ^8.4` | PHP 8.3.31 (cli) |
| **Laravel Framework** | `^11.0 \|\| ^12.0 \|\| ^13.0` | Laravel 13.38.0 |
| **Database** | MySQL 8.0+, PostgreSQL 14+, SQLite 3+ | SQLite (:memory:) & MySQL |

---

## Package Architecture & Upgrade Strategy

The package maintains a clean boundary between package-managed foundations and application-owned customizations:

```
Package-Managed (vendor/...)               Application-Owned (app/..., config/..., etc.)
┌─────────────────────────────────┐         ┌───────────────────────────────────────────┐
│ • RbacSuiteServiceProvider      │         │ • config/rbac-suite.php                   │
│ • Reusable Security Services    │ ──────> │ • routes/rbac.php                         │
│ • Fallback Views & Directives   │ (publish│ • resources/views/vendor/rbac-suite/      │
│ • RbacControllerResolver        │  tags)  │ • app/Http/Controllers/Rbac/             │
│ • RbacModelResolver             │         │ • app/Models/Rbac/                        │
│ • Base Controllers & Models     │         │ • database/migrations/                    │
│ • Safe Seeders & Install Cmd    │         │ • database/seeders/                       │
│ • Default Public Assets         │         │ • public/vendor/rbac-suite/               │
└─────────────────────────────────┘         └───────────────────────────────────────────┘
```

### Precedence Rules

1. **Controllers**: If a controller exists in `App\Http\Controllers\Rbac\...`, it automatically handles incoming requests instead of the package vendor controller.
2. **Routes**: If `routes/rbac.php` exists in the application root, it is loaded instead of the package route file.
3. **Views**: If a template exists in `resources/views/vendor/rbac-suite/...`, Laravel renders it with higher priority than the vendor fallback.
4. **Models**: If a model exists in `App\Models\Rbac\...`, `RbacModelResolver` and container bindings utilize the application model.
5. **Assets**: Asset URLs via `rbac_asset()` resolve from `public/vendor/rbac-suite/...` first.

---

## Installation & Setup

### 1. Require Package via Composer

```bash
composer require pinal-paramsoftware/laravel-rbac-suite
```

Composer auto-discovers `RbacSuiteServiceProvider` and the `RbacSuite` facade.

### 2. Automated Installer

Run the interactive, safe package installer:

```bash
# Basic setup: publishes configs & public UI assets
php artisan rbac:install

# Complete setup: publish, migrate database, and seed initial roles/admin
php artisan rbac:install --publish --migrate --seed
```

#### Installer Options

| Option | Description |
| :--- | :--- |
| `--publish` | Publishes core configs, `routes/rbac.php`, views, and public assets. |
| `--publish-all` | Publishes complete set (configs, routes, views, assets, controllers, models, migrations, seeders). |
| `--publish-controllers` | Publishes customizable controllers to `app/Http/Controllers/Rbac/`. |
| `--publish-models` | Publishes customizable models to `app/Models/Rbac/`. |
| `--publish-routes` | Publishes dedicated route file to `routes/rbac.php`. |
| `--publish-views` | Publishes customizable Blade views to `resources/views/vendor/rbac-suite/`. |
| `--publish-config` | Publishes configuration files to `config/rbac-suite.php` & `config/auth-pipeline.php`. |
| `--publish-migrations` | Publishes additive migrations to `database/migrations/`. |
| `--publish-seeders` | Publishes idempotent seeders to `database/seeders/`. |
| `--publish-assets` | Publishes public assets to `public/vendor/rbac-suite/`. |
| `--migrate` | Executes database migrations immediately. |
| `--seed` | Seeds roles, permissions, baseline settings, and default admin. |
| `--force` | Overwrites existing application files (requires explicit consent). |

---

## Publishing Tags

You may publish individual file subsets using standard Laravel `vendor:publish` commands:

### Configuration
```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-config
```
*Destination: `config/rbac-suite.php` and `config/auth-pipeline.php`*

### Controllers
```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-controllers
```
*Destination: `app/Http/Controllers/Rbac/`*

### Models
```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-models
```
*Destination: `app/Models/Rbac/`*

### Dedicated Routes
```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-routes
```
*Destination: `routes/rbac.php`*

### Views
```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-views
```
*Destination: `resources/views/vendor/rbac-suite/`*

### Migrations
```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-migrations
```
*Destination: `database/migrations/`*

### Seeders
```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-seeders
```
*Destination: `database/seeders/`*

### UI Assets
```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-assets
```
*Destination: `public/vendor/rbac-suite/`*

### Publish Complete Set
```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-all
```

---

## User Model Integration

In your `App\Models\User` model (`app/Models/User.php`), add the `HasRbacUser` trait (or standard Spatie `HasRoles`):

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Parth\LaravelRbacSuite\Traits\HasRbacUser;

class User extends Authenticatable
{
    use HasRbacUser;

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_number',
        'address',
        'is_active',
    ];
}
```

---

## Customization Guide

### 1. Customizing Controllers
Publish controllers via:
```bash
php artisan vendor:publish --tag=rbac-suite-controllers
```
Each published controller in `app/Http/Controllers/Rbac/` extends the package base controller. You can override individual actions while retaining the rest of the robust logic:

```php
namespace App\Http\Controllers\Rbac;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Http\Controllers\DashboardController as BaseDashboardController;

class DashboardController extends BaseDashboardController
{
    public function index(Request $request): View
    {
        // Custom analytics or custom view
        return parent::index($request);
    }
}
```

### 2. Customizing Routes
Publish routes via:
```bash
php artisan vendor:publish --tag=rbac-suite-routes
```
Edit `routes/rbac.php` directly. When present, the package loads your application route file and bypasses its own, preventing duplicate routes.

### 3. Customizing Views
Publish views via:
```bash
php artisan vendor:publish --tag=rbac-suite-views
```
Edit any Blade file in `resources/views/vendor/rbac-suite/`. Your modifications take precedence over the package defaults automatically.

### 4. Customizing Models
Publish models via:
```bash
php artisan vendor:publish --tag=rbac-suite-models
```
Add custom relations, scopes, or query builders in `app/Models/Rbac/`.

---

## Default Administrator Credentials

- **Email:** `admin@gmail.com`
- **Password:** `password`
- **Role:** `Admin` (granted all 20 package permissions)

> [!WARNING]
> Change the default password immediately after first login via `/master/user` or the Change Password screen!

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
