<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable()->comment('Null jika pembeli non-anggota');
            $table->string('category')->comment('indomaret, photocopy');
            $table->date('transaction_date');
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_method', ['cash', 'salary_cut'])->default('cash');
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->string('notes')->nullable();
            $table->boolean('is_posted')->default(false)->comment('Apakah sudah dijurnal');
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
            $table->index(['member_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_transactions');
    }
};
