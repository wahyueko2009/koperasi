<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_usaha_sales', function (Blueprint $table) {
            $table->string('source_module')->nullable()->after('journal_entry_id');
            $table->string('source_reference_type')->nullable()->after('source_module');
            $table->unsignedBigInteger('source_reference_id')->nullable()->after('source_reference_type');
        });

        Schema::table('unit_usaha_sale_items', function (Blueprint $table) {
            $table->foreignId('retail_item_id')->nullable()->after('inventory_id')->constrained('retail_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unit_usaha_sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('retail_item_id');
        });

        Schema::table('unit_usaha_sales', function (Blueprint $table) {
            $table->dropColumn(['source_module', 'source_reference_type', 'source_reference_id']);
        });
    }
};
