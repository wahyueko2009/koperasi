@extends('layouts.app')

@php
    $routeName = request()->route()?->getName();
    $title = 'Pengaturan User Login - Koperasi Digital Mandiri';
    $sectionLabel = 'Pengaturan Sistem';
    $pageTitle = 'User Login';
    $pageDescription = 'Kelola akun login, mapping jabatan, dan status user yang menjalankan sistem.';
    $activeUsers = $users->where('is_active', true)->count();
    $usersWithPosition = $users->whereNotNull('position_id')->count();
    $usersLinkedToOfficial = $users->whereNotNull('official_id')->count();
@endphp

@section('content')
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total User</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $users->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Akun login yang tercatat di sistem.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">User Aktif</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $activeUsers }}</p>
                <p class="mt-2 text-sm text-slate-500">Bisa masuk dan menjalankan menu sesuai tugasnya.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Mapping Siap Pakai</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $usersWithPosition }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $usersLinkedToOfficial }} user terhubung ke data pengurus.</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form User</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Tambah User Login</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan user login untuk pelaksana sistem. Akses menu utama ditentukan dari role user yang aktif.</p>
                </div>

                <form method="POST" action="{{ route('settings.users.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama</label>
                            <input name="name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Nama user">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Login</label>
                            <input name="login" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="login user">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                            <input name="email" type="email" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="email@koperasi.local">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                        <input name="password" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Minimal 6 karakter">
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Role Sistem</label>
                            <select name="role" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="admin">Admin</option>
                                <option value="finance">Finance</option>
                                <option value="staff">Staff</option>
                                <option value="member">Member</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jabatan</label>
                            <select name="position_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih jabatan</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}">{{ $position->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-slate-500">Jabatan bersifat informasi dan relasi data. Hak akses utama mengikuti role user.</p>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Pengurus Terkait</label>
                        <select name="official_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Tidak terkait pengurus</option>
                            @foreach ($officials as $official)
                                <option value="{{ $official->id }}">{{ $official->name }} - {{ $official->position?->name ?? 'Tanpa jabatan' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                        User aktif dan bisa masuk ke sistem
                    </label>
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Simpan User Login</button>
                </form>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Daftar User</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Akun Login Sistem</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Tampilan ringkas untuk membaca status user dengan cepat. Klik `Edit Detail` bila perlu mengubah data user.</p>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($users as $user)
                        <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                            <div class="grid gap-4 bg-white px-5 py-4 md:grid-cols-[1fr_0.9fr_0.9fr_auto] md:items-center">
                                <div>
                                    <p class="text-lg font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Jabatan</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $user->position?->name ?? 'Tanpa jabatan' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pengurus</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $user->official?->name ?? 'Tidak terkait pengurus' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $user->official?->member?->name ?? 'Tanpa konteks anggota' }}</p>
                                </div>
                                <div class="flex items-center justify-between gap-3 md:justify-end">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <details class="group">
                                        <summary class="cursor-pointer list-none rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                            Edit Detail
                                        </summary>
                                        <div class="mt-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                            <form method="POST" action="{{ route('settings.users.update', $user) }}" class="space-y-4">
                                                @csrf
                                                @method('PUT')
                                                <div class="grid gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Nama</label>
                                                        <input name="name" value="{{ $user->name }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Login</label>
                                                        <input name="login" value="{{ $user->login }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Email</label>
                                                        <input name="email" value="{{ $user->email }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                    </div>
                                                </div>
                                                <div class="grid gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Role</label>
                                                        <select name="role" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                            <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                                            <option value="finance" @selected($user->role === 'finance')>Finance</option>
                                                            <option value="staff" @selected($user->role === 'staff')>Staff</option>
                                                            <option value="member" @selected($user->role === 'member')>Member</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Jabatan</label>
                                                        <select name="position_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                            <option value="">Tanpa jabatan</option>
                                                            @foreach ($positions as $position)
                                                                <option value="{{ $position->id }}" @selected($user->position_id === $position->id)>{{ $position->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="grid gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pengurus Terkait</label>
                                                        <select name="official_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                            <option value="">Tidak terkait pengurus</option>
                                                            @foreach ($officials as $official)
                                                                <option value="{{ $official->id }}" @selected($user->official_id === $official->id)>{{ $official->name }} - {{ $official->position?->name ?? 'Tanpa jabatan' }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</label>
                                                        <select name="is_active" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                                            <option value="1" @selected($user->is_active)>Aktif</option>
                                                            <option value="0" @selected(! $user->is_active)>Nonaktif</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Password Baru</label>
                                                    <input name="password" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Kosongkan jika tidak diubah">
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <p class="text-sm text-slate-500">{{ ucfirst($user->role) }} di sistem</p>
                                                    <button class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Update User</button>
                                                </div>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada user login yang tersimpan.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
