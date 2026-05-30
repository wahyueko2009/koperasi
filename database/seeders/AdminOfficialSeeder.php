<?php

namespace Database\Seeders;

use App\Models\Official;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminOfficialSeeder extends Seeder
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

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@koperasi.local'],
            [
                'name' => 'Admin',
                'login' => 'admin',
                'email' => 'admin@koperasi.local',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        $official = Official::query()->updateOrCreate(
            ['login' => 'admin'],
            [
                'position_id' => $position->id,
                'name' => $user->name ?: 'Admin',
                'email' => 'admin@koperasi.local',
                'phone' => $user->official?->phone,
                'start_date' => $user->official?->start_date ?? now()->toDateString(),
                'end_date' => null,
                'is_active' => true,
            ],
        );

        $user->forceFill([
            'name' => $user->name ?: 'Admin',
            'role' => 'admin',
            'position_id' => $position->id,
            'official_id' => $official->id,
            'is_active' => true,
        ])->save();
    }
}
