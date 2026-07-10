@extends('layouts.app')

@php
    $todaySales = $sales->where('sale_date', now()->toDateString());
    $todayRevenue = $todaySales->sum('total_amount');
    $filteredRevenue = $filteredSales->sum('total_amount');
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-5xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="mb-5 border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Kasir</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Input Transaksi</h3>
                        <p class="mt-2 text-sm text-slate-500">Form ini disiapkan untuk kasir toko umum. Transaksi langsung tunai tanpa pilih anggota.</p>
                    </div>
                </div>

                    <form method="POST" action="{{ $editingSale ? route('unit-usaha.pos.update', $editingSale) : route('unit-usaha.pos.store') }}" class="space-y-5">
                        @csrf
                        @if ($editingSale)
                            @method('PUT')
                        @endif
                        <input type="hidden" name="category" value="indomaret">

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor Transaksi</label>
                            <input name="sale_number" value="{{ old('sale_number', $saleNumber) }}" readonly class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-600">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal</label>
                            <input name="sale_date" type="date" value="{{ old('sale_date', $editingSale?->sale_date?->toDateString() ?? now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Metode Bayar</label>
                            <select name="payment_method" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="cash" @selected(old('payment_method', $editingSale?->payment_method ?? 'cash') === 'cash')>Tunai</option>
                            </select>
                        </div>
                    </div>

                    @if ($editingSale)
                        <div class="rounded-[1.25rem] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Sedang edit transaksi draft `{{ $editingSale->sale_number }}`. Transaksi hanya bisa diedit di hari yang sama dan sebelum diposting.
                        </div>
                    @elseif ($editBlockedMessage)
                        <div class="rounded-[1.25rem] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $editBlockedMessage }}
                        </div>
                    @endif

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Item Penjualan</p>
                                <p class="mt-1 text-xs text-slate-500">Harga otomatis mengikuti master item dan tidak bisa diubah dari form kasir.</p>
                            </div>
                            <button type="button" id="add-sale-row" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Tambah Baris
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed border-collapse">
                                <thead class="bg-slate-100">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="w-[16%] border-b border-slate-200 px-4 py-3">Tipe</th>
                                        <th class="w-[30%] border-b border-slate-200 px-4 py-3">Item</th>
                                        <th class="w-[12%] border-b border-slate-200 px-4 py-3">Qty</th>
                                        <th class="w-[16%] border-b border-slate-200 px-4 py-3">Harga</th>
                                        <th class="w-[16%] border-b border-slate-200 px-4 py-3">Jumlah</th>
                                        <th class="w-[10%] border-b border-slate-200 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="sale-items" class="bg-white">
                                    @foreach ($formItems as $index => $item)
                                        <tr class="sale-row border-b border-slate-200 align-top">
                                            <td class="px-4 py-2">
                                                <select name="items[{{ $index }}][item_type]" class="item-type w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                                    <option value="inventory" @selected(($item['item_type'] ?? 'inventory') === 'inventory')>Barang</option>
                                                    <option value="service" @selected(($item['item_type'] ?? '') === 'service')>Jasa</option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2">
                                                <select name="items[{{ $index }}][item_id]" data-value="{{ $item['item_id'] ?? '' }}" class="item-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"></select>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input name="items[{{ $index }}][quantity]" type="number" min="1" value="{{ $item['quantity'] ?? 1 }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input name="items[{{ $index }}][unit_price]" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? 0 }}" class="unit-price w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-600" readonly>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input
                                                    type="text"
                                                    value="Rp {{ number_format(((int) ($item['quantity'] ?? 1)) * ((float) ($item['unit_price'] ?? 0)), 0, ',', '.') }}"
                                                    class="line-total w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700"
                                                    readonly
                                                >
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

                    <div class="rounded-[1.5rem] border border-cyan-100 bg-cyan-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Total Transaksi</p>
                        <p id="sale-grand-total" class="mt-2 text-2xl font-bold text-cyan-900">Rp 0</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan transaksi kasir.">{{ old('notes', $editingSale?->notes) }}</textarea>
                    </div>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                        {{ $editingSale ? 'Update Transaksi POS' : 'Simpan Transaksi POS' }}
                    </button>
                    @if ($editingSale)
                        <a href="{{ route('unit-usaha.pos', ['filter_date' => $filterDate, 'show_history' => 1]) }}" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-center font-semibold text-slate-700 hover:bg-slate-50">
                            Batal Edit
                        </a>
                    @endif
                    <button
                        type="button"
                        id="open-sales-history"
                        class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white transition hover:bg-sky-500"
                    >
                        View Data
                    </button>
                </form>
            </section>
        </div>
    </div>

    <div id="sales-history-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-sales-history></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative h-[94vh] w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Riwayat Penjualan</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Transaksi Kasir</h3>
                    </div>
                    <button
                        type="button"
                        id="close-sales-history"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup riwayat"
                    >
                        X
                    </button>
                </div>

                <div class="flex h-[calc(94vh-92px)] flex-col gap-6 overflow-hidden px-6 py-6">
                    <form method="GET" action="{{ route('unit-usaha.pos') }}" class="grid gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4 md:grid-cols-[1fr_auto]">
                        <input type="hidden" name="show_history" value="1">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Filter Tanggal</label>
                            <input type="date" name="filter_date" value="{{ $filterDate }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3">
                        </div>
                        <button class="self-end rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                            Tampilkan
                        </button>
                    </form>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-[1.25rem] bg-white px-4 py-3 ring-1 ring-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Data Tanggal</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ \Carbon\Carbon::parse($filterDate)->translatedFormat('d M Y') }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-white px-4 py-3 ring-1 ring-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Jumlah Transaksi</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ $filteredSales->count() }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-white px-4 py-3 ring-1 ring-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Omzet Tanggal Ini</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">Rp {{ number_format((float) $filteredRevenue, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="h-full overflow-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Nomor</th>
                                        <th class="px-5 py-3">Tanggal</th>
                                        <th class="px-5 py-3">Mode</th>
                                        <th class="px-5 py-3">Jumlah Item</th>
                                        <th class="px-5 py-3">Ringkasan</th>
                                        <th class="px-5 py-3">Total</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($filteredSales as $sale)
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $sale->sale_number }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ optional($sale->sale_date)->format('d M Y') }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">Tunai | Umum</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $sale->items->count() }} item</td>
                                            <td class="px-5 py-4 text-sm text-slate-600">{{ $sale->items->pluck('item_name')->take(3)->implode(', ') ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $sale->total_amount, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $sale->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($sale->status) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                @php
                                                    $canEdit = ! $sale->is_posted
                                                        && $sale->status !== 'posted'
                                                        && optional($sale->sale_date)->isToday()
                                                        && in_array($sale->source_module, [null, 'unit-usaha'], true)
                                                        && $sale->source_reference_type === null
                                                        && $sale->source_reference_id === null;
                                                @endphp
                                                @if ($canEdit)
                                                    <a href="{{ route('unit-usaha.pos', ['edit' => $sale->id, 'filter_date' => $filterDate]) }}" class="inline-flex rounded-xl border border-sky-200 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-50">
                                                        Edit
                                                    </a>
                                                @else
                                                    <span class="text-xs font-semibold text-slate-400">Terkunci</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada transaksi kasir pada tanggal ini.</td>
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
            const serviceOptions = {!! $serviceOptionsJson !!};
            const inventoryOptions = {!! $inventoryOptionsJson !!};
            const container = document.getElementById('sale-items');
            const addButton = document.getElementById('add-sale-row');
            const modal = document.getElementById('sales-history-modal');
            const openHistoryButton = document.getElementById('open-sales-history');
            const closeHistoryButton = document.getElementById('close-sales-history');
            if (!container || !addButton) return;

            const optionHtml = (type) => {
                const items = type === 'inventory' ? inventoryOptions : serviceOptions;
                let html = '<option value="">Pilih item</option>';
                items.forEach((item) => {
                    const meta = type === 'inventory'
                        ? `${item.code} - stok ${item.stock} ${item.unit}`
                        : `${item.code} - ${item.unit}`;
                    html += `<option value="${item.id}" data-price="${item.price}">${item.name} (${meta})</option>`;
                });
                return html;
            };

            const hydrateRow = (row) => {
                const typeSelect = row.querySelector('.item-type');
                const itemSelect = row.querySelector('.item-select');
                const priceInput = row.querySelector('.unit-price');
                const quantityInput = row.querySelector('input[name*="[quantity]"]');
                const lineTotalInput = row.querySelector('.line-total');

                const formatCurrency = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;
                const updateLineTotal = () => {
                    const quantity = Number(quantityInput?.value || 0);
                    const unitPrice = Number(priceInput?.value || 0);
                    const total = quantity * unitPrice;
                    if (lineTotalInput) lineTotalInput.value = formatCurrency(total);
                    updateGrandTotal();
                };

                const refreshOptions = () => {
                    const currentValue = itemSelect.dataset.value || itemSelect.value;
                    itemSelect.innerHTML = optionHtml(typeSelect.value);
                    if (currentValue) itemSelect.value = currentValue;
                };

                refreshOptions();

                typeSelect.addEventListener('change', () => {
                    itemSelect.dataset.value = '';
                    refreshOptions();
                    priceInput.value = 0;
                    updateLineTotal();
                });

                itemSelect.addEventListener('change', () => {
                    const selected = itemSelect.options[itemSelect.selectedIndex];
                    priceInput.value = selected?.dataset?.price || 0;
                    updateLineTotal();
                });

                quantityInput?.addEventListener('input', updateLineTotal);

                row.querySelector('.remove-row')?.addEventListener('click', () => {
                    if (container.querySelectorAll('.sale-row').length === 1) {
                        row.querySelectorAll('input').forEach((input) => {
                            if (input.type === 'number') input.value = input.name.includes('[quantity]') ? 1 : 0;
                        });
                        itemSelect.value = '';
                        updateLineTotal();
                        return;
                    }
                    row.remove();
                    reindexRows();
                    updateGrandTotal();
                });

                updateLineTotal();
            };

            const reindexRows = () => {
                [...container.querySelectorAll('.sale-row')].forEach((row, index) => {
                    row.querySelector('.item-type').name = `items[${index}][item_type]`;
                    row.querySelector('.item-select').name = `items[${index}][item_id]`;
                    row.querySelector('input[name*="[quantity]"]').name = `items[${index}][quantity]`;
                    row.querySelector('input[name*="[unit_price]"]').name = `items[${index}][unit_price]`;
                });
            };

            const updateGrandTotal = () => {
                const totalElement = document.getElementById('sale-grand-total');
                if (!totalElement) return;
                const total = [...container.querySelectorAll('.sale-row')].reduce((sum, row) => {
                    const quantity = Number(row.querySelector('input[name*="[quantity]"]')?.value || 0);
                    const unitPrice = Number(row.querySelector('.unit-price')?.value || 0);
                    return sum + (quantity * unitPrice);
                }, 0);
                totalElement.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(total)}`;
            };

            addButton.addEventListener('click', () => {
                const index = container.querySelectorAll('.sale-row').length;
                const row = document.createElement('tr');
                row.className = 'sale-row border-b border-slate-200 align-top';
                row.innerHTML = `
                    <td class="px-4 py-2">
                        <select name="items[${index}][item_type]" class="item-type w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                            <option value="inventory">Barang</option>
                            <option value="service">Jasa</option>
                        </select>
                    </td>
                    <td class="px-4 py-2">
                        <select name="items[${index}][item_id]" class="item-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"></select>
                    </td>
                    <td class="px-4 py-2">
                        <input name="items[${index}][quantity]" type="number" min="1" value="1" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    </td>
                    <td class="px-4 py-2">
                        <input name="items[${index}][unit_price]" type="number" min="0" step="0.01" value="0" class="unit-price w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-600" readonly>
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" value="Rp 0" class="line-total w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700" readonly>
                    </td>
                    <td class="px-4 py-2">
                        <button type="button" class="remove-row w-full rounded-lg border border-rose-200 px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                    </td>
                `;
                container.appendChild(row);
                hydrateRow(row);
            });

            container.querySelectorAll('.sale-row').forEach((row, index) => {
                const itemSelect = row.querySelector('.item-select');
                const oldValue = @json(old('items.' . $index . '.item_id'));
                if (oldValue !== null && oldValue !== '') {
                    itemSelect.dataset.value = oldValue;
                }
            });
            container.querySelectorAll('.sale-row').forEach(hydrateRow);
            updateGrandTotal();

            const toggleHistory = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            openHistoryButton?.addEventListener('click', () => toggleHistory(true));
            closeHistoryButton?.addEventListener('click', () => toggleHistory(false));
            modal?.querySelectorAll('[data-close-sales-history]').forEach((element) => {
                element.addEventListener('click', () => toggleHistory(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleHistory(false);
            });

            @if (request('show_history'))
                toggleHistory(true);
            @endif
        })();
    </script>
@endpush
