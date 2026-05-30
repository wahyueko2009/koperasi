<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_usaha_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('unit_usaha_sales')->cascadeOnDelete();
            $table->string('item_type');
            $table->foreignId('service_id')->nullable()->constrained('unit_usaha_services')->nullOnDelete();
            $table->foreignId('inventory_id')->nullable()->constrained('unit_usaha_inventories')->nullOnDelete();
            $table->string('item_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_usaha_sale_items');
    }
};
