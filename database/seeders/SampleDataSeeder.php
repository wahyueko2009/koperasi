<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\RetailItem;
use App\Models\SavingType;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create members
        $members = [
            [
                'nik' => '3201234567890000',
                'name' => 'Ahmad Subarjo',
                'email' => 'ahmad@example.com',
                'login' => 'ahmad',
                'spouse_name' => 'Rina Subarjo',
                'npwp' => '12.345.678.9-012.000',
                'phone' => '08123456789',
                'address' => 'Jl. Merdeka No. 1, Jakarta',
                'company_unit' => 'Operasional Pusat',
                'account_number' => '001234567890',
                'status' => 'active',
                'membership_type' => 'anggota_biasa',
            ],
            [
                'nik' => '3201234567890001',
                'name' => 'Siti Nurhaliza',
                'email' => 'siti@example.com',
                'login' => 'siti',
                'spouse_name' => 'Fajar Nurhaliza',
                'npwp' => '98.765.432.1-210.000',
                'phone' => '08129876543',
                'address' => 'Jl. Sudirman No. 2, Jakarta',
                'company_unit' => 'Finance Unit',
                'account_number' => '009876543210',
                'status' => 'active',
                'membership_type' => 'anggota_biasa',
            ],
            [
                'nik' => '3201234567890002',
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'login' => 'budi',
                'spouse_name' => 'Dewi Santoso',
                'npwp' => '11.223.344.5-678.000',
                'phone' => '08121111111',
                'address' => 'Jl. Ahmad Yani No. 3, Jakarta',
                'company_unit' => 'Retail Mart',
                'account_number' => '007700112233',
                'status' => 'active',
                'membership_type' => 'anggota_biasa',
            ],
        ];

        foreach ($members as $memberData) {
            Member::updateOrCreate(
                ['nik' => $memberData['nik']],
                $memberData
            );
        }

        // Create retail items
        $retailItems = [
            // Indomaret items
            ['sku' => 'INDO-001', 'name' => 'Mie Instan', 'unit' => 'pcs', 'price' => 2500, 'category' => 'indomaret', 'stock' => 100],
            ['sku' => 'INDO-002', 'name' => 'Kopi Saset', 'unit' => 'pcs', 'price' => 1000, 'category' => 'indomaret', 'stock' => 200],
            ['sku' => 'INDO-003', 'name' => 'Teh Botol', 'unit' => 'botol', 'price' => 5000, 'category' => 'indomaret', 'stock' => 150],
            ['sku' => 'INDO-004', 'name' => 'Gula', 'unit' => 'kg', 'price' => 15000, 'category' => 'indomaret', 'stock' => 50],
            ['sku' => 'INDO-005', 'name' => 'Beras', 'unit' => 'kg', 'price' => 12000, 'category' => 'indomaret', 'stock' => 60],

            // Photocopy items
            ['sku' => 'FOTO-001', 'name' => 'Fotocopy B/W A4', 'unit' => 'lembar', 'price' => 150, 'category' => 'photocopy', 'stock' => 1000],
            ['sku' => 'FOTO-002', 'name' => 'Fotocopy Color A4', 'unit' => 'lembar', 'price' => 500, 'category' => 'photocopy', 'stock' => 500],
            ['sku' => 'FOTO-003', 'name' => 'Fotocopy B/W A3', 'unit' => 'lembar', 'price' => 250, 'category' => 'photocopy', 'stock' => 500],
            ['sku' => 'FOTO-004', 'name' => 'Fotocopy Color A3', 'unit' => 'lembar', 'price' => 800, 'category' => 'photocopy', 'stock' => 300],
            ['sku' => 'FOTO-005', 'name' => 'Cetak Dok', 'unit' => 'lembar', 'price' => 2000, 'category' => 'photocopy', 'stock' => 100],
        ];

        foreach ($retailItems as $itemData) {
            RetailItem::updateOrCreate(
                ['sku' => $itemData['sku']],
                $itemData
            );
        }

        // Create user accounts
        User::updateOrCreate(
            ['email' => 'finance@koperasi.local'],
            [
                'name' => 'Finance Staff',
                'login' => 'finance',
                'password' => bcrypt('password'),
                'role' => 'finance',
            ]
        );

        User::updateOrCreate(
            ['email' => 'retail@koperasi.local'],
            [
                'name' => 'Retail Staff',
                'login' => 'retail',
                'password' => bcrypt('password'),
                'role' => 'staff',
            ]
        );

        // Link members to users
        Member::where('nik', '3201234567890000')->first()?->user()->updateOrCreate(
            ['email' => 'ahmad.user@koperasi.local'],
            [
                'name' => 'Ahmad Subarjo',
                'login' => 'ahmad',
                'email' => 'ahmad.user@koperasi.local',
                'password' => bcrypt('password'),
                'role' => 'member',
            ]
        );

        Member::where('nik', '3201234567890001')->first()?->user()->updateOrCreate(
            ['email' => 'siti.user@koperasi.local'],
            [
                'name' => 'Siti Nurhaliza',
                'login' => 'siti',
                'email' => 'siti.user@koperasi.local',
                'password' => bcrypt('password'),
                'role' => 'member',
            ]
        );
    }
}
