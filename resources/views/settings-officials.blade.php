@extends('layouts.app')

@php
    $routeName = request()->route()?->getName();
    $title = 'Manage Pengurus - Koperasi Digital Mandiri';
    $sectionLabel = 'Pengaturan Sistem';
    $pageTitle = 'Manage Pengurus';
    $pageDescription = 'Kelola data pengurus dan petugas operasional, lalu tambah atau edit datanya dari satu area pengaturan.';
    $headerTabs = [
        [
            'label' => 'Manage Anggota',
            'route' => 'members',
            'active' => false,
        ],
        [
            'label' => 'Manage Pengurus',
            'route' => 'settings.officials',
            'active' => true,
        ],
    ];
    $activeOfficials = $officials->where('is_active', true)->count();
    $withLogin = $officials->filter(fn ($official) => filled($official->login) || $official->user !== null)->count();
@endphp

@section('content')
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Pengguna Operasional</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $officials->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Data pengurus dan petugas yang tercatat di sistem.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pengguna Aktif</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $activeOfficials }}</p>
                <p class="mt-2 text-sm text-slate-500">Masih aktif dan siap menjalankan tugas.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Punya Akun Login</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $withLogin }}</p>
                <p class="mt-2 text-sm text-slate-500">Pengguna yang sudah bisa masuk ke sistem.</p>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <form method="GET" action="{{ route('settings.officials') }}" class="grid gap-3 md:grid-cols-[1.3fr_0.7fr_auto]">
                    <div>
                        <label for="search" class="mb-2 block text-sm font-medium text-slate-700">Cari pengguna</label>
                        <input
                            id="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            type="text"
                            placeholder="Cari nama, email, login, atau jabatan"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                        >
                    </div>
                    <div>
                        <label for="status" class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                        >
                            <option value="">Semua status</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button class="rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Terapkan
                        </button>
                        <a href="{{ route('settings.officials') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('settings.officials.export.excel', array_filter(['search' => $filters['search'] ?? '', 'status' => $filters['status'] ?? ''])) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <i class="fas fa-file-excel"></i>
                        Download Excel
                    </a>
                    <button
                        type="button"
                        id="open-create-official-modal"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-accent px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/20 transition hover:bg-teal-700"
                    >
                        <i class="fas fa-plus"></i>
                        Tambah Pengurus
                    </button>
                </div>
            </div>
        </div>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Daftar Pengguna Operasional</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Daftar Manage Pengurus</h2>
                </div>

                <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    <th class="px-5 py-3">Pengurus</th>
                                    <th class="px-5 py-3">Jabatan</th>
                                    <th class="px-5 py-3">Kontak</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Masa Jabatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($officials as $official)
                                    @php
                                        $detail = [
                                            'id' => $official->id,
                                            'name' => $official->name,
                                            'position_id' => $official->position_id,
                                            'position_name' => $official->position?->name ?? '-',
                                            'email' => $official->email ?? '',
                                            'login' => $official->login ?: $official->user?->login ?? '',
                                            'phone' => $official->phone ?? '',
                                            'start_date' => optional($official->start_date)?->format('Y-m-d') ?? '',
                                            'end_date' => optional($official->end_date)?->format('Y-m-d') ?? '',
                                            'is_active' => $official->is_active,
                                        ];
                                    @endphp
                                    <tr class="cursor-pointer transition hover:bg-slate-50" data-official-detail='@json($detail)'>
                                        <td class="px-5 py-3">
                                            <p class="font-semibold text-slate-900">{{ $official->name }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $official->login ?: ($official->user?->login ?? '-') }}</p>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-slate-700">{{ $official->position?->name ?? '-' }}</td>
                                        <td class="px-5 py-3 text-sm text-slate-700">
                                            <p>{{ $official->email ?: '-' }}</p>
                                            <p class="mt-1 text-slate-400">{{ $official->phone ?: 'Telepon belum diisi' }}</p>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $official->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $official->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-slate-700">
                                            <p>{{ optional($official->start_date)?->format('d M Y') ?? '-' }}</p>
                                            <p class="mt-1 text-slate-400">{{ optional($official->end_date)?->format('d M Y') ?? 'Belum ditentukan' }}</p>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada data pengguna operasional.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

        </section>
    </div>

    <div id="official-create-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/55 px-4 py-8">
        <div class="w-full max-w-2xl rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Input Data</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Form Pengurus</h2>
                </div>
                <button type="button" data-close-create-official-modal class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-slate-500 hover:bg-slate-50">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-6">
                <form method="POST" action="{{ route('settings.officials.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama</label>
                            <input name="name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Nama lengkap">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jabatan</label>
                            <select name="position_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih jabatan</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}">{{ $position->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-slate-500">Gunakan pilihan `Unit Usaha` untuk petugas yang akan mengakses modul Unit Usaha.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                            <input name="email" type="email" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="email@koperasi.local">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Login</label>
                            <input name="login" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="login pengurus">
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Telepon</label>
                            <input name="phone" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="08xxxxxxxxxx">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                            <input name="password" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Minimal 6 karakter">
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Mulai Jabatan</label>
                            <input name="start_date" type="date" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akhir Jabatan</label>
                            <input name="end_date" type="date" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Konfirmasi Password</label>
                        <input name="password_confirmation" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Ulangi password">
                    </div>
                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                        Pengguna aktif
                    </label>
                    <div class="flex items-center justify-between">
                        <button type="button" data-close-create-official-modal class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tutup</button>
                        <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">Simpan Pengurus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="official-detail-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/55 px-4 py-6">
        <div class="flex h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Detail Pengguna</p>
                    <h2 id="official-modal-title" class="text-2xl font-bold text-slate-900">Pengurus</h2>
                </div>
                <button type="button" data-close-official-modal class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-slate-500 hover:bg-slate-50">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6">
                    <div class="mb-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-[1.25rem] bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Jabatan</p>
                            <p id="official-summary-position" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</p>
                            <p id="official-summary-status" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                        </div>
                    </div>

                <form id="official-edit-form" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama</label>
                            <input id="official-name" name="name" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jabatan</label>
                            <select id="official-position" name="position_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}">{{ $position->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-slate-500">Pilih `Unit Usaha` jika akun ini digunakan untuk operasional modul Unit Usaha.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                            <input id="official-email" name="email" type="email" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Login</label>
                            <input id="official-login" name="login" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Telepon</label>
                            <input id="official-phone" name="phone" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Password Baru</label>
                            <input id="official-password" name="password" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Kosongkan jika tidak diubah">
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Mulai Jabatan</label>
                            <input id="official-start-date" name="start_date" type="date" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akhir Jabatan</label>
                            <input id="official-end-date" name="end_date" type="date" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Konfirmasi Password</label>
                        <input id="official-password-confirmation" name="password_confirmation" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Ulangi password baru">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                        <select id="official-is-active" name="is_active" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="button" data-close-official-modal class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tutup</button>
                        <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">Update Pengurus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const createModal = document.getElementById('official-create-modal');
            const modal = document.getElementById('official-detail-modal');
            const form = document.getElementById('official-edit-form');
            const rows = document.querySelectorAll('[data-official-detail]');
            const openCreateButton = document.getElementById('open-create-official-modal');

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            const closeCreateModal = () => {
                createModal.classList.add('hidden');
                createModal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            openCreateButton?.addEventListener('click', () => {
                createModal.classList.remove('hidden');
                createModal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            });

            rows.forEach((row) => {
                row.addEventListener('click', () => {
                    const detail = JSON.parse(row.dataset.officialDetail);
                    form.action = `{{ url('/settings/officials') }}/${detail.id}`;
                    document.getElementById('official-modal-title').textContent = detail.name || 'Pengurus';
                    document.getElementById('official-summary-position').textContent = detail.position_name || '-';
                    document.getElementById('official-summary-status').textContent = detail.is_active ? 'Aktif' : 'Nonaktif';
                    document.getElementById('official-name').value = detail.name || '';
                    document.getElementById('official-position').value = detail.position_id || '';
                    document.getElementById('official-email').value = detail.email || '';
                    document.getElementById('official-login').value = detail.login || '';
                    document.getElementById('official-phone').value = detail.phone || '';
                    document.getElementById('official-password').value = '';
                    document.getElementById('official-password-confirmation').value = '';
                    document.getElementById('official-start-date').value = detail.start_date || '';
                    document.getElementById('official-end-date').value = detail.end_date || '';
                    document.getElementById('official-is-active').value = detail.is_active ? '1' : '0';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                });
            });

            document.querySelectorAll('[data-close-official-modal]').forEach((button) => button.addEventListener('click', closeModal));
            document.querySelectorAll('[data-close-create-official-modal]').forEach((button) => button.addEventListener('click', closeCreateModal));
            modal.addEventListener('click', (event) => event.target === modal && closeModal());
            createModal?.addEventListener('click', (event) => event.target === createModal && closeCreateModal());
            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                if (createModal && !createModal.classList.contains('hidden')) {
                    closeCreateModal();
                }

                if (!modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
@endpush
