<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_usaha_purchases', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('total_amount');
            $table->boolean('is_posted')->default(false)->after('status');
            $table->foreignId('journal_entry_id')->nullable()->after('is_posted')->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unit_usaha_purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
            $table->dropColumn(['payment_method', 'is_posted']);
        });
    }
};
