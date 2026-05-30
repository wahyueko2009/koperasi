@extends('layouts.app')

@php
    $activeAssets = $assets->where('is_active', true)->count();
    $assetValue = $assets->sum(fn ($asset) => (float) $asset->acquisition_value);
    $bookValue = $assets->sum(fn ($asset) => (float) $asset->book_value);
    $categoryCount = $assets->pluck('category')->filter()->unique()->count();
@endphp

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Aset</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($assets->count()) }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $activeAssets }} aset aktif dalam register.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Nilai Perolehan</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format($assetValue, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Akumulasi nilai awal seluruh aset tetap.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Nilai Buku</p>
                <p class="mt-3 text-3xl font-bold text-emerald-700">Rp {{ number_format($bookValue, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Posisi nilai buku berdasarkan depresiasi yang sudah tercatat.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Kategori</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($categoryCount) }}</p>
                <p class="mt-2 text-sm text-slate-500">Kendaraan, peralatan, inventaris, dan kategori lain.</p>
            </div>
        </section>

        <div class="mx-auto max-w-6xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="mb-5 flex flex-col gap-4 border-b border-slate-100 pb-5 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Register Aset</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Tambah Fixed Aset</h3>
                        <p class="mt-2 text-sm text-slate-500">Simpan data induk aset tetap beserta akun aset, akun akumulasi, akun beban, dan opsi posting jurnal perolehan.</p>
                    </div>
                    <button
                        type="button"
                        id="open-fixed-asset-list"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        View Data
                    </button>
                </div>

                <form method="POST" action="{{ route('fixed-assets.assets.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Aset</label>
                            <input name="asset_code" value="{{ old('asset_code', $assetCode) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="FA-202605-001">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Aset</label>
                            <input name="asset_name" value="{{ old('asset_name') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Mobil Operasional">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kategori</label>
                            <input name="category" value="{{ old('category') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Kendaraan / Peralatan / Inventaris">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Perolehan</label>
                            <input name="acquisition_date" type="date" value="{{ old('acquisition_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Mulai Digunakan</label>
                            <input name="in_service_date" type="date" value="{{ old('in_service_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Supplier / Vendor</label>
                            <input name="supplier_name" value="{{ old('supplier_name') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="PT Sumber Kendaraan">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Lokasi</label>
                            <input name="location" value="{{ old('location') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Kantor Pusat / Gudang / Cabang">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Pusat Biaya</label>
                            <select name="cost_center_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Tanpa pusat biaya</option>
                                @foreach ($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}" @selected((string) old('cost_center_id') === (string) $costCenter->id)>{{ $costCenter->code }} - {{ $costCenter->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nilai Perolehan</label>
                            <input name="acquisition_value" type="number" min="0.01" step="0.01" value="{{ old('acquisition_value') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="250000000">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nilai Residu</label>
                            <input name="residual_value" type="number" min="0" step="0.01" value="{{ old('residual_value', 0) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Umur Manfaat</label>
                            <input name="useful_life_months" type="number" min="1" value="{{ old('useful_life_months', 60) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kondisi</label>
                            <select name="condition_status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach (['baik' => 'Baik', 'cukup' => 'Cukup', 'perlu-perbaikan' => 'Perlu Perbaikan'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('condition_status', 'baik') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Status Aset</label>
                            <select name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach (['active' => 'Aktif', 'idle' => 'Idle', 'maintenance' => 'Maintenance'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Kredit Perolehan</label>
                            <select name="acquisition_credit_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih akun lawan saat perolehan</option>
                                @foreach ($creditAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) old('acquisition_credit_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Aset</label>
                            <select name="asset_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih akun aset</option>
                                @foreach ($assetAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) old('asset_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Akumulasi</label>
                            <select name="accumulated_depreciation_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih akun akumulasi</option>
                                @foreach ($contraAssetAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) old('accumulated_depreciation_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Beban</label>
                            <select name="depreciation_expense_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih akun beban</option>
                                @foreach ($expenseAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) old('depreciation_expense_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Spesifikasi aset, nomor polisi, nomor seri, atau catatan lain.">{{ old('notes') }}</textarea>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-cyan-100 bg-cyan-50 px-4 py-3 text-sm text-cyan-800">
                        <input type="checkbox" name="auto_post_acquisition" value="1" class="rounded border-cyan-300" {{ old('auto_post_acquisition') ? 'checked' : '' }}>
                        Otomatis buat jurnal perolehan aset saat data disimpan
                    </label>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                        Simpan Fixed Aset
                    </button>
                </form>
            </section>
        </div>
    </div>

    <div id="fixed-asset-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-fixed-asset-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Register Aset</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Fixed Aset</h3>
                    </div>
                    <button type="button" id="close-fixed-asset-list" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">X</button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Aset</th>
                                        <th class="px-5 py-3">Perolehan</th>
                                        <th class="px-5 py-3">Depresiasi</th>
                                        <th class="px-5 py-3">Akun</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($assets as $asset)
                                        @php
                                            $detail = [
                                                'id' => $asset->id,
                                                'asset_code' => $asset->asset_code,
                                                'asset_name' => $asset->asset_name,
                                                'category' => $asset->category,
                                                'acquisition_date' => optional($asset->acquisition_date)->format('Y-m-d'),
                                                'in_service_date' => optional($asset->in_service_date)->format('Y-m-d'),
                                                'supplier_name' => $asset->supplier_name,
                                                'location' => $asset->location,
                                                'condition_status' => $asset->condition_status,
                                                'status' => $asset->status,
                                                'acquisition_value' => (float) $asset->acquisition_value,
                                                'residual_value' => (float) $asset->residual_value,
                                                'useful_life_months' => (int) $asset->useful_life_months,
                                                'asset_account_id' => $asset->asset_account_id,
                                                'accumulated_depreciation_account_id' => $asset->accumulated_depreciation_account_id,
                                                'depreciation_expense_account_id' => $asset->depreciation_expense_account_id,
                                                'acquisition_credit_account_id' => $asset->acquisition_credit_account_id,
                                                'cost_center_id' => $asset->cost_center_id,
                                                'notes' => $asset->notes,
                                                'is_active' => (bool) $asset->is_active,
                                            ];
                                        @endphp
                                        <tr class="cursor-pointer hover:bg-slate-50" data-fixed-asset-detail='@json($detail)'>
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-slate-900">{{ $asset->asset_code }} - {{ $asset->asset_name }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $asset->category }} {{ $asset->location ? '· ' . $asset->location : '' }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="text-sm font-semibold text-slate-900">Rp {{ number_format((float) $asset->acquisition_value, 0, ',', '.') }}</p>
                                                <p class="mt-1 text-xs text-slate-500">Residu Rp {{ number_format((float) $asset->residual_value, 0, ',', '.') }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="text-sm font-semibold text-slate-900">{{ number_format((int) $asset->useful_life_months) }} bulan</p>
                                                <p class="mt-1 text-xs text-slate-500">Nilai buku Rp {{ number_format((float) $asset->book_value, 0, ',', '.') }}</p>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-slate-700">
                                                <p>{{ $asset->assetAccount?->code ?: '-' }} / {{ $asset->accumulatedDepreciationAccount?->code ?: '-' }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $asset->depreciationExpenseAccount?->code ?: '-' }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $asset->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($asset->status === 'maintenance' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                                    {{ strtoupper($asset->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada fixed aset yang tersimpan.</td>
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

    <div id="fixed-asset-detail-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/55 px-4 py-6">
        <div class="flex h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Detail Fixed Aset</p>
                    <h2 id="fixed-asset-modal-title" class="text-2xl font-bold text-slate-900">Fixed Aset</h2>
                </div>
                <button type="button" data-close-fixed-asset-detail class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-slate-500 hover:bg-slate-50">
                    X
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6">
                <form id="fixed-asset-edit-form" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Aset</label>
                            <input id="edit-asset-code" name="asset_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Aset</label>
                            <input id="edit-asset-name" name="asset_name" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kategori</label>
                            <input id="edit-category" name="category" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Perolehan</label>
                            <input id="edit-acquisition-date" name="acquisition_date" type="date" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Mulai Digunakan</label>
                            <input id="edit-in-service-date" name="in_service_date" type="date" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Supplier / Vendor</label>
                            <input id="edit-supplier-name" name="supplier_name" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Lokasi</label>
                            <input id="edit-location" name="location" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Pusat Biaya</label>
                            <select id="edit-cost-center-id" name="cost_center_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Tanpa pusat biaya</option>
                                @foreach ($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}">{{ $costCenter->code }} - {{ $costCenter->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nilai Perolehan</label>
                            <input id="edit-acquisition-value" name="acquisition_value" type="number" min="0.01" step="0.01" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nilai Residu</label>
                            <input id="edit-residual-value" name="residual_value" type="number" min="0" step="0.01" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Umur Manfaat</label>
                            <input id="edit-useful-life-months" name="useful_life_months" type="number" min="1" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kondisi</label>
                            <select id="edit-condition-status" name="condition_status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="baik">Baik</option>
                                <option value="cukup">Cukup</option>
                                <option value="perlu-perbaikan">Perlu Perbaikan</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Status Aset</label>
                            <select id="edit-status" name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="active">Aktif</option>
                                <option value="idle">Idle</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="disposed">Disposed</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Kredit Perolehan</label>
                            <select id="edit-acquisition-credit-account-id" name="acquisition_credit_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih akun lawan saat perolehan</option>
                                @foreach ($creditAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Aset</label>
                            <select id="edit-asset-account-id" name="asset_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach ($assetAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Akumulasi</label>
                            <select id="edit-accumulated-account-id" name="accumulated_depreciation_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach ($contraAssetAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Beban</label>
                            <select id="edit-expense-account-id" name="depreciation_expense_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach ($expenseAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                        <textarea id="edit-notes" name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></textarea>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input id="edit-is-active" type="checkbox" name="is_active" value="1" class="rounded border-slate-300">
                        Aset aktif dalam register
                    </label>

                    <div class="flex items-center justify-between">
                        <button type="button" data-close-fixed-asset-detail class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tutup</button>
                        <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">Update Fixed Aset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('fixed-asset-list-modal');
            const openButton = document.getElementById('open-fixed-asset-list');
            const closeButton = document.getElementById('close-fixed-asset-list');
            const detailModal = document.getElementById('fixed-asset-detail-modal');
            const detailForm = document.getElementById('fixed-asset-edit-form');

            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            const toggleDetailModal = (show) => {
                if (!detailModal) return;
                detailModal.classList.toggle('hidden', !show);
                detailModal.classList.toggle('flex', show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-fixed-asset-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.querySelectorAll('[data-fixed-asset-detail]').forEach((row) => {
                row.addEventListener('click', () => {
                    const detail = JSON.parse(row.dataset.fixedAssetDetail);
                    document.getElementById('fixed-asset-modal-title').textContent = `${detail.asset_code} - ${detail.asset_name}`;
                    detailForm.action = `{{ url('/fixed-aset-depresiasi/fixed-aset') }}/${detail.id}`;
                    document.getElementById('edit-asset-code').value = detail.asset_code || '';
                    document.getElementById('edit-asset-name').value = detail.asset_name || '';
                    document.getElementById('edit-category').value = detail.category || '';
                    document.getElementById('edit-acquisition-date').value = detail.acquisition_date || '';
                    document.getElementById('edit-in-service-date').value = detail.in_service_date || '';
                    document.getElementById('edit-supplier-name').value = detail.supplier_name || '';
                    document.getElementById('edit-location').value = detail.location || '';
                    document.getElementById('edit-condition-status').value = detail.condition_status || 'baik';
                    document.getElementById('edit-status').value = detail.status || 'active';
                    document.getElementById('edit-acquisition-value').value = detail.acquisition_value ?? '';
                    document.getElementById('edit-residual-value').value = detail.residual_value ?? '';
                    document.getElementById('edit-useful-life-months').value = detail.useful_life_months ?? '';
                    document.getElementById('edit-asset-account-id').value = detail.asset_account_id || '';
                    document.getElementById('edit-accumulated-account-id').value = detail.accumulated_depreciation_account_id || '';
                    document.getElementById('edit-expense-account-id').value = detail.depreciation_expense_account_id || '';
                    document.getElementById('edit-acquisition-credit-account-id').value = detail.acquisition_credit_account_id || '';
                    document.getElementById('edit-cost-center-id').value = detail.cost_center_id || '';
                    document.getElementById('edit-notes').value = detail.notes || '';
                    document.getElementById('edit-is-active').checked = !!detail.is_active;
                    toggleDetailModal(true);
                });
            });
            detailModal?.querySelectorAll('[data-close-fixed-asset-detail]').forEach((element) => {
                element.addEventListener('click', () => toggleDetailModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    toggleModal(false);
                    toggleDetailModal(false);
                }
            });
        })();
    </script>
@endpush
