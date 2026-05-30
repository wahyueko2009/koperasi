@extends('layouts.app')

@php
    $activeContracts = $contracts->where('status', 'active')->count();
    $draftContracts = $contracts->where('status', 'draft')->count();
    $companyCount = $contracts->pluck('company_id')->filter()->unique()->count();
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-6xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <form method="POST" action="{{ route('piutang-usaha.contracts.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor Kontrak</label>
                            <input name="contract_number" value="{{ old('contract_number', $contractNumber) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="CTR-20260528-001">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Perusahaan</label>
                            <select name="company_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih perusahaan</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>
                                        {{ $company->name }} ({{ $company->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Kontrak</label>
                            <input name="contract_date" type="date" value="{{ old('contract_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Mulai Berlaku</label>
                            <input name="start_date" type="date" value="{{ old('start_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Selesai</label>
                            <input name="end_date" type="date" value="{{ old('end_date') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                            <select name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach (['draft' => 'Draft', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-[1fr_0.7fr_0.7fr]">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kategori Layanan</label>
                            <input name="service_category" value="{{ old('service_category') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Sewa mobil dan driver / jasa driver / operasional">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Siklus Tagihan</label>
                            <select name="billing_cycle" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach (['bulanan' => 'Bulanan', 'mingguan' => 'Mingguan', 'harian' => 'Harian'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('billing_cycle', 'bulanan') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Termin Pembayaran (Hari)</label>
                            <input name="payment_term_days" type="number" min="0" value="{{ old('payment_term_days', 30) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Komponen Kontrak</p>
                                <p class="mt-1 text-xs text-slate-500">Masukkan komponen layanan yang akan ditagihkan dalam kontrak ini.</p>
                            </div>
                            <button type="button" id="add-contract-row" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Tambah Baris
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed border-collapse">
                                <thead class="bg-slate-100">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="w-[24%] border-b border-slate-200 px-4 py-3">Item Layanan</th>
                                        <th class="w-[22%] border-b border-slate-200 px-4 py-3">Deskripsi</th>
                                        <th class="w-[10%] border-b border-slate-200 px-4 py-3">Qty</th>
                                        <th class="w-[12%] border-b border-slate-200 px-4 py-3">Satuan</th>
                                        <th class="w-[14%] border-b border-slate-200 px-4 py-3">Harga</th>
                                        <th class="w-[12%] border-b border-slate-200 px-4 py-3">Jumlah</th>
                                        <th class="w-[6%] border-b border-slate-200 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="contract-items" class="bg-white">
                                    @php
                                        $oldItems = old('items', [['item_name' => '', 'description' => '', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => 0]]);
                                    @endphp
                                    @foreach ($oldItems as $index => $item)
                                        <tr class="contract-row border-b border-slate-200 align-top">
                                            <td class="px-4 py-2"><input name="items[{{ $index }}][item_name]" value="{{ $item['item_name'] ?? '' }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Sewa mobil Avanza"></td>
                                            <td class="px-4 py-2"><input name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Driver shift pagi / unit A"></td>
                                            <td class="px-4 py-2"><input name="items[{{ $index }}][quantity]" type="number" min="0.01" step="0.01" value="{{ $item['quantity'] ?? 1 }}" class="quantity-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"></td>
                                            <td class="px-4 py-2"><input name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? 'paket' }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="unit / orang / paket"></td>
                                            <td class="px-4 py-2"><input name="items[{{ $index }}][unit_price]" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? 0 }}" class="unit-price w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"></td>
                                            <td class="px-4 py-2"><input type="text" value="Rp {{ number_format(((float) ($item['quantity'] ?? 1)) * ((float) ($item['unit_price'] ?? 0)), 0, ',', '.') }}" class="line-total w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700" readonly></td>
                                            <td class="px-4 py-2"><button type="button" class="remove-row w-full rounded-lg border border-rose-200 px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Hapus</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-cyan-100 bg-cyan-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Nilai Kontrak</p>
                        <p id="contract-grand-total" class="mt-2 text-2xl font-bold text-cyan-900">Rp 0</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan Kontrak</label>
                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Ruang untuk syarat penagihan, referensi dokumen, atau catatan kontrak lainnya.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="grid gap-3 border-t border-slate-100 pt-2">
                        <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                            Simpan Kontrak Jasa
                        </button>
                        <button
                            type="button"
                            id="open-contract-list"
                            class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500"
                        >
                            View Data
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <div id="contract-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-contract-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Kontrak Jasa</h3>
                    </div>
                    <button type="button" id="close-contract-list" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" aria-label="Tutup popup data kontrak">X</button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p><p class="mt-2 text-xl font-bold text-slate-900">{{ $contracts->count() }}</p></div>
                        <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Aktif</p><p class="mt-2 text-xl font-bold text-emerald-800">{{ $activeContracts }}</p></div>
                        <div class="rounded-[1.25rem] bg-amber-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Draft</p><p class="mt-2 text-xl font-bold text-amber-800">{{ $draftContracts }}</p></div>
                        <div class="rounded-[1.25rem] bg-cyan-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Perusahaan</p><p class="mt-2 text-xl font-bold text-cyan-800">{{ $companyCount }}</p></div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Nomor</th>
                                        <th class="px-5 py-3">Perusahaan</th>
                                        <th class="px-5 py-3">Periode</th>
                                        <th class="px-5 py-3">Tagihan</th>
                                        <th class="px-5 py-3">Nilai</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($contracts as $contract)
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $contract->contract_number }}</td>
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-slate-900">{{ $contract->company?->name ?: '-' }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $contract->service_category }}</p>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-slate-700">
                                                {{ optional($contract->start_date)->format('d M Y') }}
                                                {{ $contract->end_date ? ' - ' . optional($contract->end_date)->format('d M Y') : ' - Berjalan' }}
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="text-sm font-semibold text-slate-900">{{ ucfirst($contract->billing_cycle) }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $contract->items->count() }} komponen · termin {{ $contract->payment_term_days }} hari</p>
                                            </td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $contract->total_amount, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $contract->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($contract->status === 'draft' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ ucfirst($contract->status) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada kontrak jasa yang tersimpan.</td></tr>
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
            const modal = document.getElementById('contract-list-modal');
            const openButton = document.getElementById('open-contract-list');
            const closeButton = document.getElementById('close-contract-list');
            const container = document.getElementById('contract-items');
            const addButton = document.getElementById('add-contract-row');

            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            const formatCurrency = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;

            const updateGrandTotal = () => {
                const totalElement = document.getElementById('contract-grand-total');
                if (!totalElement) return;
                const total = [...container.querySelectorAll('.contract-row')].reduce((sum, row) => {
                    const quantity = Number(row.querySelector('.quantity-input')?.value || 0);
                    const unitPrice = Number(row.querySelector('.unit-price')?.value || 0);
                    return sum + (quantity * unitPrice);
                }, 0);
                totalElement.textContent = formatCurrency(total);
            };

            const reindexRows = () => {
                [...container.querySelectorAll('.contract-row')].forEach((row, index) => {
                    row.querySelector('input[name*="[item_name]"]').name = `items[${index}][item_name]`;
                    row.querySelector('input[name*="[description]"]').name = `items[${index}][description]`;
                    row.querySelector('input[name*="[quantity]"]').name = `items[${index}][quantity]`;
                    row.querySelector('input[name*="[unit]"]').name = `items[${index}][unit]`;
                    row.querySelector('input[name*="[unit_price]"]').name = `items[${index}][unit_price]`;
                });
            };

            const attachRowEvents = (row) => {
                const quantityInput = row.querySelector('.quantity-input');
                const unitPriceInput = row.querySelector('.unit-price');
                const lineTotalInput = row.querySelector('.line-total');

                const updateLineTotal = () => {
                    const quantity = Number(quantityInput?.value || 0);
                    const unitPrice = Number(unitPriceInput?.value || 0);
                    if (lineTotalInput) lineTotalInput.value = formatCurrency(quantity * unitPrice);
                    updateGrandTotal();
                };

                quantityInput?.addEventListener('input', updateLineTotal);
                unitPriceInput?.addEventListener('input', updateLineTotal);
                row.querySelector('.remove-row')?.addEventListener('click', () => {
                    if (container.querySelectorAll('.contract-row').length === 1) {
                        row.querySelectorAll('input').forEach((input) => {
                            if (input.type === 'number') input.value = input.name.includes('[quantity]') ? 1 : 0;
                            if (input.type === 'text') input.value = input.classList.contains('line-total') ? 'Rp 0' : '';
                        });
                        updateLineTotal();
                        return;
                    }
                    row.remove();
                    reindexRows();
                    updateGrandTotal();
                });

                updateLineTotal();
            };

            addButton?.addEventListener('click', () => {
                const index = container.querySelectorAll('.contract-row').length;
                const row = document.createElement('tr');
                row.className = 'contract-row border-b border-slate-200 align-top';
                row.innerHTML = `
                    <td class="px-4 py-2"><input name="items[${index}][item_name]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Sewa mobil Avanza"></td>
                    <td class="px-4 py-2"><input name="items[${index}][description]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Driver shift pagi / unit A"></td>
                    <td class="px-4 py-2"><input name="items[${index}][quantity]" type="number" min="0.01" step="0.01" value="1" class="quantity-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"></td>
                    <td class="px-4 py-2"><input name="items[${index}][unit]" value="paket" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="unit / orang / paket"></td>
                    <td class="px-4 py-2"><input name="items[${index}][unit_price]" type="number" min="0" step="0.01" value="0" class="unit-price w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"></td>
                    <td class="px-4 py-2"><input type="text" value="Rp 0" class="line-total w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700" readonly></td>
                    <td class="px-4 py-2"><button type="button" class="remove-row w-full rounded-lg border border-rose-200 px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Hapus</button></td>
                `;
                container.appendChild(row);
                attachRowEvents(row);
            });

            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-contract-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });

            container?.querySelectorAll('.contract-row').forEach(attachRowEvents);
            updateGrandTotal();
        })();
    </script>
@endpush
