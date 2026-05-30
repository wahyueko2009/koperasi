<?php

namespace Database\Seeders;

use App\Models\SavingType;
use Illuminate\Database\Seeder;

class SavingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $savingTypes = [
            [
                'code' => 'POKOK',
                'name' => 'Simpanan Pokok',
                'description' => 'Simpanan pokok keanggotaan',
                'is_mandatory' => true,
                'monthly_amount' => null,
            ],
            [
                'code' => 'WAJIB',
                'name' => 'Simpanan Wajib',
                'description' => 'Simpanan wajib bulanan',
                'is_mandatory' => true,
                'monthly_amount' => 100000,
            ],
            [
                'code' => 'SUKARELA',
                'name' => 'Simpanan Sukarela',
                'description' => 'Simpanan sukarela sesuai kemampuan',
                'is_mandatory' => false,
                'monthly_amount' => null,
            ],
        ];

        foreach ($savingTypes as $type) {
            SavingType::create($type);
        }
    }
}
