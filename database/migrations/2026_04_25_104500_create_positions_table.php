<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('approval_scope')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('positions')->insert([
            [
                'code' => 'ADMIN_KOPERASI',
                'name' => 'Admin Koperasi',
                'approval_scope' => 'operasional',
                'description' => 'Mengelola pengajuan, input transaksi, dan operasional koperasi.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FINANCE',
                'name' => 'Finance',
                'approval_scope' => 'approval_finance',
                'description' => 'Memastikan ketersediaan dana dan memberi approval tahap finance.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'KETUA',
                'name' => 'Ketua Koperasi',
                'approval_scope' => 'approval_ketua',
                'description' => 'Memberi persetujuan akhir pada proses pinjaman.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ANGGOTA',
                'name' => 'Anggota',
                'approval_scope' => null,
                'description' => 'Pengguna portal anggota untuk melihat pinjaman dan angsuran.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
