# ITMOSOFT Laravel Inventory

Reusable Laravel inventory management for stock locations, polymorphic stock items, stock movements, and reservations.

## Requirements
- PHP 8.3+
- Laravel 12 or 13

## Installation
```bash
composer require itmosoft/laravel-inventory
php artisan vendor:publish --tag=inventory-config
php artisan migrate
```

## Usage
Any saved Eloquent model can be inventoried, so this package does not require a hard dependency on the Product Catalog package.

```php
use ItmoSoft\Inventory\Models\StockLocation;
use ItmoSoft\Inventory\Services\InventoryManager;

$warehouse = StockLocation::create(['name' => 'Main Warehouse', 'code' => 'MAIN']);
$inventory = app(InventoryManager::class);

// Receive 100 units.
$inventory->receive($product, $warehouse, 100, 'PO-1001');

// Reserve 4 units for an order.
$reservation = $inventory->reserve($product, $warehouse, 4, 'ORDER-5001');

// Release a cancelled order's reservation.
$inventory->release($reservation);

// Or fulfill it: both on-hand and reserved quantities decrease.
$movement = $inventory->fulfill($reservation);
```

## Adjustments
```php
$inventory->adjust($product, $warehouse, -2, 'Damaged during handling', 'ADJUST-20');
```
Positive adjustment quantities increase stock; negative quantities decrease stock. An adjustment cannot reduce on-hand stock below reserved quantity.

## Read stock levels
```php
$stock = $inventory->stockFor($product, $warehouse);
$stock->quantity_on_hand;
$stock->quantity_reserved;
$stock->available_quantity;
$stock->movements;
$stock->reservations;
```

## Configuration
Publish `config/inventory.php` with `php artisan vendor:publish --tag=inventory-config`.
- `table_prefix`: default `inventory_`; configure before running migrations.
- `models`: model classes used by package relationships.

Tables: `inventory_locations`, `inventory_stock_items`, `inventory_movements`, `inventory_reservations`.

## Boundaries
This package manages stock only. It does not implement catalog data, pricing, promotions, carts, checkout, order lifecycle, payments, lot/batch tracking, serial numbers, or automatic reservation expiry. `expires_at` is stored for application-managed expiry workflows.

## Testing
```bash
composer install
composer validate --no-check-publish
composer test
```
GitHub Actions targets Laravel 12 and 13 separately.

## License
MIT. See LICENSE.
