<?php
namespace ItmoSoft\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ItmoSoft\Inventory\Models\Concerns\UsesInventoryTable;
class StockReservation extends Model
{
    use UsesInventoryTable;
    protected string $inventoryTable = 'reservations';
    protected $guarded = [];
    protected function casts(): array { return ['quantity' => 'integer', 'expires_at' => 'datetime', 'metadata' => 'array']; }
    public function stockItem(): BelongsTo { return $this->belongsTo(config('inventory.models.stock_item', StockItem::class), 'stock_item_id'); }
}
