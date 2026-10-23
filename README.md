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
│ • Fallback Views & Directives   │ (publish│ • resources/views/ (direct views)         │
│ • RbacViewResolver              │  tags)  │ • app/Http/Controllers/Rbac/             │
│ • RbacControllerResolver        │         │ • app/Models/Rbac/                        │
│ • RbacModelResolver             │         │ • database/migrations/                    │
│ • Base Controllers & Models     │         │ • database/seeders/                       │
│ • Safe Seeders & Install Cmd    │         │ • public/vendor/rbac-suite/               │
│ • Default Public Assets         │         │                                           │
└─────────────────────────────────┘         └───────────────────────────────────────────┘
```

### Precedence Rules

1. **Controllers**: If a controller exists in `App\Http\Controllers\Rbac\...`, it automatically handles incoming requests instead of the package vendor controller.
2. **Routes**: If `routes/rbac.php` exists in the application root, it is loaded instead of the package route file.
3. **Views**: The package implements a three-tier view resolution strategy (`RbacViewResolver`):
   - **Tier 1 (Host Application Direct View)**: `resources/views/{path}` (highest priority; e.g. `resources/views/auth/login.blade.php`).
   - **Tier 2 (Legacy Vendor Override)**: `resources/views/vendor/rbac-suite/{path}` (retained for backward compatibility).
   - **Tier 3 (Package Bundled Fallback)**: `vendor/pinal-paramsoftware/laravel-rbac-suite/resources/views/{path}`.
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
| `--publish-views` | Publishes customizable Blade views directly to `resources/views/`. |
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
*Destination: `resources/views/` (directly into the application views folder)*

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

Package Blade views are published directly into the consuming Laravel application's `resources/views/` folder, allowing seamless customization without nested vendor paths:

```bash
php artisan vendor:publish --provider="Parth\LaravelRbacSuite\RbacSuiteServiceProvider" --tag=rbac-suite-views
```

Or using the installer:
```bash
php artisan rbac:install --publish-views
```

#### Published Host View Structure

All 35 package Blade templates map directly to standard Laravel subdirectories under `resources/views/`:

```
resources/views/
├── dashboard.blade.php                 # Administrative dashboard
├── auth/
│   ├── login.blade.php                 # Login interface & captcha challenge
│   ├── register.blade.php              # Self-service user registration
│   ├── otp-verify.blade.php            # Primary email OTP verification
│   ├── mobile-otp-verify.blade.php     # SMS OTP verification screen
│   ├── whatsapp-otp-verify.blade.php   # WhatsApp OTP verification screen
│   ├── two-factor.blade.php            # 2FA entry container
│   ├── two-factor-setup.blade.php      # 2FA QR code onboarding screen
│   ├── two-factor-verify.blade.php     # 2FA authenticator verification
│   ├── verify.blade.php                # Email verification prompt
│   └── passwords/
│       ├── confirm.blade.php           # Password confirmation dialog
│       ├── email.blade.php             # Password reset email request
│       └── reset.blade.php             # New password submission
├── layouts/
│   ├── master.blade.php                # Primary layout container (sidebar + topbar)
│   ├── master-without-nav.blade.php    # Clean authentication layout shell
│   ├── head.blade.php                  # Global HTML head tags & font links
│   ├── title-meta.blade.php            # Meta tags & page title handler
│   ├── topbar.blade.php                # Top navigation bar, notifications & user menu
│   ├── sidebar.blade.php               # Left sidebar navigation links
│   ├── footer.blade.php                # Admin footer copyright notice
│   ├── right-sidebar.blade.php         # Layout customization drawer
│   └── vendor-scripts.blade.php        # Core JavaScript & bundle scripts
├── common-components/
│   └── breadcrumb.blade.php            # Page title & breadcrumb header component
├── master/
│   ├── AuthSettings/
│   │   └── setting.blade.php           # Security & authentication settings console
│   └── user/
│       ├── index.blade.php             # User master listing with DataTables
│       └── create.blade.php            # User creation & edit modal/form
├── roles/
│   ├── index.blade.php                 # Role management table & permissions audit
│   ├── create.blade.php                # Create new role with permission matrix
│   ├── edit.blade.php                  # Edit existing role permissions
│   └── show.blade.php                  # View role details & active assigned users
├── permissions/
│   ├── index.blade.php                 # Permission catalogue grid
│   ├── create.blade.php                # Register new modular permission
│   └── edit.blade.php                  # Update permission operations
└── emails/
    ├── otp.blade.php                   # HTML template for dispatched OTP emails
    └── smtp-test.blade.php             # Test email dispatched from admin console
```

#### Safe Customization Guidelines

- **Authentication Interfaces (`resources/views/auth/`)**: Safe to re-brand, apply custom CSS, adjust card styling, or embed corporate badges. Form field names (`email`, `password`, `otp`, `captcha`) and action routes should be preserved.
- **Admin Layout & Navigation (`resources/views/layouts/topbar.blade.php`, `sidebar.blade.php`, `footer.blade.php`)**: Safe to customize with custom company branding, logos, additional menu links, or header widgets.
- **Admin Dashboard (`resources/views/dashboard.blade.php`)**: Safe to replace with application-specific analytics, charts, and metrics widgets.
- **User & Role Modules (`resources/views/master/`, `roles/`, `permissions/`)**: Safe to customize layout, add custom profile fields, or inject custom JavaScript/CSS.
- **Email Notifications (`resources/views/emails/`)**: Safe to adjust email branding, logo, colors, and disclaimers.

#### View Precedence Hierarchy

The package automatically resolves views using `RbacViewResolver`:
1. **Direct Host View (`resources/views/...`)**: If present, the application's view renders immediately.
2. **Legacy Vendor Override (`resources/views/vendor/rbac-suite/...`)**: Supported as a seamless fallback for upgraded applications.
3. **Internal Package View (`vendor/...`)**: If no host view exists, the bundled package template renders.

#### Non-Destructive Publishing & Overwrite Protection

- **Default Behavior**: Running `php artisan vendor:publish --tag=rbac-suite-views` is non-destructive. If a file already exists in `resources/views/` (such as existing host views or previously customized package views), Laravel **skips** it and will **never** silently overwrite your customizations.
- **Forced Overwrite**: To restore or replace all views with fresh original package copies, pass the `--force` flag:
  ```bash
  php artisan vendor:publish --tag=rbac-suite-views --force
  ```
  > [!CAUTION]
  > Using `--force` will replace all matching published views with the default package templates. Commit your work in Git before forcing publication.

#### How to Restore Original Package Views

If you customize a host view and wish to revert back to the package default:
1. Simply delete or rename the customized file from `resources/views/` (e.g., delete `resources/views/auth/login.blade.php`).
2. The application will instantly and automatically fall back to rendering the internal package view.
3. Alternatively, re-publish that specific view using `--force`.

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
