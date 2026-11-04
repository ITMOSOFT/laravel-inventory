<?php
namespace ItmoSoft\Inventory\Services;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ItmoSoft\Inventory\Exceptions\InsufficientStockException;
use ItmoSoft\Inventory\Models\StockItem;
use ItmoSoft\Inventory\Models\StockLocation;
use ItmoSoft\Inventory\Models\StockMovement;
use ItmoSoft\Inventory\Models\StockReservation;
class InventoryManager
{
    public function stockFor(Model $item, StockLocation $location): StockItem { return $this->findOrCreate($item, $location); }

    public function receive(Model $item, StockLocation $location, int $quantity, ?string $reference = null, ?array $metadata = null): StockMovement
    {
        $this->positive($quantity);
        return DB::transaction(function () use ($item, $location, $quantity, $reference, $metadata) {
            $stock = $this->locked($item, $location);
            $stock->quantity_on_hand += $quantity;
            $stock->save();
            return $this->movement($stock, $location, 'receipt', $quantity, $reference, $metadata);
        });
    }

    public function adjust(Model $item, StockLocation $location, int $quantity, string $reason, ?string $reference = null, ?array $metadata = null): StockMovement
    {
        if ($quantity === 0) throw new InvalidArgumentException('Adjustment quantity must not be zero.');
        return DB::transaction(function () use ($item, $location, $quantity, $reason, $reference, $metadata) {
            $stock = $this->locked($item, $location);
            $next = $stock->quantity_on_hand + $quantity;
            if ($next < $stock->quantity_reserved) throw new InsufficientStockException('Adjustment cannot reduce stock below reserved quantity.');
            $stock->quantity_on_hand = $next;
            $stock->save();
            return $this->movement($stock, $location, 'adjustment', $quantity, $reference, array_merge($metadata ?? [], ['reason' => $reason]));
        });
    }

    public function reserve(Model $item, StockLocation $location, int $quantity, ?string $reference = null, ?DateTimeInterface $expiresAt = null, ?array $metadata = null): StockReservation
    {
        $this->positive($quantity);
        return DB::transaction(function () use ($item, $location, $quantity, $reference, $expiresAt, $metadata) {
            $stock = $this->locked($item, $location);
            if ($stock->available_quantity < $quantity) throw new InsufficientStockException('Insufficient available stock.');
            $stock->quantity_reserved += $quantity;
            $stock->save();
            return $stock->reservations()->create(['quantity' => $quantity, 'reference' => $reference, 'status' => 'active', 'expires_at' => $expiresAt, 'metadata' => $metadata]);
        });
    }

    public function release(StockReservation $reservation): StockReservation
    {
        return DB::transaction(function () use ($reservation) {
            $reservation = $reservation->newQuery()->lockForUpdate()->findOrFail($reservation->getKey());
            if ($reservation->status !== 'active') return $reservation;
            $stock = $reservation->stockItem()->lockForUpdate()->firstOrFail();
            $stock->quantity_reserved -= $reservation->quantity;
            $stock->save();
            $reservation->status = 'released';
            $reservation->save();
            return $reservation;
        });
    }

    public function fulfill(StockReservation $reservation): StockMovement
    {
        return DB::transaction(function () use ($reservation) {
            $reservation = $reservation->newQuery()->lockForUpdate()->findOrFail($reservation->getKey());
            if ($reservation->status !== 'active') throw new InvalidArgumentException('Only active reservations can be fulfilled.');
            $stock = $reservation->stockItem()->lockForUpdate()->firstOrFail();
            $stock->quantity_reserved -= $reservation->quantity;
            $stock->quantity_on_hand -= $reservation->quantity;
            $stock->save();
            $reservation->status = 'fulfilled';
            $reservation->save();
            return $this->movement($stock, $stock->location, 'fulfillment', -$reservation->quantity, $reservation->reference, ['reservation_id' => $reservation->id]);
        });
    }

    protected function findOrCreate(Model $item, StockLocation $location): StockItem
    {
        $class = config('inventory.models.stock_item', StockItem::class);
        return $class::firstOrCreate([
            'inventoryable_type' => $item->getMorphClass(),
            'inventoryable_id' => $item->getKey(),
            'stock_location_id' => $location->getKey(),
        ], ['quantity_on_hand' => 0, 'quantity_reserved' => 0]);
    }
    protected function locked(Model $item, StockLocation $location): StockItem
    {
        $stock = $this->findOrCreate($item, $location);
        return $stock->newQuery()->lockForUpdate()->findOrFail($stock->getKey());
    }
    protected function movement(StockItem $stock, StockLocation $location, string $type, int $quantity, ?string $reference, ?array $metadata): StockMovement
    {
        return $stock->movements()->create(['stock_location_id' => $location->getKey(), 'type' => $type, 'quantity' => $quantity, 'reference' => $reference, 'metadata' => $metadata]);
    }
    protected function positive(int $quantity): void
    {
        if ($quantity < 1) throw new InvalidArgumentException('Quantity must be greater than zero.');
    }
}
