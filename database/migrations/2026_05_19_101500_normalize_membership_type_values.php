<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('members')
            ->whereIn('membership_type', ['regular', 'calon', 'luar_biasa', 'kehormatan'])
            ->update(['membership_type' => 'anggota_biasa']);
    }

    public function down(): void
    {
        DB::table('members')
            ->where('membership_type', 'anggota_biasa')
            ->update(['membership_type' => 'regular']);
    }
};
