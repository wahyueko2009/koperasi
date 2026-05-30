<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->decimal('principal_amount', 15, 2)->comment('Pokok pinjaman');
            $table->decimal('interest_rate', 5, 2)->comment('Persentase bunga');
            $table->integer('tenor_months')->comment('Jangka waktu dalam bulan');
            $table->date('approval_date');
            $table->date('disbursement_date')->nullable();
            $table->date('maturity_date');
            $table->decimal('remaining_balance', 15, 2);
            $table->enum('status', ['pending', 'approved', 'disbursed', 'active', 'completed', 'defaulted'])->default('pending');
            $table->decimal('monthly_payment', 15, 2)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('restrict');
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
