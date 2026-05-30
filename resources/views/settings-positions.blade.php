@extends('layouts.app')

@php
    $routeName = request()->route()?->getName();
    $title = 'Pengaturan Jabatan - Koperasi Digital Mandiri';
    $sectionLabel = 'Pengaturan Sistem';
    $pageTitle = 'Jabatan';
    $pageDescription = 'Kelola jabatan yang dipakai sebagai dasar otoritas approval dan struktur kerja operasional.';
    $activePositions = $positions->where('is_active', true)->count();
    $approvalPositions = $positions->filter(fn ($position) => filled($position->approval_scope))->count();
    $linkedUsers = $positions->sum('users_count');
    $linkedOfficials = $positions->sum('officials_count');
@endphp

@section('content')
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Jabatan</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $positions->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Struktur peran yang tersimpan di sistem.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Jabatan Aktif</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $activePositions }}</p>
                <p class="mt-2 text-sm text-slate-500">Siap dipakai untuk operasional dan approval.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Terkait Pengurus</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $linkedOfficials }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $linkedUsers }} user login memakai jabatan ini.</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Master</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Tambah Jabatan Baru</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan jabatan untuk mewakili otoritas bisnis seperti Finance, Ketua Koperasi, atau Admin Koperasi.</p>
                </div>

                <form method="POST" action="{{ route('settings.positions.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Jabatan</label>
                            <input name="code" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="FINANCE">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Jabatan</label>
                            <input name="name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Finance">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Scope Approval</label>
                        <input name="approval_scope" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="approval_finance">
                        <p class="mt-2 text-xs text-slate-500">Kosongkan jika jabatan ini tidak dipakai untuk approval khusus.</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea name="description" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Penjelasan singkat tentang fungsi jabatan"></textarea>
                    </div>
                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                        Jabatan aktif dan bisa dipakai di sistem
                    </label>
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Simpan Jabatan</button>
                </form>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Daftar Jabatan</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Struktur Jabatan Sistem</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Setiap baris menampilkan ringkasan. Klik `Edit Detail` bila kamu ingin mengubah data jabatan tersebut.</p>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($positions as $position)
                        <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                            <div class="grid gap-4 bg-white px-5 py-4 md:grid-cols-[1.1fr_0.9fr_0.75fr_auto] md:items-center">
                                <div>
                                    <p class="text-lg font-semibold text-slate-900">{{ $position->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $position->code }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Scope</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $position->approval_scope ?: 'Tidak ada scope khusus' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Terhubung</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $position->users_count }} user</p>
                                </div>
                                <div class="flex items-center justify-between gap-3 md:justify-end">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $position->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $position->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <details class="group">
                                        <summary class="cursor-pointer list-none rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                            Edit Detail
                                        </summary>
                                        <div class="mt-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                            <form method="POST" action="{{ route('settings.positions.update', $position) }}" class="space-y-4">
                                                @csrf
                                                @method('PUT')
                                                <div class="grid gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kode</label>
                                                        <input name="code" value="{{ $position->code }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Nama</label>
                                                        <input name="name" value="{{ $position->name }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                    </div>
                                                </div>
                                                <div class="grid gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Approval Scope</label>
                                                        <input name="approval_scope" value="{{ $position->approval_scope }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</label>
                                                        <select name="is_active" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                            <option value="1" @selected($position->is_active)>Aktif</option>
                                                            <option value="0" @selected(! $position->is_active)>Nonaktif</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Deskripsi</label>
                                                    <textarea name="description" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3">{{ $position->description }}</textarea>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <p class="text-sm text-slate-500">{{ $position->users_count }} user terhubung</p>
                                                    <button class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Update Jabatan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            </div>
                            @if ($position->description)
                                <div class="border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600">
                                    {{ $position->description }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada jabatan yang tersimpan.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
