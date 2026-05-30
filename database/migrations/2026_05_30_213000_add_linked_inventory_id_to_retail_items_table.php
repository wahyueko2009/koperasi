<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retail_items', function (Blueprint $table) {
            $table->foreignId('linked_inventory_id')->nullable()->after('is_active')->constrained('unit_usaha_inventories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('retail_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_inventory_id');
        });
    }
};
