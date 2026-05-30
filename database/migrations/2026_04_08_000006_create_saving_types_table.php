<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->comment('Pokok, Wajib, Sukarela');
            $table->string('description')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->decimal('monthly_amount', 15, 2)->nullable()->comment('Untuk simpanan wajib');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_types');
    }
};
