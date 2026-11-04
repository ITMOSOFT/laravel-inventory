# Release checklist

## GitHub repository
Name: `ITMOSOFT/laravel-inventory`

Description:
> A reusable Laravel inventory package for stock locations, stock levels, movements, and reservations. Supports Laravel 12 and 13.

## Install and test
```bash
composer install
composer validate --no-check-publish
composer test
```
Release only after local tests and both CI matrix jobs pass.

## Commit and push
```bash
git add .
git status --short
git ls-files vendor/
git commit -m "feat: add Laravel inventory package"
git push -u origin main
```
`git ls-files vendor/` should return no files.

## Tag
```bash
git checkout main
git pull --ff-only origin main
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
git show v1.0.0
```

## GitHub Release
Title: `v1.0.0 — Initial Stable Release`

Description:
```markdown
## ITMOSOFT Laravel Inventory v1.0.0

Initial stable release of a reusable Laravel inventory package.

### Features
- Stock locations for warehouses, stores, and branches
- Polymorphic stock records for any Eloquent model
- Stock receipt and signed adjustment movements
- Quantity-on-hand, reserved, and available tracking
- Reservations with optional expiry timestamps
- Reservation release and fulfillment operations
- Transactional updates with row-level locking
- Configurable table prefix and model class map
- Laravel 12 and Laravel 13 support

### Requirements
- PHP 8.3+
- Laravel 12 or 13

### Installation
```bash
composer require itmosoft/laravel-inventory
php artisan vendor:publish --tag=inventory-config
php artisan migrate
```

Includes PHPUnit feature tests and a GitHub Actions Laravel 12/13 test matrix.
```

## Packagist
Submit `https://github.com/ITMOSOFT/laravel-inventory` at https://packagist.org/packages/submit, configure the Packagist GitHub webhook if needed, then verify installation in a clean Laravel app.

Package: `itmosoft/laravel-inventory`
Tag: `v1.0.0`
