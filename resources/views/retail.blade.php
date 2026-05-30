<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Retail - Koperasi Digital Mandiri</title>
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
                @if (auth()->user()?->isAdministrator())
                    <a href="{{ route('members') }}" class="flex items-center gap-3 px-6 py-3 hover:bg-blue-700 transition">
                        <i class="fas fa-users text-lg"></i>
                        <span>Anggota</span>
                    </a>
                @endif
                <a href="{{ route('simpan-pinjam') }}" class="flex items-center gap-3 px-6 py-3 hover:bg-blue-700 transition">
                    <i class="fas fa-piggy-bank text-lg"></i>
                    <span>Simpan Pinjam</span>
                </a>
                <a href="{{ route('retail') }}" class="flex items-center gap-3 px-6 py-3 bg-blue-700 border-l-4 border-yellow-400">
                    <i class="fas fa-store text-lg"></i>
                    <span>Unit Usaha</span>
                </a>
                <a href="{{ route('accounting') }}" class="flex items-center gap-3 px-6 py-3 hover:bg-blue-700 transition">
                    <i class="fas fa-calculator text-lg"></i>
                    <span>Akuntansi</span>
                </a>
                @if (auth()->user()?->isAdministrator())
                    <a href="{{ route('reports') }}" class="flex items-center gap-3 px-6 py-3 hover:bg-blue-700 transition">
                        <i class="fas fa-file-chart-line text-lg"></i>
                        <span>Laporan</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden" style="background-color: #F3F4F6;">
            <!-- Top Bar -->
            <div class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm">
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input type="text" placeholder="Cari NIK/Nama Anggota" class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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

            <!-- Retail POS Content -->
            <div class="flex-1 overflow-auto p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-primary mb-2">Unit Usaha - POS System</h1>
                    <p class="text-gray-600">Point of sale untuk unit usaha koperasi</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- POS Form -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-xl font-bold text-primary mb-6">Transaksi Baru</h3>

                            <form id="posForm" class="space-y-6">
                                <!-- Field 1: Member Search -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2"></i>Scan Barcode / Ketik NIK Anggota
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="memberSearch" placeholder="Ketik NIK atau nama anggota..."
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg">
                                        <i class="fas fa-search absolute right-3 top-4 text-gray-400"></i>
                                    </div>
                                    <!-- Member Info Display -->
                                    <div id="memberInfo" class="mt-3 p-3 bg-blue-50 rounded-lg hidden">
                                        <div class="flex items-center gap-3">
                                            <img id="memberPhoto" src="https://via.placeholder.com/40" alt="Member" class="w-10 h-10 rounded-full">
                                            <div>
                                                <p id="memberName" class="font-medium text-gray-900"></p>
                                                <p id="memberNIK" class="text-sm text-gray-600"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Field 2: Transaction Type & Amount -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-store mr-2"></i>Jenis Transaksi
                                        </label>
                                        <select id="transactionType" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg">
                                            <option value="indomaret">Indomaret</option>
                                            <option value="photocopy">Photocopy</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-money-bill-wave mr-2"></i>Nominal Belanja
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-3 text-gray-500 text-lg">Rp</span>
                                            <input type="number" id="amount" placeholder="0"
                                                   class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg text-right">
                                        </div>
                                    </div>
                                </div>

                                <!-- Field 3: Payment Method -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-credit-card mr-2"></i>Metode Pembayaran
                                    </label>
                                    <select id="paymentMethod" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg">
                                        <option value="salary_cut">Potong Gaji (Piutang)</option>
                                        <option value="sukarela">Saldo Sukarela</option>
                                        <option value="cash">Tunai</option>
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Potong Gaji akan menambah piutang anggota
                                    </p>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="w-full bg-success hover:bg-green-700 text-white py-4 px-6 rounded-lg font-bold text-lg transition flex items-center justify-center gap-3">
                                    <i class="fas fa-check-circle"></i>
                                    Proses Transaksi
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Transaction History & Quick Actions -->
                    <div class="space-y-6">
                        <!-- Success Notification (Hidden by default) -->
                        <div id="successNotification" class="bg-success text-white p-4 rounded-lg hidden">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-2xl"></i>
                                <div>
                                    <p class="font-bold">Transaksi Berhasil!</p>
                                    <p class="text-sm opacity-90">Rp 125.000 - Indomaret</p>
                                </div>
                            </div>
                            <button class="mt-3 w-full bg-white text-success py-2 px-4 rounded font-medium hover:bg-gray-100 transition">
                                <i class="fas fa-print mr-2"></i>Cetak Struk Sederhana
                            </button>
                        </div>

                        <!-- Today's Summary -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h4 class="text-lg font-bold text-primary mb-4">Ringkasan Hari Ini</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total Transaksi</span>
                                    <span class="font-bold text-primary">24</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total Pendapatan</span>
                                    <span class="font-bold text-success">Rp 2.450.000</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Piutang Baru</span>
                                    <span class="font-bold text-warning">Rp 850.000</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Transactions -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h4 class="text-lg font-bold text-primary mb-4">Transaksi Terakhir</h4>
                            <div class="space-y-3 max-h-64 overflow-y-auto">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-shopping-cart text-green-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">Ahmad Subarjo</p>
                                            <p class="text-xs text-gray-500">Indomaret</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800">Rp 125.000</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-print text-orange-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">Siti Nurhaliza</p>
                                            <p class="text-xs text-gray-500">Photocopy</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800">Rp 15.000</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-piggy-bank text-blue-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">Budi Santoso</p>
                                            <p class="text-xs text-gray-500">Indomaret</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800">Rp 75.000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mock member data for demo
        const members = [
            { nik: '1234567890123456', name: 'Ahmad Subarjo', email: 'ahmad@example.com' },
            { nik: '2345678901234567', name: 'Siti Nurhaliza', email: 'siti@example.com' },
            { nik: '3456789012345678', name: 'Budi Santoso', email: 'budi@example.com' }
        ];

        // Member search functionality
        document.getElementById('memberSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const memberInfo = document.getElementById('memberInfo');
            const memberName = document.getElementById('memberName');
            const memberNIK = document.getElementById('memberNIK');

            if (searchTerm.length >= 3) {
                const member = members.find(m =>
                    m.nik.includes(searchTerm) ||
                    m.name.toLowerCase().includes(searchTerm)
                );

                if (member) {
                    memberName.textContent = member.name;
                    memberNIK.textContent = `NIK: ${member.nik}`;
                    memberInfo.classList.remove('hidden');
                } else {
                    memberInfo.classList.add('hidden');
                }
            } else {
                memberInfo.classList.add('hidden');
            }
        });

        // Form submission
        document.getElementById('posForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const amount = document.getElementById('amount').value;
            const transactionType = document.getElementById('transactionType').value;
            const paymentMethod = document.getElementById('paymentMethod').value;

            if (!amount || amount <= 0) {
                alert('Masukkan nominal belanja yang valid');
                return;
            }

            // Show success notification
            const notification = document.getElementById('successNotification');
            notification.classList.remove('hidden');

            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 5000);

            // Reset form
            document.getElementById('posForm').reset();
            document.getElementById('memberInfo').classList.add('hidden');

            console.log('Transaction processed:', { amount, transactionType, paymentMethod });
        });

        // Format number input
        document.getElementById('amount').addEventListener('input', function(e) {
            // Allow only numbers
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>

</body>
</html>
