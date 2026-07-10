<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_usaha_inventory_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('unit_usaha_inventories')->cascadeOnDelete();
            $table->string('price_type', 20);
            $table->decimal('old_price', 15, 2)->nullable();
            $table->decimal('new_price', 15, 2);
            $table->string('change_source', 50);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('effective_at')->nullable();
            $table->timestamps();

            $table->index(['inventory_id', 'price_type']);
            $table->index(['change_source', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_usaha_inventory_price_histories');
    }
};
