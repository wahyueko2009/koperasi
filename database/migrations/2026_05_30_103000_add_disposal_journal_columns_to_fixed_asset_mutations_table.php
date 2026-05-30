<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_asset_mutations', function (Blueprint $table) {
            $table->foreignId('disposal_debit_account_id')->nullable()->after('disposal_value')->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('disposal_gain_account_id')->nullable()->after('disposal_debit_account_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('disposal_loss_account_id')->nullable()->after('disposal_gain_account_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('disposal_reference_number')->nullable()->after('disposal_loss_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_asset_mutations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disposal_debit_account_id');
            $table->dropConstrainedForeignId('disposal_gain_account_id');
            $table->dropConstrainedForeignId('disposal_loss_account_id');
            $table->dropColumn('disposal_reference_number');
        });
    }
};
