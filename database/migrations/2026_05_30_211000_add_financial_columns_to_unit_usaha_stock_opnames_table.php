<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_usaha_stock_opnames', function (Blueprint $table) {
            $table->decimal('adjustment_value', 15, 2)->default(0)->after('status');
            $table->boolean('is_posted')->default(false)->after('adjustment_value');
            $table->foreignId('journal_entry_id')->nullable()->after('is_posted')->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unit_usaha_stock_opnames', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
            $table->dropColumn(['adjustment_value', 'is_posted']);
        });
    }
};
