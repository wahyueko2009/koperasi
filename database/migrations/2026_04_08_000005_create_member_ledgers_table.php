<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->date('entry_date');
            $table->string('transaction_type')->comment('savings, loan, retail, etc');
            $table->unsignedBigInteger('transaction_id');
            $table->decimal('debit', 15, 2)->default(0)->comment('Piutang/Beban');
            $table->decimal('credit', 15, 2)->default(0)->comment('Kredit/Pendapatan');
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->index(['member_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_ledgers');
    }
};
