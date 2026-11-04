<?php
namespace ItmoSoft\Inventory\Tests;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use ItmoSoft\Inventory\InventoryServiceProvider;
abstract class TestCase extends Orchestra {
    protected function getPackageProviders($app): array { return [InventoryServiceProvider::class]; }
    protected function defineEnvironment($app): void {
        $app['config']->set('database.default','testing');
        $app['config']->set('database.connections.testing',['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true]);
    }
    protected function setUp(): void {
        parent::setUp();
        $migration = require __DIR__.'/../database/migrations/2026_11_04_000001_create_inventory_tables.php';
        $migration->up();
        Schema::create('test_products', function (Blueprint $t) { $t->id(); $t->string('name'); });
    }
    protected function product(): Model {
        return new class extends Model { protected $table='test_products'; protected $guarded=[]; public $timestamps=false; };
    }
}
