@extends('layouts.app')

@php
    $todaySales = $sales->where('sale_date', now()->toDateString());
    $todayRevenue = $todaySales->sum('total_amount');
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-5xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="mb-5 border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Kasir</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Input Transaksi</h3>
                        <p class="mt-2 text-sm text-slate-500">Fokuskan input penjualan di satu panel. Riwayat transaksi bisa dibuka saat dibutuhkan.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('unit-usaha.pos.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor Transaksi</label>
                            <input name="sale_number" value="{{ old('sale_number', $saleNumber) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal</label>
                            <input name="sale_date" type="date" value="{{ old('sale_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kategori Penjualan</label>
                            <select name="category" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="photocopy" @selected(old('category', 'photocopy') === 'photocopy')>Photocopy</option>
                                <option value="indomaret" @selected(old('category') === 'indomaret')>Indomaret</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Metode Bayar</label>
                            <select name="payment_method" id="payment-method" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Tunai</option>
                                <option value="salary_cut" @selected(old('payment_method') === 'salary_cut')>Potong Gaji</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Anggota</label>
                            <select name="member_id" id="member-id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih anggota jika potong gaji</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}" @selected((string) old('member_id') === (string) $member->id)>
                                        {{ $member->nik }} - {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Item Penjualan</p>
                                <p class="mt-1 text-xs text-slate-500">Bisa campur jasa dan barang. Barang otomatis mengurangi stok.</p>
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
                                    @php
                                        $oldItems = old('items', [['item_type' => 'service', 'item_id' => '', 'quantity' => 1, 'unit_price' => 0]]);
                                    @endphp
                                    @foreach ($oldItems as $index => $item)
                                        <tr class="sale-row border-b border-slate-200 align-top">
                                            <td class="px-4 py-2">
                                                <select name="items[{{ $index }}][item_type]" class="item-type w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                                    <option value="service" @selected(($item['item_type'] ?? 'service') === 'service')>Jasa</option>
                                                    <option value="inventory" @selected(($item['item_type'] ?? '') === 'inventory')>Barang</option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2">
                                                <select name="items[{{ $index }}][item_id]" class="item-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"></select>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input name="items[{{ $index }}][quantity]" type="number" min="1" value="{{ $item['quantity'] ?? 1 }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input name="items[{{ $index }}][unit_price]" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? 0 }}" class="unit-price w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
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
                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan transaksi kasir.">{{ old('notes') }}</textarea>
                    </div>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                        Simpan Transaksi POS
                    </button>
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
            <section class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
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

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ $sales->count() }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Hari Ini</p>
                            <p class="mt-2 text-xl font-bold text-emerald-800">{{ $todaySales->count() }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-cyan-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Omzet Hari Ini</p>
                            <p class="mt-2 text-lg font-bold text-cyan-800">Rp {{ number_format((float) $todayRevenue, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Nomor</th>
                                        <th class="px-5 py-3">Tanggal</th>
                                        <th class="px-5 py-3">Metode</th>
                                        <th class="px-5 py-3">Anggota</th>
                                        <th class="px-5 py-3">Jumlah Item</th>
                                        <th class="px-5 py-3">Ringkasan</th>
                                        <th class="px-5 py-3">Total</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($sales as $sale)
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $sale->sale_number }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ optional($sale->sale_date)->format('d M Y') }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ ucfirst(str_replace('_', ' ', $sale->payment_method ?? 'cash')) }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $sale->member?->name ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $sale->items->count() }} item</td>
                                            <td class="px-5 py-4 text-sm text-slate-600">{{ $sale->items->pluck('item_name')->take(3)->implode(', ') ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $sale->total_amount, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $sale->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($sale->status) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada transaksi kasir.</td>
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
            const paymentMethodSelect = document.getElementById('payment-method');
            const memberSelect = document.getElementById('member-id');
            const modal = document.getElementById('sales-history-modal');
            const openHistoryButton = document.getElementById('open-sales-history');
            const closeHistoryButton = document.getElementById('close-sales-history');
            if (!container || !addButton) return;

            const optionHtml = (type) => {
                const items = type === 'inventory' ? inventoryOptions : serviceOptions;
                let html = '<option value="">Pilih item</option>';
                items.forEach((item) => {
                    html += `<option value="${item.id}" data-price="${item.price}">${item.name} (${item.code})</option>`;
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
                    if (selected?.dataset?.price && (!priceInput.value || Number(priceInput.value) === 0)) {
                        priceInput.value = selected.dataset.price;
                    }
                    updateLineTotal();
                });

                quantityInput?.addEventListener('input', updateLineTotal);
                priceInput?.addEventListener('input', updateLineTotal);

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
                            <option value="service">Jasa</option>
                            <option value="inventory">Barang</option>
                        </select>
                    </td>
                    <td class="px-4 py-2">
                        <select name="items[${index}][item_id]" class="item-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"></select>
                    </td>
                    <td class="px-4 py-2">
                        <input name="items[${index}][quantity]" type="number" min="1" value="1" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    </td>
                    <td class="px-4 py-2">
                        <input name="items[${index}][unit_price]" type="number" min="0" step="0.01" value="0" class="unit-price w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
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
                itemSelect.dataset.value = @json(old('items.' . $index . '.item_id'));
            });
            container.querySelectorAll('.sale-row').forEach(hydrateRow);
            updateGrandTotal();

            const syncMemberState = () => {
                if (!paymentMethodSelect || !memberSelect) return;
                const requiresMember = paymentMethodSelect.value === 'salary_cut';
                memberSelect.required = requiresMember;
                memberSelect.closest('div')?.classList.toggle('opacity-70', !requiresMember);
            };

            paymentMethodSelect?.addEventListener('change', syncMemberState);
            syncMemberState();

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
        })();
    </script>
@endpush
