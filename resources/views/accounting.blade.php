<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akuntansi - Koperasi Digital Mandiri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0F172A',
                        success: '#10B981',
                        danger: '#EF4444',
                        warning: '#F59E0B'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Layout Utama -->
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <div class="w-64 bg-primary text-white flex flex-col">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <span class="text-primary font-bold text-lg">K</span>
                    </div>
                    <div>
                        <p class="font-bold text-xl">KOPERASI</p>
                        <p class="text-xs text-blue-200">DIGITAL MANDIRI</p>
                    </div>
                </div>
            </div>

            <nav class="mt-8 flex-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-6 py-3 hover:bg-blue-700 transition">
                    <i class="fas fa-home text-lg"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('members') }}" class="flex items-center gap-3 px-6 py-3 hover:bg-blue-700 transition">
                    <i class="fas fa-users text-lg"></i>
                    <span>Anggota</span>
                </a>
                <a href="{{ route('simpan-pinjam') }}" class="flex items-center gap-3 px-6 py-3 hover:bg-blue-700 transition">
                    <i class="fas fa-piggy-bank text-lg"></i>
                    <span>Simpan Pinjam</span>
                </a>
                <a href="{{ route('retail') }}" class="flex items-center gap-3 px-6 py-3 hover:bg-blue-700 transition">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span>Unit Retail</span>
                </a>
                <a href="{{ route('accounting') }}" class="flex items-center gap-3 px-6 py-3 bg-blue-700 border-l-4 border-yellow-400">
                    <i class="fas fa-calculator text-lg"></i>
                    <span>Akuntansi</span>
                </a>
                <a href="#" class="flex items-center gap-3 px-6 py-3 hover:bg-blue-700 transition">
                    <i class="fas fa-file-chart-line text-lg"></i>
                    <span>Laporan</span>
                </a>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden" style="background-color: #F3F4F6;">
            <!-- Top Bar -->
            <div class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm">
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input type="text" placeholder="Cari kode akun atau nama..." class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                @php
                    $user = auth()->user();
                    $userName = $user?->name ?? 'Pengguna';
                    $userEmail = $user?->email ?? '-';
                    $userRole = ucwords(str_replace('_', ' ', $user?->role ?? 'user'));
                    $userInitial = strtoupper(substr($userName, 0, 1));
                @endphp
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <button class="text-gray-600 hover:text-gray-900 transition">
                            <i class="fas fa-bell text-xl"></i>
                        </button>
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-danger rounded-full"></span>
                    </div>
                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-2 py-1.5 hover:bg-gray-50">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-gray-200 bg-blue-100 font-bold text-blue-700">
                                {{ $userInitial }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm text-gray-800">{{ $userName }}</p>
                                <p class="text-xs text-gray-500">{{ $userRole }}</p>
                            </div>
                            <span class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </summary>
                        <div class="absolute right-0 z-20 mt-3 w-72 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
                            <div class="border-b border-gray-100 bg-gray-50 px-4 py-4">
                                <p class="font-semibold text-sm text-gray-900">{{ $userName }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $userEmail }}</p>
                                <p class="mt-2 inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $userRole }}</p>
                            </div>
                            <div class="p-2">
                                <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user text-gray-400"></i>
                                    Profil Saya
                                </a>
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-gauge-high text-gray-400"></i>
                                    Dashboard
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-right-from-bracket"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Accounting Content -->
            <div class="flex-1 overflow-auto p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-primary mb-2">Modul Akuntansi - The Engine</h1>
                    <p class="text-gray-600">Chart of Accounts & Journal Entries Management</p>
                </div>

                <!-- Accounting Tabs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex">
                            <button onclick="switchAccountingTab('coa')" class="tab-button px-6 py-4 border-b-2 border-blue-500 text-blue-600 font-medium text-sm">
                                Chart of Accounts
                            </button>
                            <button onclick="switchAccountingTab('journal')" class="tab-button px-6 py-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                                Journal Entries
                            </button>
                            <button onclick="switchAccountingTab('ledger')" class="tab-button px-6 py-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                                General Ledger
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Chart of Accounts Tab -->
                <div id="tab-coa" class="tab-content">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-primary">Chart of Accounts (COA)</h3>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
                                <i class="fas fa-plus"></i>
                                Tambah Akun
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Akun</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Akun</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Normal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Terkini</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <!-- Asset Accounts -->
                                    <tr class="bg-blue-50">
                                        <td class="px-6 py-3 text-sm font-bold text-blue-800">1000-1999</td>
                                        <td class="px-6 py-3 text-sm font-bold text-blue-800">AKTIVA</td>
                                        <td colspan="4" class="px-6 py-3 text-sm text-blue-600 italic">Asset Accounts</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900 pl-12">1001</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Kas</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Asset</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Debit</td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">Rp 4.890.000</td>
                                        <td class="px-6 py-3 text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900 pl-12">1101</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Piutang Anggota</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Asset</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Debit</td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">Rp 4.500.000</td>
                                        <td class="px-6 py-3 text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        </td>
                                    </tr>

                                    <!-- Liability Accounts -->
                                    <tr class="bg-orange-50">
                                        <td class="px-6 py-3 text-sm font-bold text-orange-800">2000-2999</td>
                                        <td class="px-6 py-3 text-sm font-bold text-orange-800">KEWAJIBAN</td>
                                        <td colspan="4" class="px-6 py-3 text-sm text-orange-600 italic">Liability Accounts</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900 pl-12">2101</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Hutang Simpanan</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Liability</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Credit</td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">Rp 2.500.000</td>
                                        <td class="px-6 py-3 text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        </td>
                                    </tr>

                                    <!-- Equity Accounts -->
                                    <tr class="bg-purple-50">
                                        <td class="px-6 py-3 text-sm font-bold text-purple-800">3000-3999</td>
                                        <td class="px-6 py-3 text-sm font-bold text-purple-800">MODAL</td>
                                        <td colspan="4" class="px-6 py-3 text-sm text-purple-600 italic">Equity Accounts</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900 pl-12">3101</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Modal Awal</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Equity</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Credit</td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">Rp 1.000.000</td>
                                        <td class="px-6 py-3 text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        </td>
                                    </tr>

                                    <!-- Income Accounts -->
                                    <tr class="bg-green-50">
                                        <td class="px-6 py-3 text-sm font-bold text-green-800">4000-4999</td>
                                        <td class="px-6 py-3 text-sm font-bold text-green-800">PENDAPATAN</td>
                                        <td colspan="4" class="px-6 py-3 text-sm text-green-600 italic">Income Accounts</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900 pl-12">4101</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Pendapatan Indomaret</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Income</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Credit</td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">Rp 250.000</td>
                                        <td class="px-6 py-3 text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900 pl-12">4201</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Pendapatan Photocopy</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Income</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Credit</td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">Rp 75.000</td>
                                        <td class="px-6 py-3 text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900 pl-12">4301</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Pendapatan Bunga Pinjaman</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Income</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Credit</td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">Rp 65.000</td>
                                        <td class="px-6 py-3 text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        </td>
                                    </tr>

                                    <!-- Expense Accounts -->
                                    <tr class="bg-red-50">
                                        <td class="px-6 py-3 text-sm font-bold text-red-800">5000-5999</td>
                                        <td class="px-6 py-3 text-sm font-bold text-red-800">BEBAN</td>
                                        <td colspan="4" class="px-6 py-3 text-sm text-red-600 italic">Expense Accounts</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900 pl-12">5101</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Beban Operasional</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Expense</td>
                                        <td class="px-6 py-3 text-sm text-gray-900">Debit</td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">Rp 0</td>
                                        <td class="px-6 py-3 text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Journal Entries Tab -->
                <div id="tab-journal" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-primary">Journal Entries</h3>
                            <div class="flex gap-3">
                                <input type="date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
                                    <i class="fas fa-plus"></i>
                                    Manual Entry
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Debit Account</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Debit Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credit Account</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credit Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">2024-03-21</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">TRX-001</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">Setoran Simpanan Pokok Ahmad Subarjo</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">1001 - Kas</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 1.000.000</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">2101 - Hutang Simpanan</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 1.000.000</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">
                                                <i class="fas fa-robot mr-1"></i>System Generated
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">2024-03-21</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">TRX-002</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">Setoran Simpanan Wajib Ahmad Subarjo</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">1001 - Kas</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 500.000</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">2101 - Hutang Simpanan</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 500.000</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">
                                                <i class="fas fa-robot mr-1"></i>System Generated
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">2024-03-21</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">TRX-003</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">Belanja Indomaret Ahmad Subarjo</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">1101 - Piutang Anggota</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 125.000</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">4101 - Pendapatan Indomaret</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 125.000</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">
                                                <i class="fas fa-robot mr-1"></i>System Generated
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">2024-03-20</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">TRX-004</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">Pembayaran Pinjaman Budi Santoso</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">1101 - Piutang Anggota</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 400.000</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">1001 - Kas</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 400.000</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">
                                                <i class="fas fa-robot mr-1"></i>System Generated
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">2024-03-20</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">TRX-005</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">Bunga Pinjaman Budi Santoso</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">1101 - Piutang Anggota</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 38.000</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">4301 - Pendapatan Bunga</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 38.000</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">
                                                <i class="fas fa-robot mr-1"></i>System Generated
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 bg-yellow-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">2024-03-19</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">JE-001</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">Manual Journal Entry - Adjustment</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">5101 - Beban Operasional</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 50.000</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">1001 - Kas</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 50.000</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full font-medium">
                                                <i class="fas fa-user mr-1"></i>Manual Entry
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- General Ledger Tab -->
                <div id="tab-ledger" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-primary">General Ledger</h3>
                            <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option>Semua Akun</option>
                                <option>1001 - Kas</option>
                                <option>1101 - Piutang Anggota</option>
                                <option>2101 - Hutang Simpanan</option>
                            </select>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akun</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr class="bg-blue-50">
                                        <td colspan="6" class="px-6 py-3 text-sm font-bold text-blue-800">1001 - Kas</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">2024-03-21</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">1001 - Kas</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">Setoran Simpanan Pokok</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-success">Rp 1.000.000</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 1.000.000</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">2024-03-21</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">1001 - Kas</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">Setoran Simpanan Wajib</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-success">Rp 500.000</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp 1.500.000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchAccountingTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            // Add active class to selected button
            event.target.classList.remove('border-transparent', 'text-gray-500');
            event.target.classList.add('border-blue-500', 'text-blue-600');
        }
    </script>

</body>
</html>
