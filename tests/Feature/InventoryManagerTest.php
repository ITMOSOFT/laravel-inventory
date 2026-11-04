<?php
namespace ItmoSoft\Inventory\Tests\Feature;
use InvalidArgumentException;
use ItmoSoft\Inventory\Exceptions\InsufficientStockException;
use ItmoSoft\Inventory\Models\StockLocation;
use ItmoSoft\Inventory\Services\InventoryManager;
use ItmoSoft\Inventory\Tests\TestCase;
class InventoryManagerTest extends TestCase {
    protected function location(string $code='MAIN'): StockLocation { return StockLocation::create(['name'=>'Warehouse '.$code,'code'=>$code]); }
    protected function savedProduct(): \Illuminate\Database\Eloquent\Model { $p=$this->product(); $p->name='Test'; $p->save(); return $p; }

    public function test_receipt_creates_stock_and_ledger_movement(): void {
        $p=$this->savedProduct(); $m=app(InventoryManager::class)->receive($p,$this->location(),20,'PO-1');
        $this->assertSame('receipt',$m->type); $this->assertSame(20,$m->stockItem->quantity_on_hand); $this->assertSame(20,$m->stockItem->available_quantity);
    }
    public function test_stock_is_separated_by_location(): void {
        $p=$this->savedProduct(); $manager=app(InventoryManager::class);
        $a=$manager->stockFor($p,$this->location('A')); $b=$manager->stockFor($p,$this->location('B'));
        $this->assertNotSame($a->id,$b->id);
    }
    public function test_adjustment_records_signed_quantity_and_reason(): void {
        $p=$this->savedProduct(); $l=$this->location(); $manager=app(InventoryManager::class);
        $manager->receive($p,$l,10); $m=$manager->adjust($p,$l,-2,'Damaged');
        $this->assertSame(-2,$m->quantity); $this->assertSame('Damaged',$m->metadata['reason']); $this->assertSame(8,$m->stockItem->quantity_on_hand);
    }
    public function test_reservation_reduces_available_not_on_hand(): void {
        $p=$this->savedProduct(); $l=$this->location(); $manager=app(InventoryManager::class);
        $manager->receive($p,$l,10); $r=$manager->reserve($p,$l,3,'ORDER-1');
        $this->assertSame(10,$r->stockItem->quantity_on_hand); $this->assertSame(3,$r->stockItem->quantity_reserved); $this->assertSame(7,$r->stockItem->available_quantity);
    }
    public function test_insufficient_stock_cannot_be_reserved(): void {
        $this->expectException(InsufficientStockException::class);
        $p=$this->savedProduct(); $l=$this->location(); $manager=app(InventoryManager::class);
        $manager->receive($p,$l,2); $manager->reserve($p,$l,3);
    }
    public function test_adjustment_cannot_reduce_stock_below_reserved(): void {
        $this->expectException(InsufficientStockException::class);
        $p=$this->savedProduct(); $l=$this->location(); $manager=app(InventoryManager::class);
        $manager->receive($p,$l,5); $manager->reserve($p,$l,4); $manager->adjust($p,$l,-2,'Correction');
    }
    public function test_release_restores_available_quantity(): void {
        $p=$this->savedProduct(); $l=$this->location(); $manager=app(InventoryManager::class);
        $manager->receive($p,$l,9); $r=$manager->reserve($p,$l,4); $manager->release($r);
        $stock=$r->stockItem()->first(); $this->assertSame('released',$r->fresh()->status); $this->assertSame(0,$stock->quantity_reserved); $this->assertSame(9,$stock->available_quantity);
    }
    public function test_release_is_idempotent(): void {
        $p=$this->savedProduct(); $l=$this->location(); $manager=app(InventoryManager::class);
        $manager->receive($p,$l,9); $r=$manager->reserve($p,$l,4); $manager->release($r); $manager->release($r);
        $this->assertSame(0,$r->stockItem()->first()->quantity_reserved);
    }
    public function test_fulfillment_decrements_on_hand_and_reserved(): void {
        $p=$this->savedProduct(); $l=$this->location(); $manager=app(InventoryManager::class);
        $manager->receive($p,$l,10); $r=$manager->reserve($p,$l,3,'ORDER-2'); $movement=$manager->fulfill($r);
        $stock=$r->stockItem()->first(); $this->assertSame(-3,$movement->quantity); $this->assertSame(7,$stock->quantity_on_hand); $this->assertSame(0,$stock->quantity_reserved); $this->assertSame('fulfilled',$r->fresh()->status);
    }
    public function test_fulfilled_reservation_cannot_be_fulfilled_again(): void {
        $this->expectException(InvalidArgumentException::class);
        $p=$this->savedProduct(); $l=$this->location(); $manager=app(InventoryManager::class);
        $manager->receive($p,$l,5); $r=$manager->reserve($p,$l,2); $manager->fulfill($r); $manager->fulfill($r);
    }
    public function test_zero_quantity_receipt_is_rejected(): void {
        $this->expectException(InvalidArgumentException::class);
        $p=$this->savedProduct(); app(InventoryManager::class)->receive($p,$this->location(),0);
    }
    public function test_zero_adjustment_is_rejected(): void {
        $this->expectException(InvalidArgumentException::class);
        $p=$this->savedProduct(); app(InventoryManager::class)->adjust($p,$this->location(),0,'No-op');
    }
}
