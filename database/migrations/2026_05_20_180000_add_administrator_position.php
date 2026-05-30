<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('positions')->updateOrInsert(
            ['code' => 'ADMINISTRATOR'],
            [
                'name' => 'Administrator',
                'approval_scope' => null,
                'description' => 'Administrator sistem yang memiliki akses ke menu setting.',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('positions')->where('code', 'ADMINISTRATOR')->delete();
    }
};
