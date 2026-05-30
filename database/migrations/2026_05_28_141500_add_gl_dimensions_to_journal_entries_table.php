<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('period_id')->nullable()->after('entry_date')->constrained('gl_periods')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->after('period_id')->constrained('gl_cost_centers')->nullOnDelete();
            $table->string('source_module')->default('manual')->after('reference_id');
            $table->string('reference_number')->nullable()->after('source_module');
            $table->timestamp('posted_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('period_id');
            $table->dropConstrainedForeignId('cost_center_id');
            $table->dropColumn(['source_module', 'reference_number', 'posted_at']);
        });
    }
};
