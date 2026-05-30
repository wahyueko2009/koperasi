<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_ledgers', function (Blueprint $table) {
            $table->string('ledger_scope')->default('general')->after('member_id');
        });
    }

    public function down(): void
    {
        Schema::table('member_ledgers', function (Blueprint $table) {
            $table->dropColumn('ledger_scope');
        });
    }
};
