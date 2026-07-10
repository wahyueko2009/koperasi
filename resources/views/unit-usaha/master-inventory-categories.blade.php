@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 pt-6">
                <div class="inline-flex rounded-[1.25rem] bg-slate-100 p-1">
                    <a
                        href="{{ route('unit-usaha.products', ['tab' => 'barang']) }}"
                        class="rounded-xl px-5 py-3 text-sm font-semibold text-slate-500 transition hover:text-slate-700"
                    >
                        Barang / Inventory
                    </a>
                    <a
                        href="{{ route('unit-usaha.products', ['tab' => 'jasa']) }}"
                        class="rounded-xl px-5 py-3 text-sm font-semibold text-slate-500 transition hover:text-slate-700"
                    >
                        Jasa / Service
                    </a>
                    <span class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm">
                        Jenis Barang
                    </span>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-5xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Master</p>
                        <h3 id="inventory-category-form-title" class="mt-2 text-2xl font-bold text-slate-900">Tambah Jenis Barang</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan jenis barang untuk mengelompokkan inventory agar pembelian, kasir, dan stock opname memakai klasifikasi yang seragam.</p>
                    </div>
                </div>

                <form id="inventory-category-form" method="POST" action="{{ route('unit-usaha.master.inventory-categories.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" id="inventory-category-id" name="category_id" value="">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Jenis</label>
                            <input
                                id="inventory-category-code"
                                name="code"
                                value="{{ old('code') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="JBR-ATK"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Jenis</label>
                            <input
                                id="inventory-category-name"
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="Alat Tulis Kantor"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kategori</label>
                            <select
                                id="inventory-category-usage-type"
                                name="usage_type"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            >
                                <option value="barang" @selected(old('usage_type', 'barang') === 'barang')>Barang</option>
                                <option value="jasa" @selected(old('usage_type') === 'jasa')>Jasa</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea
                            id="inventory-category-description"
                            name="description"
                            rows="4"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            placeholder="Catatan penggunaan jenis barang ini di operasional unit usaha."
                        >{{ old('description') }}</textarea>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input id="inventory-category-active" type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', '1') ? 'checked' : '' }}>
                        Jenis barang aktif dan bisa dipilih di master inventory
                    </label>

                    <button id="inventory-category-submit" class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                        Simpan Jenis Barang
                    </button>
                    <button
                        type="button"
                        id="inventory-category-cancel-edit"
                        class="hidden w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Batal Edit
                    </button>
                    <button
                        type="button"
                        id="open-inventory-category-list"
                        class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white transition hover:bg-sky-500"
                    >
                        View Data
                    </button>
                </form>
            </section>
        </div>
    </div>

    <div id="inventory-category-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-inventory-category-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Jenis Barang</h3>
                    </div>
                    <button
                        type="button"
                        id="close-inventory-category-list"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup data jenis barang"
                    >
                        X
                    </button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <label for="inventory-category-status-filter" class="text-sm font-semibold text-slate-700">Filter Status:</label>
                        <select
                            id="inventory-category-status-filter"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition focus:border-slate-400 focus:outline-none"
                        >
                            <option value="all">Semua</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Kode</th>
                                        <th class="px-5 py-3">Nama Jenis</th>
                                        <th class="px-5 py-3">Kategori</th>
                                        <th class="px-5 py-3">Deskripsi</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($categories as $category)
                                        <tr
                                            class="cursor-pointer transition hover:bg-sky-50"
                                            data-category-row
                                            data-status="{{ $category->is_active ? 'active' : 'inactive' }}"
                                            data-id="{{ $category->id }}"
                                            data-code="{{ $category->code }}"
                                            data-name="{{ $category->name }}"
                                            data-usage-type="{{ $category->usage_type }}"
                                            data-description="{{ $category->description ?? '' }}"
                                            data-active="{{ $category->is_active ? '1' : '0' }}"
                                        >
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $category->code }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $category->name }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ ucfirst($category->usage_type) }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-600">{{ $category->description ?: '-' }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $category->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada jenis barang yang tersimpan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('inventory-category-list-modal');
            const openButton = document.getElementById('open-inventory-category-list');
            const closeButton = document.getElementById('close-inventory-category-list');
            const form = document.getElementById('inventory-category-form');
            const formTitle = document.getElementById('inventory-category-form-title');
            const categoryIdInput = document.getElementById('inventory-category-id');
            const codeInput = document.getElementById('inventory-category-code');
            const nameInput = document.getElementById('inventory-category-name');
            const usageTypeInput = document.getElementById('inventory-category-usage-type');
            const descriptionInput = document.getElementById('inventory-category-description');
            const activeInput = document.getElementById('inventory-category-active');
            const submitButton = document.getElementById('inventory-category-submit');
            const cancelEditButton = document.getElementById('inventory-category-cancel-edit');
            const filterSelect = document.getElementById('inventory-category-status-filter');

            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            const applyStatusFilter = (status) => {
                document.querySelectorAll('[data-category-row]').forEach((row) => {
                    const rowStatus = row.dataset.status;
                    const show = status === 'all' || rowStatus === status;
                    row.classList.toggle('hidden', !show);
                });
                if (filterSelect) {
                    filterSelect.value = status;
                }
            };

            const setEditMode = (payload) => {
                if (!payload) return;
                if (formTitle) formTitle.textContent = 'Edit Jenis Barang';
                if (categoryIdInput) categoryIdInput.value = payload.id || '';
                if (codeInput) {
                    codeInput.value = payload.code || '';
                    codeInput.readOnly = true;
                    codeInput.classList.add('bg-slate-50', 'text-slate-500');
                }
                if (nameInput) {
                    nameInput.value = payload.name || '';
                    nameInput.readOnly = true;
                    nameInput.classList.add('bg-slate-50', 'text-slate-500');
                }
                if (usageTypeInput) {
                    usageTypeInput.value = payload.usageType || 'barang';
                    usageTypeInput.disabled = true;
                    usageTypeInput.classList.add('bg-slate-50', 'text-slate-500');
                }
                if (descriptionInput) descriptionInput.value = payload.description || '';
                if (activeInput) activeInput.checked = payload.active === '1';
                if (submitButton) submitButton.textContent = 'Simpan Perubahan Jenis Barang';
                cancelEditButton?.classList.remove('hidden');
                form?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };

            const resetCreateMode = () => {
                if (formTitle) formTitle.textContent = 'Tambah Jenis Barang';
                if (categoryIdInput) categoryIdInput.value = '';
                if (codeInput) {
                    codeInput.value = '';
                    codeInput.readOnly = false;
                    codeInput.classList.remove('bg-slate-50', 'text-slate-500');
                }
                if (nameInput) {
                    nameInput.value = '';
                    nameInput.readOnly = false;
                    nameInput.classList.remove('bg-slate-50', 'text-slate-500');
                }
                if (usageTypeInput) {
                    usageTypeInput.value = 'barang';
                    usageTypeInput.disabled = false;
                    usageTypeInput.classList.remove('bg-slate-50', 'text-slate-500');
                }
                if (descriptionInput) descriptionInput.value = '';
                if (activeInput) activeInput.checked = true;
                if (submitButton) submitButton.textContent = 'Simpan Jenis Barang';
                cancelEditButton?.classList.add('hidden');
            };

            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-inventory-category-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.querySelectorAll('[data-category-row]').forEach((row) => {
                row.addEventListener('click', () => {
                    setEditMode({
                        id: row.dataset.id,
                        code: row.dataset.code,
                        name: row.dataset.name,
                        usageType: row.dataset.usageType,
                        description: row.dataset.description,
                        active: row.dataset.active,
                    });
                    toggleModal(false);
                });
            });
            filterSelect?.addEventListener('change', (event) => {
                applyStatusFilter(event.target.value || 'all');
            });
            cancelEditButton?.addEventListener('click', () => resetCreateMode());
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });
            applyStatusFilter('all');
        })();
    </script>
@endpush
