<?php

namespace ItmoSoft\Inventory\Tests\Feature;

use InvalidArgumentException;
use ItmoSoft\Inventory\Exceptions\InsufficientStockException;
use ItmoSoft\Inventory\Models\StockLocation;
use ItmoSoft\Inventory\Services\InventoryManager;
use ItmoSoft\Inventory\Tests\TestCase;

class InventoryManagerTest extends TestCase
{
    protected function location(string $code = 'MAIN'): StockLocation
    {
        return StockLocation::create(['name' => 'Warehouse '.$code, 'code' => $code]);
    }

    protected function savedProduct(): \Illuminate\Database\Eloquent\Model
    {
        $product = $this->product();
        $product->name = 'Test';
        $product->save();

        return $product;
    }

    public function test_receipt_creates_stock_and_ledger_movement(): void
    {
        $product = $this->savedProduct();
        $movement = app(InventoryManager::class)->receive($product, $this->location(), 20, 'PO-1');

        $this->assertSame('receipt', $movement->type);
        $this->assertSame(20, $movement->stockItem->quantity_on_hand);
        $this->assertSame(20, $movement->stockItem->available_quantity);
    }

    public function test_stock_is_separated_by_location(): void
    {
        $product = $this->savedProduct();
        $manager = app(InventoryManager::class);
        $first = $manager->stockFor($product, $this->location('A'));
        $second = $manager->stockFor($product, $this->location('B'));

        $this->assertNotSame($first->id, $second->id);
    }

    public function test_stock_for_returns_existing_row_instead_of_creating_duplicates(): void
    {
        $product = $this->savedProduct();
        $location = $this->location();
        $manager = app(InventoryManager::class);

        $first = $manager->stockFor($product, $location);
        $second = $manager->stockFor($product, $location);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('inventory_stock_items', 1);
    }

    public function test_stock_operations_reject_unsaved_inventory_item(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(InventoryManager::class)->stockFor($this->product(), $this->location());
    }

    public function test_stock_operations_reject_unsaved_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(InventoryManager::class)->stockFor($this->savedProduct(), new StockLocation([
            'name' => 'Unsaved',
            'code' => 'UNSAVED',
        ]));
    }

    public function test_adjustment_records_signed_quantity_and_reason(): void
    {
        $product = $this->savedProduct();
        $location = $this->location();
        $manager = app(InventoryManager::class);
        $manager->receive($product, $location, 10);
        $movement = $manager->adjust($product, $location, -2, 'Damaged');

        $this->assertSame(-2, $movement->quantity);
        $this->assertSame('Damaged', $movement->metadata['reason']);
        $this->assertSame(8, $movement->stockItem->quantity_on_hand);
    }

    public function test_reservation_reduces_available_not_on_hand(): void
    {
        $product = $this->savedProduct();
        $location = $this->location();
        $manager = app(InventoryManager::class);
        $manager->receive($product, $location, 10);
        $reservation = $manager->reserve($product, $location, 3, 'ORDER-1');

        $this->assertSame(10, $reservation->stockItem->quantity_on_hand);
        $this->assertSame(3, $reservation->stockItem->quantity_reserved);
        $this->assertSame(7, $reservation->stockItem->available_quantity);
    }

    public function test_insufficient_stock_cannot_be_reserved(): void
    {
        $this->expectException(InsufficientStockException::class);

        $product = $this->savedProduct();
        $location = $this->location();
        $manager = app(InventoryManager::class);
        $manager->receive($product, $location, 2);
        $manager->reserve($product, $location, 3);
    }

    public function test_adjustment_cannot_reduce_stock_below_reserved(): void
    {
        $this->expectException(InsufficientStockException::class);

        $product = $this->savedProduct();
        $location = $this->location();
        $manager = app(InventoryManager::class);
        $manager->receive($product, $location, 5);
        $manager->reserve($product, $location, 4);
        $manager->adjust($product, $location, -2, 'Correction');
    }

    public function test_release_restores_available_quantity(): void
    {
        $product = $this->savedProduct();
        $location = $this->location();
        $manager = app(InventoryManager::class);
        $manager->receive($product, $location, 9);
        $reservation = $manager->reserve($product, $location, 4);
        $manager->release($reservation);

        $stock = $reservation->stockItem()->first();
        $this->assertSame('released', $reservation->fresh()->status);
        $this->assertSame(0, $stock->quantity_reserved);
        $this->assertSame(9, $stock->available_quantity);
    }

    public function test_release_is_idempotent(): void
    {
        $product = $this->savedProduct();
        $location = $this->location();
        $manager = app(InventoryManager::class);
        $manager->receive($product, $location, 9);
        $reservation = $manager->reserve($product, $location, 4);
        $manager->release($reservation);
        $manager->release($reservation);

        $this->assertSame(0, $reservation->stockItem()->first()->quantity_reserved);
    }

    public function test_fulfillment_decrements_on_hand_and_reserved(): void
    {
        $product = $this->savedProduct();
        $location = $this->location();
        $manager = app(InventoryManager::class);
        $manager->receive($product, $location, 10);
        $reservation = $manager->reserve($product, $location, 3, 'ORDER-2');
        $movement = $manager->fulfill($reservation);

        $stock = $reservation->stockItem()->first();
        $this->assertSame(-3, $movement->quantity);
        $this->assertSame(7, $stock->quantity_on_hand);
        $this->assertSame(0, $stock->quantity_reserved);
        $this->assertSame('fulfilled', $reservation->fresh()->status);
    }

    public function test_fulfilled_reservation_cannot_be_fulfilled_again(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->savedProduct();
        $location = $this->location();
        $manager = app(InventoryManager::class);
        $manager->receive($product, $location, 5);
        $reservation = $manager->reserve($product, $location, 2);
        $manager->fulfill($reservation);
        $manager->fulfill($reservation);
    }

    public function test_zero_quantity_receipt_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->savedProduct();
        app(InventoryManager::class)->receive($product, $this->location(), 0);
    }

    public function test_zero_adjustment_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->savedProduct();
        app(InventoryManager::class)->adjust($product, $this->location(), 0, 'No-op');
    }
}
