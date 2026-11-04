<?php
namespace ItmoSoft\Inventory\Models\Concerns;
trait UsesInventoryTable
{
    public function getTable(): string
    {
        return config('inventory.table_prefix', 'inventory_').$this->inventoryTable;
    }
}
