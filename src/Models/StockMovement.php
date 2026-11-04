<?php
namespace ItmoSoft\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ItmoSoft\Inventory\Models\Concerns\UsesInventoryTable;
class StockMovement extends Model
{
    use UsesInventoryTable;
    protected string $inventoryTable = 'movements';
    protected $guarded = [];
    protected function casts(): array { return ['quantity' => 'integer', 'metadata' => 'array']; }
    public function stockItem(): BelongsTo { return $this->belongsTo(config('inventory.models.stock_item', StockItem::class), 'stock_item_id'); }
    public function location(): BelongsTo { return $this->belongsTo(config('inventory.models.location', StockLocation::class), 'stock_location_id'); }
}
