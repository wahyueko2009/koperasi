<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('asset_name');
            $table->string('category');
            $table->date('acquisition_date');
            $table->date('in_service_date');
            $table->string('supplier_name')->nullable();
            $table->string('location')->nullable();
            $table->string('condition_status')->default('baik');
            $table->string('status')->default('active');
            $table->decimal('acquisition_value', 15, 2);
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->unsignedInteger('useful_life_months');
            $table->string('depreciation_method')->default('straight_line');
            $table->foreignId('asset_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('depreciation_expense_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('acquisition_credit_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('gl_cost_centers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->date('last_depreciation_at')->nullable();
            $table->date('disposed_at')->nullable();
            $table->decimal('disposal_value', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fixed_asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('depreciation_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('accumulated_amount', 15, 2);
            $table->decimal('book_value', 15, 2);
            $table->string('status')->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fixed_asset_id', 'period_year', 'period_month'], 'fixed_asset_period_unique');
        });

        Schema::create('fixed_asset_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->date('mutation_date');
            $table->string('mutation_type');
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->decimal('disposal_value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_mutations');
        Schema::dropIfExists('fixed_asset_depreciations');
        Schema::dropIfExists('fixed_assets');
    }
};
