<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('positions')->updateOrInsert(
            ['code' => 'UNIT_USAHA'],
            [
                'name' => 'Unit Usaha',
                'approval_scope' => 'unit_usaha',
                'description' => 'Petugas operasional unit usaha yang mengakses modul POS dan inventory.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('positions')->where('code', 'UNIT_USAHA')->delete();
    }
};
