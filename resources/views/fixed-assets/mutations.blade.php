@extends('layouts.app')

@php
    $disposedCount = $assets->where('status', 'disposed')->count();
    $maintenanceCount = $assets->where('status', 'maintenance')->count();
@endphp

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Histori</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($mutations->count()) }}</p>
                <p class="mt-2 text-sm text-slate-500">Semua perpindahan dan perubahan status aset.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Maintenance</p>
                <p class="mt-3 text-3xl font-bold text-amber-700">{{ number_format($maintenanceCount) }}</p>
                <p class="mt-2 text-sm text-slate-500">Aset yang sedang maintenance saat ini.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Disposed</p>
                <p class="mt-3 text-3xl font-bold text-rose-700">{{ number_format($disposedCount) }}</p>
                <p class="mt-2 text-sm text-slate-500">Aset yang sudah dihentikan dari penggunaan.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Aset Aktif</p>
                <p class="mt-3 text-3xl font-bold text-emerald-700">{{ number_format($assets->where('status', 'active')->count()) }}</p>
                <p class="mt-2 text-sm text-slate-500">Masih aktif untuk operasional koperasi.</p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Mutasi</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Update Lokasi / Status Aset</h3>
                </div>

                <form method="POST" action="{{ route('fixed-assets.mutations.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Pilih Aset</label>
                        <select name="fixed_asset_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Pilih aset</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}" @selected((string) old('fixed_asset_id') === (string) $asset->id)>{{ $asset->asset_code }} - {{ $asset->asset_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Mutasi</label>
                            <input name="mutation_date" type="date" value="{{ old('mutation_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Mutasi</label>
                            <select name="mutation_type" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach (['location' => 'Perpindahan Lokasi', 'status' => 'Perubahan Status', 'maintenance' => 'Maintenance', 'disposal' => 'Penghentian / Disposal'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('mutation_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Lokasi Tujuan</label>
                        <input name="to_location" value="{{ old('to_location') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Gudang, cabang, ruang kerja, atau lokasi baru">
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Status Baru</label>
                            <select name="to_status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Ikuti jenis mutasi</option>
                                @foreach (['active' => 'Aktif', 'idle' => 'Idle', 'maintenance' => 'Maintenance', 'disposed' => 'Disposed'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('to_status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nilai Pelepasan</label>
                            <input name="disposal_value" type="number" min="0" step="0.01" value="{{ old('disposal_value') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="0">
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-cyan-100 bg-cyan-50 px-5 py-4">
                        <p class="text-sm font-semibold text-cyan-900">Akun Disposal Otomatis</p>
                        <p class="mt-1 text-sm text-cyan-800">Isi bagian ini jika mutasi bertipe `Penghentian / Disposal` dan kamu ingin sistem membuat jurnal pelepasan aset secara otomatis.</p>

                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Penerimaan</label>
                                <select name="disposal_debit_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                    <option value="">Pilih akun kas/bank</option>
                                    @foreach ($disposalDebitAccounts as $account)
                                        <option value="{{ $account->id }}" @selected((string) old('disposal_debit_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Laba Disposal</label>
                                <select name="disposal_gain_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                    <option value="">Pilih akun laba</option>
                                    @foreach ($incomeAccounts as $account)
                                        <option value="{{ $account->id }}" @selected((string) old('disposal_gain_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Rugi Disposal</label>
                                <select name="disposal_loss_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                    <option value="">Pilih akun rugi</option>
                                    @foreach ($expenseAccounts as $account)
                                        <option value="{{ $account->id }}" @selected((string) old('disposal_loss_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="mt-4 flex items-center gap-3 rounded-2xl border border-cyan-200 bg-white px-4 py-3 text-sm text-cyan-900">
                            <input type="checkbox" name="auto_post_disposal" value="1" class="rounded border-cyan-300" {{ old('auto_post_disposal') ? 'checked' : '' }}>
                            Otomatis posting jurnal pelepasan aset saat disposal disimpan
                        </label>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                        <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Alasan perpindahan, kerusakan, maintenance, atau penghentian aset.">{{ old('notes') }}</textarea>
                    </div>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                        Simpan Mutasi Aset
                    </button>
                </form>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Riwayat Mutasi</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Histori Perubahan Aset</h3>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($mutations as $mutation)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $mutation->asset?->asset_code }} - {{ $mutation->asset?->asset_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ optional($mutation->mutation_date)->format('d M Y') }} · {{ strtoupper($mutation->mutation_type) }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                    {{ strtoupper($mutation->to_status ?: $mutation->asset?->status ?: '-') }}
                                </span>
                            </div>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    <p class="font-semibold text-slate-900">Lokasi</p>
                                    <p class="mt-1">{{ $mutation->from_location ?: '-' }} → {{ $mutation->to_location ?: '-' }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    <p class="font-semibold text-slate-900">Status</p>
                                    <p class="mt-1">{{ $mutation->from_status ?: '-' }} → {{ $mutation->to_status ?: '-' }}</p>
                                </div>
                            </div>
                            @if ($mutation->notes || $mutation->disposal_value)
                                <div class="mt-3 text-sm text-slate-600">
                                    @if ($mutation->disposal_value)
                                        <p>Nilai pelepasan: Rp {{ number_format((float) $mutation->disposal_value, 0, ',', '.') }}</p>
                                    @endif
                                    @if ($mutation->disposal_reference_number)
                                        <p class="mt-1">Batch jurnal: {{ $mutation->disposal_reference_number }}</p>
                                    @endif
                                    @if ($mutation->notes)
                                        <p class="mt-1">{{ $mutation->notes }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada mutasi aset yang tercatat.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
