<?php
namespace ItmoSoft\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ItmoSoft\Inventory\Models\Concerns\UsesInventoryTable;
class StockLocation extends Model
{
    use UsesInventoryTable;
    protected string $inventoryTable = 'locations';
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean', 'metadata' => 'array']; }
    public function stockItems(): HasMany { return $this->hasMany(config('inventory.models.stock_item', StockItem::class), 'stock_location_id'); }
    public function movements(): HasMany { return $this->hasMany(config('inventory.models.movement', StockMovement::class), 'stock_location_id'); }
}
