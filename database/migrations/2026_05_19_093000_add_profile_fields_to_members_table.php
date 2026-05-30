<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('spouse_name')->nullable()->after('email');
            $table->string('npwp', 32)->nullable()->after('spouse_name');
            $table->string('company_unit')->nullable()->after('address');
            $table->string('account_number', 50)->nullable()->after('company_unit');
            $table->string('membership_type')->default('regular')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'spouse_name',
                'npwp',
                'company_unit',
                'account_number',
                'membership_type',
            ]);
        });
    }
};
