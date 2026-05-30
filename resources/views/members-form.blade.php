@extends('layouts.app')

@php
    $title = $pageTitle . ' - Koperasi Digital Mandiri';
    $sectionLabel = 'Pengaturan Sistem';
    $headerTabs = [
        [
            'label' => 'Manage Anggota',
            'route' => 'members',
            'active' => true,
        ],
        [
            'label' => 'Manage Pengurus',
            'route' => 'settings.officials',
            'active' => false,
        ],
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-4xl">
        <form method="POST" action="{{ $formAction }}" class="space-y-6">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Form Anggota</h2>
                <p class="mt-1 text-sm text-slate-500">Lengkapi data dasar yang akan dipakai oleh modul simpanan, pinjaman, dan portal anggota.</p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="nik" class="mb-2 block text-sm font-medium text-slate-700">NIK / Nomor Identitas</label>
                        <input id="nik" name="nik" type="text" value="{{ old('nik', $member->nik) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" required>
                    </div>
                    <div>
                        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $member->name) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" required>
                    </div>
                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $member->email) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" required>
                    </div>
                    <div>
                        <label for="spouse_name" class="mb-2 block text-sm font-medium text-slate-700">Nama Pasangan</label>
                        <input id="spouse_name" name="spouse_name" type="text" value="{{ old('spouse_name', $member->spouse_name) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    </div>
                    <div>
                        <label for="npwp" class="mb-2 block text-sm font-medium text-slate-700">NPWP</label>
                        <input id="npwp" name="npwp" type="text" value="{{ old('npwp', $member->npwp) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    </div>
                    <div>
                        <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">Nomor Telepon</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $member->phone) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="mb-2 block text-sm font-medium text-slate-700">Alamat</label>
                        <textarea id="address" name="address" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">{{ old('address', $member->address) }}</textarea>
                    </div>
                    <div>
                        <label for="company_unit" class="mb-2 block text-sm font-medium text-slate-700">Company/Unit Kerja</label>
                        <input id="company_unit" name="company_unit" type="text" value="{{ old('company_unit', $member->company_unit) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    </div>
                    <div>
                        <label for="account_number" class="mb-2 block text-sm font-medium text-slate-700">No Rekening</label>
                        <input id="account_number" name="account_number" type="text" value="{{ old('account_number', $member->account_number) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    </div>
                    <div class="md:col-span-2">
                        <label for="status" class="mb-2 block text-sm font-medium text-slate-700">Status Anggota</label>
                        <select id="status" name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" required>
                            <option value="active" @selected(old('status', $member->status ?: 'active') === 'active')>Aktif</option>
                            <option value="inactive" @selected(old('status', $member->status) === 'inactive')>Nonaktif</option>
                            <option value="suspended" @selected(old('status', $member->status) === 'suspended')>Suspended</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Akun Login</h2>
                <p class="mt-1 text-sm text-slate-500">Data ini dipakai anggota untuk masuk ke portal anggota.</p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="login" class="mb-2 block text-sm font-medium text-slate-700">Login</label>
                        <input id="login" name="login" type="text" value="{{ old('login', $member->login ?: $member->user?->login) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" required>
                    </div>
                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-slate-700">{{ $member->exists ? 'Password Baru' : 'Password' }}</label>
                        <input id="password" name="password" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" {{ $member->exists ? '' : 'required' }}>
                    </div>
                    <div class="md:col-span-2">
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" {{ $member->exists ? '' : 'required' }}>
                        @if ($member->exists)
                            <p class="mt-2 text-xs text-slate-500">Kosongkan password jika tidak ingin mengubah akun login anggota.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('members', $member->exists ? ['selected' => $member->id] : []) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Kembali ke daftar
                </a>
                <button class="rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Simpan Data Anggota
                </button>
            </div>
        </form>
    </div>
@endsection
