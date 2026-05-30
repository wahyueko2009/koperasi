@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('gl.cost-centers.store') }}" class="space-y-5">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Pusat Biaya</label>
                        <input name="code" value="{{ old('code', $costCenterCode) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="CC-001">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Pusat Biaya</label>
                        <input name="name" value="{{ old('name') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Unit Usaha Retail">
                    </div>
                </div>
                <div class="grid gap-4 md:grid-cols-[1fr_0.8fr]">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">PIC</label>
                        <input name="pic_name" value="{{ old('pic_name') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Nama penanggung jawab">
                    </div>
                    <div>
                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', '1') ? 'checked' : '' }}>
                            Pusat biaya aktif
                        </label>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
                    <textarea name="description" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Digunakan untuk biaya retail, gudang, atau proyek tertentu.">{{ old('description') }}</textarea>
                </div>
                <div class="grid gap-3 border-t border-slate-100 pt-2">
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Simpan Pusat Biaya</button>
                    <button type="button" id="open-cost-center-list" class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500">Lihat Data</button>
                </div>
            </form>
        </section>
    </div>

    <div id="cost-center-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-cost-center-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Pusat Biaya</h3>
                    </div>
                    <button type="button" id="close-cost-center-list" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">X</button>
                </div>
                <div class="max-h-[72vh] overflow-y-scroll overflow-x-hidden px-6 py-6">
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-4 py-3">Kode</th>
                                        <th class="px-4 py-3">Pusat Biaya</th>
                                        <th class="px-4 py-3">PIC</th>
                                        <th class="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($costCenters as $costCenter)
                                        <tr class="text-sm">
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $costCenter->code }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $costCenter->name }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $costCenter->pic_name ?: '-' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $costCenter->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $costCenter->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada pusat biaya.</td></tr>
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
            const modal = document.getElementById('cost-center-list-modal');
            const openButton = document.getElementById('open-cost-center-list');
            const closeButton = document.getElementById('close-cost-center-list');
            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };
            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-cost-center-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });
        })();
    </script>
@endpush
