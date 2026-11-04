<?php
namespace ItmoSoft\Inventory;
use Illuminate\Support\ServiceProvider;
use ItmoSoft\Inventory\Services\InventoryManager;
class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/inventory.php', 'inventory');
        $this->app->singleton(InventoryManager::class);
    }
    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/inventory.php' => config_path('inventory.php')], 'inventory-config');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
