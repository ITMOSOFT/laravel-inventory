<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        $p = config('inventory.table_prefix', 'inventory_');
        Schema::create($p.'locations', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('code')->unique(); $t->text('address')->nullable(); $t->boolean('is_active')->default(true); $t->json('metadata')->nullable(); $t->timestamps(); });
        Schema::create($p.'stock_items', function (Blueprint $t) use ($p) { $t->id(); $t->morphs('inventoryable'); $t->foreignId('stock_location_id')->constrained($p.'locations')->cascadeOnDelete(); $t->unsignedInteger('quantity_on_hand')->default(0); $t->unsignedInteger('quantity_reserved')->default(0); $t->timestamps(); $t->unique(['inventoryable_type','inventoryable_id','stock_location_id'], 'inventory_stock_item_unique'); });
        Schema::create($p.'movements', function (Blueprint $t) use ($p) { $t->id(); $t->foreignId('stock_item_id')->constrained($p.'stock_items')->cascadeOnDelete(); $t->foreignId('stock_location_id')->constrained($p.'locations')->cascadeOnDelete(); $t->string('type'); $t->integer('quantity'); $t->string('reference')->nullable(); $t->json('metadata')->nullable(); $t->timestamps(); $t->index(['type','created_at']); $t->index('reference'); });
        Schema::create($p.'reservations', function (Blueprint $t) use ($p) { $t->id(); $t->foreignId('stock_item_id')->constrained($p.'stock_items')->cascadeOnDelete(); $t->unsignedInteger('quantity'); $t->string('reference')->nullable(); $t->string('status')->default('active'); $t->timestamp('expires_at')->nullable(); $t->json('metadata')->nullable(); $t->timestamps(); $t->index(['status','expires_at']); $t->index('reference'); });
    }
    public function down(): void {
        $p = config('inventory.table_prefix', 'inventory_');
        Schema::dropIfExists($p.'reservations'); Schema::dropIfExists($p.'movements'); Schema::dropIfExists($p.'stock_items'); Schema::dropIfExists($p.'locations');
    }
};
