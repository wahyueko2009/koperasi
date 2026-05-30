<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1001', 'name' => 'Kas', 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Uang tunai koperasi.'],
            ['code' => '1002', 'name' => 'Bank', 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Saldo rekening bank koperasi.'],
            ['code' => '1101', 'name' => 'Piutang Anggota', 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Piutang pinjaman atau tagihan ke anggota.'],
            ['code' => '1102', 'name' => 'Piutang Usaha', 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Piutang jasa atau tagihan ke perusahaan.'],
            ['code' => '1201', 'name' => 'Inventori', 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Stok barang retail.'],
            ['code' => '2001', 'name' => 'Utang Usaha', 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Utang ke supplier atau mitra usaha.'],
            ['code' => '2002', 'name' => 'Utang Simpanan Anggota', 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Kewajiban simpanan anggota.'],
            ['code' => '2101', 'name' => 'Utang Simpanan Anggota Legacy', 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Kode legacy untuk simpanan anggota.'],
            ['code' => '2102', 'name' => 'Utang Pinjaman Anggota', 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Kewajiban pinjaman anggota / akun legacy.'],
            ['code' => '3001', 'name' => 'Modal Koperasi', 'account_type' => 'equity', 'normal_balance' => 'credit', 'description' => 'Modal dasar koperasi.'],
            ['code' => '3002', 'name' => 'Saldo Laba', 'account_type' => 'equity', 'normal_balance' => 'credit', 'description' => 'Akumulasi laba ditahan.'],
            ['code' => '4101', 'name' => 'Pendapatan Bunga', 'account_type' => 'income', 'normal_balance' => 'credit', 'description' => 'Pendapatan bunga pinjaman.'],
            ['code' => '4102', 'name' => 'Pendapatan Indomaret', 'account_type' => 'income', 'normal_balance' => 'credit', 'description' => 'Penjualan unit Indomaret.'],
            ['code' => '4103', 'name' => 'Pendapatan Photocopy', 'account_type' => 'income', 'normal_balance' => 'credit', 'description' => 'Penjualan unit Photocopy.'],
            ['code' => '4104', 'name' => 'Pendapatan Outsourcing', 'account_type' => 'income', 'normal_balance' => 'credit', 'description' => 'Pendapatan jasa outsourcing.'],
            ['code' => '5001', 'name' => 'Beban Gaji', 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Beban gaji karyawan.'],
            ['code' => '5002', 'name' => 'Beban Operasional', 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Beban operasional umum.'],
            ['code' => '5003', 'name' => 'Beban Pemeliharaan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Beban maintenance dan repair.'],
            ['code' => '5101', 'name' => 'Beban Penyusutan Kendaraan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Beban penyusutan kendaraan.'],
            ['code' => '5102', 'name' => 'Beban Penyusutan Peralatan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Beban penyusutan peralatan.'],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::updateOrCreate(
                ['code' => $account['code']],
                $account
            );
        }
    }
}
