@extends('layouts.app')

@php
    $todayOpnames = $stockOpnames->where('opname_date', now()->toDateString())->count();
    $adjustedItems = $stockOpnames->flatMap->items->filter(fn ($item) => $item->difference !== 0)->count();
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-5xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="mb-5 border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Opname</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Input Stock Opname</h3>
                        <p class="mt-2 text-sm text-slate-500">Masukkan hasil pengecekan fisik di satu panel. Riwayat opname dibuka saat dibutuhkan.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('unit-usaha.stock-opname.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor Opname</label>
                            <input name="opname_number" value="{{ old('opname_number', $opnameNumber) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Opname</label>
                            <input name="opname_date" type="date" value="{{ old('opname_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Detail Opname</p>
                                <p class="mt-1 text-xs text-slate-500">Pilih barang, cek stok fisik, lalu sistem akan menyesuaikan stok master sesuai hasil opname.</p>
                            </div>
                            <button type="button" id="add-opname-row" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Tambah Baris
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed border-collapse">
                                <thead class="bg-slate-100">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="w-[28%] border-b border-slate-200 px-4 py-3">Barang</th>
                                        <th class="w-[14%] border-b border-slate-200 px-4 py-3">Stok Sistem</th>
                                        <th class="w-[14%] border-b border-slate-200 px-4 py-3">Stok Fisik</th>
                                        <th class="w-[14%] border-b border-slate-200 px-4 py-3">Selisih</th>
                                        <th class="w-[20%] border-b border-slate-200 px-4 py-3">Catatan</th>
                                        <th class="w-[10%] border-b border-slate-200 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="opname-items" class="bg-white">
                                    @php
                                        $oldItems = old('items', [['inventory_id' => '', 'physical_stock' => 0, 'notes' => '']]);
                                    @endphp
                                    @foreach ($oldItems as $index => $item)
                                        <tr class="opname-row border-b border-slate-200 align-top">
                                            <td class="px-4 py-2">
                                                <select name="items[{{ $index }}][inventory_id]" class="inventory-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                                    <option value="">Pilih barang</option>
                                                    @foreach ($inventories as $inventory)
                                                        <option
                                                            value="{{ $inventory->id }}"
                                                            data-stock="{{ $inventory->stock }}"
                                                            @selected((string) ($item['inventory_id'] ?? '') === (string) $inventory->id)
                                                        >
                                                            {{ $inventory->name }} ({{ $inventory->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" class="system-stock w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm" value="0" readonly>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input name="items[{{ $index }}][physical_stock]" type="number" min="0" value="{{ $item['physical_stock'] ?? 0 }}" class="physical-stock w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" class="difference-stock w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm" value="0" readonly>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input name="items[{{ $index }}][notes]" value="{{ $item['notes'] ?? '' }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Catatan">
                                            </td>
                                            <td class="px-4 py-2">
                                                <button type="button" class="remove-row w-full rounded-lg border border-rose-200 px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan Opname</label>
                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan umum hasil stock opname.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                        Sistem akan menghitung nilai penyesuaian stok berdasarkan harga beli terakhir, lalu otomatis membuat jurnal jika total penyesuaiannya tidak nol.
                    </div>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                        Simpan Stock Opname
                    </button>
                    <button
                        type="button"
                        id="open-opname-history"
                        class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white transition hover:bg-sky-500"
                    >
                        View Data
                    </button>
                </form>
            </section>
        </div>
    </div>

    <div id="opname-history-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-opname-history></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Riwayat Opname</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Stock Opname</h3>
                    </div>
                    <button
                        type="button"
                        id="close-opname-history"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup riwayat opname"
                    >
                        X
                    </button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ $stockOpnames->count() }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Hari Ini</p>
                            <p class="mt-2 text-xl font-bold text-emerald-800">{{ $todayOpnames }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-amber-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Item Disesuaikan</p>
                            <p class="mt-2 text-xl font-bold text-amber-800">{{ $adjustedItems }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Nomor</th>
                                        <th class="px-5 py-3">Tanggal</th>
                                        <th class="px-5 py-3">Jumlah Item</th>
                                        <th class="px-5 py-3">Ringkasan</th>
                                        <th class="px-5 py-3">Penyesuaian</th>
                                        <th class="px-5 py-3">Nilai</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($stockOpnames as $opname)
                                        @php
                                            $adjustmentCount = $opname->items->filter(fn ($item) => $item->difference !== 0)->count();
                                        @endphp
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $opname->opname_number }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ optional($opname->opname_date)->format('d M Y') }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $opname->items->count() }} item</td>
                                            <td class="px-5 py-4 text-sm text-slate-600">{{ $opname->items->pluck('inventory.name')->filter()->take(3)->implode(', ') ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $adjustmentCount }} item selisih</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $opname->adjustment_value, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $opname->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ ucfirst($opname->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada transaksi stock opname.</td>
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
            const container = document.getElementById('opname-items');
            const addButton = document.getElementById('add-opname-row');
            const modal = document.getElementById('opname-history-modal');
            const openHistoryButton = document.getElementById('open-opname-history');
            const closeHistoryButton = document.getElementById('close-opname-history');
            if (!container || !addButton) return;

            const inventoryOptions = {!! $inventoryOptionsJson !!};

            const toggleHistory = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            const buildOptions = () => {
                let html = '<option value="">Pilih barang</option>';
                inventoryOptions.forEach((inventory) => {
                    html += `<option value="${inventory.id}" data-stock="${inventory.stock}">${inventory.name} (${inventory.code})</option>`;
                });
                return html;
            };

            const refreshRow = (row) => {
                const select = row.querySelector('.inventory-select');
                const systemInput = row.querySelector('.system-stock');
                const physicalInput = row.querySelector('.physical-stock');
                const differenceInput = row.querySelector('.difference-stock');

                const recalc = () => {
                    const selected = select.options[select.selectedIndex];
                    const systemStock = Number(selected?.dataset?.stock ?? 0);
                    const physicalStock = Number(physicalInput.value || 0);
                    systemInput.value = systemStock;
                    differenceInput.value = physicalStock - systemStock;
                };

                select.addEventListener('change', recalc);
                physicalInput.addEventListener('input', recalc);

                row.querySelector('.remove-row')?.addEventListener('click', () => {
                    if (container.querySelectorAll('.opname-row').length === 1) {
                        select.value = '';
                        systemInput.value = 0;
                        physicalInput.value = 0;
                        differenceInput.value = 0;
                        return;
                    }
                    row.remove();
                    reindexRows();
                });

                recalc();
            };

            const reindexRows = () => {
                [...container.querySelectorAll('.opname-row')].forEach((row, index) => {
                    row.querySelector('.inventory-select').name = `items[${index}][inventory_id]`;
                    row.querySelector('.physical-stock').name = `items[${index}][physical_stock]`;
                    row.querySelector('input[name*="[notes]"]').name = `items[${index}][notes]`;
                });
            };

            addButton.addEventListener('click', () => {
                const index = container.querySelectorAll('.opname-row').length;
                const row = document.createElement('tr');
                row.className = 'opname-row border-b border-slate-200 align-top';
                row.innerHTML = `
                    <td class="px-4 py-2">
                        <select name="items[${index}][inventory_id]" class="inventory-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                            ${buildOptions()}
                        </select>
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" class="system-stock w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm" value="0" readonly>
                    </td>
                    <td class="px-4 py-2">
                        <input name="items[${index}][physical_stock]" type="number" min="0" value="0" class="physical-stock w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" class="difference-stock w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm" value="0" readonly>
                    </td>
                    <td class="px-4 py-2">
                        <input name="items[${index}][notes]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Catatan">
                    </td>
                    <td class="px-4 py-2">
                        <button type="button" class="remove-row w-full rounded-lg border border-rose-200 px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                    </td>
                `;
                container.appendChild(row);
                refreshRow(row);
            });

            openHistoryButton?.addEventListener('click', () => toggleHistory(true));
            closeHistoryButton?.addEventListener('click', () => toggleHistory(false));
            modal?.querySelectorAll('[data-close-opname-history]').forEach((element) => {
                element.addEventListener('click', () => toggleHistory(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleHistory(false);
            });

            container.querySelectorAll('.opname-row').forEach(refreshRow);
        })();
    </script>
@endpush
