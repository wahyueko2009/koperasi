<?php

namespace Database\Seeders;

use App\Models\Official;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministratorUserSeeder extends Seeder
{
    public function run(): void
    {
        $position = Position::query()->firstOrCreate(
            ['code' => 'ADMIN'],
            [
                'name' => 'Admin',
                'approval_scope' => null,
                'description' => 'Jabatan pengurus koperasi.',
                'is_active' => true,
            ],
        );

        $official = Official::query()->updateOrCreate(
            ['login' => 'administrator'],
            [
                'position_id' => $position->id,
                'name' => 'Administrator Koperasi',
                'email' => 'administrator@koperasi.local',
                'phone' => null,
                'start_date' => now()->toDateString(),
                'end_date' => null,
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['login' => 'administrator'],
            [
                'name' => 'Administrator Koperasi',
                'email' => 'administrator@koperasi.local',
                'password' => Hash::make('password'),
                'role' => 'administrator',
                'official_id' => $official->id,
                'position_id' => $position->id,
                'is_active' => true,
            ],
        );
    }
}
