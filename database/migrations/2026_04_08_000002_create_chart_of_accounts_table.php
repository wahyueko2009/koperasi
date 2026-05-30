<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Kode Akun');
            $table->string('name');
            $table->enum('account_type', [
                'asset',           // Aset
                'liability',       // Kewajiban
                'equity',          // Ekuitas
                'income',          // Pendapatan
                'expense'          // Beban
            ]);
            $table->enum('normal_balance', ['debit', 'credit'])->comment('Saldo Normal');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
