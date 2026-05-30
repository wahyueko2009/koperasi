<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('reference_type')->comment('Tipe referensi: savings, loan, retail, etc');
            $table->unsignedBigInteger('reference_id')->comment('ID dari transaksi yang mereferensi');
            $table->unsignedBigInteger('debit_account_id')->nullable();
            $table->unsignedBigInteger('credit_account_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('memo')->nullable();
            $table->string('status')->default('posted')->comment('posted, reversed, cancelled');
            $table->timestamps();

            $table->foreign('debit_account_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
            $table->foreign('credit_account_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
