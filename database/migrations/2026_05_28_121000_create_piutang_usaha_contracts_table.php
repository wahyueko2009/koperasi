<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piutang_usaha_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->foreignId('company_id')->constrained('piutang_usaha_companies')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('contract_date');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('service_category');
            $table->string('billing_cycle')->default('bulanan');
            $table->unsignedInteger('payment_term_days')->default(30);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piutang_usaha_contracts');
    }
};
