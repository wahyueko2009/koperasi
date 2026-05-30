<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retail_transaction_id');
            $table->unsignedBigInteger('retail_item_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign('retail_transaction_id')->references('id')->on('retail_transactions')->onDelete('cascade');
            $table->foreign('retail_item_id')->references('id')->on('retail_items')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_transaction_items');
    }
};
