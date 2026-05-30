<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('saving_type_id');
            $table->date('deposit_date');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'salary_cut'])->default('cash');
            $table->string('notes')->nullable();
            $table->boolean('is_posted')->default(false)->comment('Apakah sudah dijurnal');
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('restrict');
            $table->foreign('saving_type_id')->references('id')->on('saving_types')->onDelete('restrict');
            $table->index(['member_id', 'deposit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings');
    }
};
