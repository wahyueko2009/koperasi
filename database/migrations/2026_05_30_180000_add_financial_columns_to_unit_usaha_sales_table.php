<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_usaha_sales', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('sale_date')->constrained('members')->nullOnDelete();
            $table->string('category')->default('photocopy')->after('member_id');
            $table->string('payment_method')->default('cash')->after('category');
            $table->boolean('is_posted')->default(false)->after('status');
            $table->foreignId('journal_entry_id')->nullable()->after('is_posted')->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unit_usaha_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
            $table->dropColumn(['is_posted', 'payment_method', 'category']);
            $table->dropConstrainedForeignId('member_id');
        });
    }
};
