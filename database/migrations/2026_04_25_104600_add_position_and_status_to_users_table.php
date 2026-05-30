<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable()->after('member_id');
            $table->boolean('is_active')->default(true)->after('role');

            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
        });

        $positions = DB::table('positions')->pluck('id', 'code');

        DB::table('users')
            ->where('role', 'admin')
            ->update(['position_id' => $positions['ADMIN_KOPERASI'] ?? null, 'is_active' => true]);

        DB::table('users')
            ->where('role', 'finance')
            ->update(['position_id' => $positions['FINANCE'] ?? null, 'is_active' => true]);

        DB::table('users')
            ->where('role', 'member')
            ->update(['position_id' => $positions['ANGGOTA'] ?? null, 'is_active' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropColumn(['position_id', 'is_active']);
        });
    }
};
