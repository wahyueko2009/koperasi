@extends('layouts.app')

@php
    $postedPurchases = $purchases->where('status', 'posted')->count();
    $supplierCount = $purchases->pluck('supplier_name')->filter()->unique()->count();
    $todayTotal = $purchases->where('purchase_date', now()->toDateString())->sum('total_amount');
    $inventoryOptionsJson = json_encode(
        $inventories->map(fn ($inventory) => [
            'id' => $inventory->id,
            'name' => $inventory->name,
            'code' => $inventory->code,
            'purchase_price' => (float) $inventory->purchase_price,
        ])->values()->all(),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-5xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="mb-5 border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Pembelian</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Input Pembelian Barang</h3>
                        <p class="mt-2 text-sm text-slate-500">Fokus pada input pembelian. Riwayat transaksi bisa dibuka saat diperlukan.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('unit-usaha.purchases.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor Pembelian</label>
                            <input
                                name="purchase_number"
                                value="{{ old('purchase_number', $purchaseNumber) }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="PBM-20260521-001"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Pembelian</label>
                            <input
                                name="purchase_date"
                                type="date"
                                value="{{ old('purchase_date', now()->toDateString()) }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Supplier</label>
                        <input
                            name="supplier_name"
                            value="{{ old('supplier_name') }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            placeholder="Nama supplier atau toko"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Metode Pembayaran</label>
                        <select name="payment_method" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Tunai</option>
                            <option value="credit" @selected(old('payment_method') === 'credit')>Belum Dibayar / Utang</option>
                        </select>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Detail Barang</p>
                                <p class="mt-1 text-xs text-slate-500">Minimal satu barang. Harga beli diisi sesuai transaksi aktual.</p>
                            </div>
                            <button type="button" id="add-purchase-row" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Tambah Baris
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed border-collapse">
                                <thead class="bg-slate-100">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="w-[36%] border-b border-slate-200 px-4 py-3">Barang</th>
                                        <th class="w-[14%] border-b border-slate-200 px-4 py-3">Qty</th>
                                        <th class="w-[18%] border-b border-slate-200 px-4 py-3">Harga Beli</th>
                                        <th class="w-[18%] border-b border-slate-200 px-4 py-3">Jumlah</th>
                                        <th class="w-[14%] border-b border-slate-200 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="purchase-items" class="bg-white">
                                    @php
                                        $oldItems = old('items', [['inventory_id' => '', 'quantity' => 1, 'unit_price' => 0]]);
                                    @endphp
                                    @foreach ($oldItems as $index => $item)
                                        <tr class="purchase-row border-b border-slate-200 align-top">
                                            <td class="px-4 py-2">
                                                <select name="items[{{ $index }}][inventory_id]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                                    <option value="">Pilih barang</option>
                                                    @foreach ($inventories as $inventory)
                                                        <option
                                                            value="{{ $inventory->id }}"
                                                            data-price="{{ (float) $inventory->purchase_price }}"
                                                            @selected((string) ($item['inventory_id'] ?? '') === (string) $inventory->id)
                                                        >
                                                            {{ $inventory->name }} ({{ $inventory->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
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
                                                <button type="button" class="remove-row w-full rounded-lg border border-rose-200 px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-cyan-100 bg-cyan-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Total Pembelian</p>
                        <p id="purchase-grand-total" class="mt-2 text-2xl font-bold text-cyan-900">Rp 0</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Keterangan</label>
                        <textarea
                            name="notes"
                            rows="3"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            placeholder="Catatan pembelian, nomor nota, atau keterangan lain."
                        >{{ old('notes') }}</textarea>
                    </div>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                        Simpan Pembelian / Barang Masuk
                    </button>
                    <button
                        type="button"
                        id="open-purchase-history"
                        class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white transition hover:bg-sky-500"
                    >
                        View Data
                    </button>
                </form>
            </section>
        </div>
    </div>

    <div id="purchase-history-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-purchase-history></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Riwayat Transaksi</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Pembelian</h3>
                    </div>
                    <button
                        type="button"
                        id="close-purchase-history"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup riwayat pembelian"
                    >
                        X
                    </button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ $purchases->count() }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Posted</p>
                            <p class="mt-2 text-xl font-bold text-emerald-800">{{ $postedPurchases }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-amber-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Supplier</p>
                            <p class="mt-2 text-xl font-bold text-amber-800">{{ $supplierCount }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-cyan-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Hari Ini</p>
                            <p class="mt-2 text-lg font-bold text-cyan-800">Rp {{ number_format((float) $todayTotal, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Nomor</th>
                                        <th class="px-5 py-3">Tanggal</th>
                                        <th class="px-5 py-3">Supplier</th>
                                        <th class="px-5 py-3">Pembayaran</th>
                                        <th class="px-5 py-3">Jumlah Item</th>
                                        <th class="px-5 py-3">Ringkasan</th>
                                        <th class="px-5 py-3">Total</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($purchases as $purchase)
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $purchase->purchase_number }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ optional($purchase->purchase_date)->format('d M Y') }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $purchase->supplier_name ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $purchase->payment_method === 'credit' ? 'Utang' : 'Tunai' }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $purchase->items->count() }} item</td>
                                            <td class="px-5 py-4 text-sm text-slate-600">{{ $purchase->items->pluck('inventory.name')->filter()->take(3)->implode(', ') ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $purchase->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ ucfirst($purchase->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada transaksi pembelian / barang masuk.</td>
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
            const container = document.getElementById('purchase-items');
            const addButton = document.getElementById('add-purchase-row');
            const modal = document.getElementById('purchase-history-modal');
            const openHistoryButton = document.getElementById('open-purchase-history');
            const closeHistoryButton = document.getElementById('close-purchase-history');

            if (!container || !addButton) {
                return;
            }

            const inventoryOptions = {!! $inventoryOptionsJson !!};

            const toggleHistory = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            const buildOptions = () => {
                let html = '<option value="">Pilih barang</option>';
                inventoryOptions.forEach((inventory) => {
                    html += `<option value="${inventory.id}" data-price="${inventory.purchase_price}">${inventory.name} (${inventory.code})</option>`;
                });
                return html;
            };

            const formatCurrency = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;

            const updateGrandTotal = () => {
                const totalElement = document.getElementById('purchase-grand-total');
                if (!totalElement) return;
                const total = [...container.querySelectorAll('.purchase-row')].reduce((sum, row) => {
                    const quantity = Number(row.querySelector('input[name*="[quantity]"]')?.value || 0);
                    const unitPrice = Number(row.querySelector('.unit-price')?.value || 0);
                    return sum + (quantity * unitPrice);
                }, 0);
                totalElement.textContent = formatCurrency(total);
            };

            const reindexRows = () => {
                [...container.querySelectorAll('.purchase-row')].forEach((row, index) => {
                    row.querySelectorAll('select, input').forEach((field) => {
                        if (field.name.includes('[inventory_id]')) field.name = `items[${index}][inventory_id]`;
                        if (field.name.includes('[quantity]')) field.name = `items[${index}][quantity]`;
                        if (field.name.includes('[unit_price]')) field.name = `items[${index}][unit_price]`;
                    });
                });
            };

            const attachRowEvents = (row) => {
                const removeButton = row.querySelector('.remove-row');
                const select = row.querySelector('select');
                const quantityInput = row.querySelector('input[name*="[quantity]"]');
                const unitPrice = row.querySelector('.unit-price');
                const lineTotalInput = row.querySelector('.line-total');

                const updateLineTotal = () => {
                    const quantity = Number(quantityInput?.value || 0);
                    const price = Number(unitPrice?.value || 0);
                    if (lineTotalInput) {
                        lineTotalInput.value = formatCurrency(quantity * price);
                    }
                    updateGrandTotal();
                };

                removeButton?.addEventListener('click', () => {
                    if (container.querySelectorAll('.purchase-row').length === 1) {
                        row.querySelectorAll('input').forEach((input) => {
                            if (input.type === 'number') {
                                input.value = input.name.includes('[quantity]') ? 1 : 0;
                            }
                        });
                        if (select) select.value = '';
                        updateLineTotal();
                        return;
                    }

                    row.remove();
                    reindexRows();
                    updateGrandTotal();
                });

                select?.addEventListener('change', () => {
                    const selected = select.options[select.selectedIndex];
                    const defaultPrice = selected?.dataset?.price ?? '';
                    if (unitPrice && (unitPrice.value === '' || Number(unitPrice.value) === 0)) {
                        unitPrice.value = defaultPrice;
                    }
                    updateLineTotal();
                });

                quantityInput?.addEventListener('input', updateLineTotal);
                unitPrice?.addEventListener('input', updateLineTotal);
                updateLineTotal();
            };

            addButton.addEventListener('click', () => {
                const index = container.querySelectorAll('.purchase-row').length;
                const row = document.createElement('tr');
                row.className = 'purchase-row border-b border-slate-200 align-top';
                row.innerHTML = `
                    <td class="px-4 py-2">
                        <select name="items[${index}][inventory_id]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                            ${buildOptions()}
                        </select>
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
                        <button type="button" class="remove-row w-full rounded-lg border border-rose-200 px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">
                            Hapus
                        </button>
                    </td>
                `;
                container.appendChild(row);
                attachRowEvents(row);
            });

            openHistoryButton?.addEventListener('click', () => toggleHistory(true));
            closeHistoryButton?.addEventListener('click', () => toggleHistory(false));
            modal?.querySelectorAll('[data-close-purchase-history]').forEach((element) => {
                element.addEventListener('click', () => toggleHistory(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleHistory(false);
            });

            container.querySelectorAll('.purchase-row').forEach(attachRowEvents);
            updateGrandTotal();
        })();
    </script>
@endpush
