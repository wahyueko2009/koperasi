<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Koperasi Digital Mandiri' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0f172a',
                        accent: '#0f766e',
                        success: '#10b981',
                        danger: '#ef4444',
                        warning: '#f59e0b',
                        ink: '#111827',
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-900">
    @php
        $routeName = request()->route()?->getName();
        $activeDomain = 'admin_koperasi';
        $user = auth()->user();
        $isAdministrator = (bool) $user?->isAdministrator();
        $canAccessUnitUsaha = (bool) $user?->canAccessUnitUsaha();
        $savingLoanSidebarChildren = collect($headerTabs ?? [])
            ->filter(fn (array $tab) => !empty($tab['route']) && in_array($tab['route'], [
                'simpan-pinjam.loans.applications',
                'simpan-pinjam.loans.approvals',
                'simpan-pinjam.loans.monitoring',
                'simpan-pinjam.loans.completed',
            ], true))
            ->map(fn (array $tab) => [
                'label' => $tab['label'],
                'route' => $tab['route'],
                'badge' => $tab['badge'] ?? null,
            ])
            ->values()
            ->all();
        $activeModule = match (true) {
            str_starts_with((string) $routeName, 'settings.') => 'settings',
            str_starts_with((string) $routeName, 'members') => 'settings',
            str_starts_with((string) $routeName, 'simpan-pinjam') => 'loans',
            str_starts_with((string) $routeName, 'fixed-assets.') => 'fixed_assets',
            str_starts_with((string) $routeName, 'unit-usaha.') => 'business',
            str_starts_with((string) $routeName, 'piutang-usaha.') => 'receivables',
            str_starts_with((string) $routeName, 'gl.') => 'general_ledger',
            $routeName === 'reports' => 'reports',
            $routeName === 'dashboard' => 'members',
            $routeName === 'retail' => 'business',
            $routeName === 'accounting' => 'finance',
            $routeName === 'member-portal' => 'member_portal',
            default => 'members',
        };

        $navigation = collect([
            [
                'label' => 'Dashboard',
                'icon' => 'fas fa-house',
                'route' => 'dashboard',
                'active' => $routeName === 'dashboard',
                'visible' => true,
            ],
            [
                'label' => 'Simpan Pinjam',
                'icon' => 'fas fa-hand-holding-dollar',
                'route' => 'simpan-pinjam',
                'active' => str_starts_with((string) $routeName, 'simpan-pinjam'),
                'children' => $savingLoanSidebarChildren,
                'show_chevron' => true,
                'visible' => true,
            ],
            [
                'label' => 'Unit Usaha',
                'icon' => 'fas fa-store',
                'route' => 'unit-usaha.dashboard',
                'active' => str_starts_with((string) $routeName, 'unit-usaha.')
                    || $routeName === 'retail',
                'children' => [
                    ['label' => 'Kasir / POS', 'route' => 'unit-usaha.pos'],
                    ['label' => 'Stok & Inventory', 'route' => 'unit-usaha.inventory'],
                    ['label' => 'Master Produk/Jasa', 'route' => 'unit-usaha.master.services'],
                    ['label' => 'Pembelian / Barang Masuk', 'route' => 'unit-usaha.purchases'],
                    ['label' => 'Stock Opname', 'route' => 'unit-usaha.stock-opname'],
                    ['label' => 'Laporan Unit Usaha', 'route' => 'unit-usaha.reports'],
                ],
                'visible' => $canAccessUnitUsaha,
            ],
            [
                'label' => 'Buku Besar / GL',
                'icon' => 'fas fa-book-open-reader',
                'route' => 'gl.dashboard',
                'active' => str_starts_with((string) $routeName, 'gl.'),
                'children' => [
                    ['label' => 'Daftar Akun / COA', 'route' => 'gl.accounts'],
                    ['label' => 'Kelompok Akun', 'route' => 'gl.account-groups'],
                    ['label' => 'Periode Akuntansi', 'route' => 'gl.periods'],
                    ['label' => 'Jurnal Umum', 'route' => 'gl.journals'],
                    ['label' => 'Posting Jurnal', 'route' => 'gl.posting'],
                    ['label' => 'Mapping Akun', 'route' => 'gl.mappings'],
                    ['label' => 'Pusat Biaya', 'route' => 'gl.cost-centers'],
                    ['label' => 'Buku Besar', 'route' => 'gl.ledgers'],
                    ['label' => 'Neraca Saldo', 'route' => 'gl.trial-balance'],
                    ['label' => 'Laporan Keuangan', 'route' => 'gl.financial-reports'],
                    ['label' => 'Tutup Buku', 'route' => 'gl.closing'],
                    ['label' => 'Audit Jurnal', 'route' => 'gl.audit'],
                ],
                'visible' => $isAdministrator,
            ],
            [
                'label' => 'Piutang Usaha',
                'icon' => 'fas fa-file-invoice-dollar',
                'route' => 'piutang-usaha.dashboard',
                'active' => str_starts_with((string) $routeName, 'piutang-usaha.'),
                'children' => [
                    ['label' => 'Data Perusahaan', 'route' => 'piutang-usaha.companies'],
                    ['label' => 'Kontrak Jasa', 'route' => 'piutang-usaha.contracts'],
                    ['label' => 'Tagihan / Invoice', 'route' => 'piutang-usaha.invoices'],
                    ['label' => 'Pembayaran Piutang', 'route' => 'piutang-usaha.payments'],
                    ['label' => 'Laporan', 'route' => 'piutang-usaha.reports'],
                ],
                'visible' => $isAdministrator,
            ],
            [
                'label' => 'Fixed Aset & Depresiasi',
                'icon' => 'fas fa-building-circle-arrow-right',
                'route' => 'fixed-assets.assets',
                'active' => str_starts_with((string) $routeName, 'fixed-assets.'),
                'children' => [
                    ['label' => 'Fixed Aset', 'route' => 'fixed-assets.assets'],
                    ['label' => 'Depresiasi', 'route' => 'fixed-assets.depreciation'],
                    ['label' => 'Mutasi Aset', 'route' => 'fixed-assets.mutations'],
                    ['label' => 'Laporan FA', 'route' => 'fixed-assets.reports'],
                ],
                'visible' => $canAccessUnitUsaha,
            ],
            [
                'label' => 'Setting',
                'icon' => 'fas fa-user-tie',
                'route' => 'settings.officials',
                'active' => str_starts_with((string) $routeName, 'settings.')
                    || str_starts_with((string) $routeName, 'members'),
                'children' => [
                    ['label' => 'Manage Pengurus', 'route' => 'settings.officials'],
                    ['label' => 'Manage Anggota', 'route' => 'members'],
                ],
                'visible' => $isAdministrator,
            ],
            [
                'label' => 'Laporan',
                'icon' => 'fas fa-file-lines',
                'route' => 'reports',
                'active' => $routeName === 'reports',
                'visible' => $isAdministrator,
            ],
        ])->filter(fn (array $item) => $item['visible'])->values()->all();

        $userName = $user?->name ?? 'Pengguna';
        $userEmail = $user?->email ?? '-';
        $userRole = ucwords(str_replace('_', ' ', $user?->role ?? 'user'));
        $userInitial = strtoupper(substr($userName, 0, 1));
    @endphp

    <div class="min-h-screen lg:flex">
        <aside class="hidden w-80 shrink-0 bg-primary text-white lg:flex lg:flex-col">
            <div class="border-b border-white/10 px-6 py-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-accent font-bold text-white shadow-lg shadow-accent/30">
                        KD
                    </div>
                    <div>
                        <p class="text-lg font-bold tracking-wide">Koperasi Digital</p>
                        <p class="text-sm text-slate-300">Sistem operasional koperasi</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-2 px-4 py-6">
                @foreach ($navigation as $item)
                    <div class="rounded-2xl {{ $item['active'] ? 'bg-white/10' : '' }}">
                        <a
                            href="{{ route($item['route']) }}"
                            class="flex items-center justify-between gap-3 rounded-2xl px-4 py-3 transition hover:bg-white/10"
                        >
                            <span class="flex items-center gap-3">
                                <i class="{{ $item['icon'] }} w-5 text-center text-slate-300"></i>
                                <span class="font-medium">{{ $item['label'] }}</span>
                            </span>
                            @if (!empty($item['children']) || !empty($item['show_chevron']))
                                <i class="fas fa-chevron-right text-xs text-slate-400"></i>
                            @endif
                        </a>

                        @if (!empty($item['children']) && $item['active'])
                            <div class="space-y-1 px-4 pb-3">
                                @foreach ($item['children'] as $child)
                                    <a
                                        href="{{ route($child['route']) }}"
                                        class="flex items-center justify-between gap-3 rounded-xl px-11 py-2 text-sm transition {{
                                            $routeName === $child['route']
                                                || ($child['route'] === 'unit-usaha.master.services' && str_starts_with((string) $routeName, 'unit-usaha.master.'))
                                                ? 'bg-white/10 text-white'
                                                : 'text-slate-300 hover:bg-white/10 hover:text-white'
                                        }}"
                                    >
                                        <span>{{ $child['label'] }}</span>
                                        @if (!empty($child['badge']))
                                            <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-rose-500 px-2 py-0.5 text-xs font-bold text-white">
                                                {{ $child['badge'] }}
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>

            <div class="border-t border-white/10 px-6 py-5">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Domain Aktif</p>
                <p class="mt-2 text-sm font-semibold text-white">Admin Koperasi</p>
                <p class="mt-3 text-xs uppercase tracking-[0.3em] text-slate-400">Tahap Aktif</p>
                <p class="mt-2 text-sm font-semibold text-white">{{ match ($activeModule) {
                    'settings' => 'Menu Setting',
                    'business' => 'Modul Unit Usaha',
                    'receivables' => 'Modul Piutang Usaha',
                    'general_ledger' => 'Modul Buku Besar / GL',
                    'fixed_assets' => 'Modul Fixed Aset',
                    'reports' => 'Menu Laporan',
                    default => 'Modul Anggota',
                } }}</p>
                <p class="mt-1 text-sm text-slate-300">{{ $activeModule === 'business'
                    ? 'Gunakan submenu Unit Usaha untuk masuk ke dashboard, kasir, stok, dan laporan operasional.'
                    : ($activeModule === 'receivables'
                        ? 'Gunakan submenu Piutang Usaha untuk masuk ke perusahaan, kontrak jasa, invoice, pembayaran, dan laporan.'
                    : ($activeModule === 'general_ledger'
                        ? 'Gunakan submenu Buku Besar / GL untuk masuk ke COA, jurnal, posting, buku besar, dan laporan keuangan.'
                    : ($activeModule === 'fixed_assets'
                        ? 'Gunakan submenu Fixed Aset untuk mengelola master aset tetap dan proses depresiasi periodik.'
                        : 'Entry data utama sekarang dipusatkan langsung di halaman anggota dan pengurus.'))) }}</p>
            </div>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col">
            <header class="border-b border-slate-200 bg-white/95 px-4 py-4 shadow-sm backdrop-blur lg:px-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">{{ $sectionLabel ?? 'Operasional Koperasi' }}</p>
                        <h1 class="mt-1 text-2xl font-bold text-ink">{{ $pageTitle ?? 'Koperasi Digital Mandiri' }}</h1>
                        @if (!empty($pageDescription))
                            <p class="mt-1 text-sm text-slate-500">{{ $pageDescription }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 self-start lg:self-auto">
                        <div class="hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right sm:block">
                            <p class="text-sm font-semibold text-slate-800">{{ $userName }}</p>
                            <p class="text-xs text-slate-500">{{ $userRole }} · {{ $userEmail }}</p>
                        </div>
                        <details class="relative">
                            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-accent/10 font-bold text-accent">
                                    {{ $userInitial }}
                                </div>
                                <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                            </summary>
                            <div class="absolute right-0 z-20 mt-3 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                                <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $userName }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $userEmail }}</p>
                                    <p class="mt-2 inline-flex rounded-full bg-accent/10 px-2.5 py-1 text-xs font-semibold text-accent">{{ $userRole }}</p>
                                </div>
                                <div class="p-2">
                                    <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm text-slate-700 hover:bg-slate-50">
                                        <i class="fas fa-user text-slate-400"></i>
                                        Profil Saya
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

            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p class="font-semibold">Ada data yang perlu diperbaiki.</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
