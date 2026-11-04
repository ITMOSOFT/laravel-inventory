<?php
namespace ItmoSoft\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ItmoSoft\Inventory\Models\Concerns\UsesInventoryTable;
class StockItem extends Model
{
    use UsesInventoryTable;
    protected string $inventoryTable = 'stock_items';
    protected $guarded = [];
    protected function casts(): array { return ['quantity_on_hand' => 'integer', 'quantity_reserved' => 'integer']; }
    public function getAvailableQuantityAttribute(): int { return $this->quantity_on_hand - $this->quantity_reserved; }
    public function inventoryable(): MorphTo { return $this->morphTo(); }
    public function location(): BelongsTo { return $this->belongsTo(config('inventory.models.location', StockLocation::class), 'stock_location_id'); }
    public function movements(): HasMany { return $this->hasMany(config('inventory.models.movement', StockMovement::class), 'stock_item_id'); }
    public function reservations(): HasMany { return $this->hasMany(config('inventory.models.reservation', StockReservation::class), 'stock_item_id'); }
}
