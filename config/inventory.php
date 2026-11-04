<?php
return [
    'table_prefix' => env('INVENTORY_TABLE_PREFIX', 'inventory_'),
    'models' => [
        'location' => \ItmoSoft\Inventory\Models\StockLocation::class,
        'stock_item' => \ItmoSoft\Inventory\Models\StockItem::class,
        'movement' => \ItmoSoft\Inventory\Models\StockMovement::class,
        'reservation' => \ItmoSoft\Inventory\Models\StockReservation::class,
    ],
];
