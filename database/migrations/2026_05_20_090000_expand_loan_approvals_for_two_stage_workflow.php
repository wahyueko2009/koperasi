<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_approvals', function (Blueprint $table) {
            $table->string('approval_level')->nullable()->after('official_id');
            $table->string('status')->default('pending')->after('approval_level');
        });

        DB::table('loan_approvals')
            ->whereNull('approval_level')
            ->update([
                'approval_level' => 'ketua_koperasi',
                'status' => DB::raw("CASE WHEN action = 'approved' THEN 'approved' WHEN action = 'rejected' THEN 'rejected' ELSE 'pending' END"),
            ]);
    }

    public function down(): void
    {
        Schema::table('loan_approvals', function (Blueprint $table) {
            $table->dropColumn([
                'approval_level',
                'status',
            ]);
        });
    }
};
