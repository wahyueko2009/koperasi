<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Koperasi Digital Mandiri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0F172A',
                        success: '#10B981',
                        danger: '#EF4444',
                        warning: '#F59E0B'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    @php
        $user = auth()->user();
        $role = ucwords(str_replace('_', ' ', $user?->role ?? 'user'));
        $initial = strtoupper(substr($user?->name ?? 'U', 0, 1));
    @endphp

    <div class="mx-auto max-w-5xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-primary">Profil Pengguna</h1>
                <p class="mt-2 text-gray-600">Informasi akun yang sedang login di aplikasi koperasi.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm border border-gray-200 hover:bg-gray-50">
                Kembali ke Dashboard
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <section class="rounded-2xl bg-primary p-8 text-white shadow-sm">
                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-white text-3xl font-black text-primary">
                    {{ $initial }}
                </div>
                <h2 class="mt-6 text-2xl font-bold">{{ $user?->name }}</h2>
                <p class="mt-1 text-blue-200">{{ $user?->email }}</p>
                <div class="mt-6 inline-flex items-center rounded-full bg-white/10 px-4 py-2 text-sm font-semibold">
                    Role: {{ $role }}
                </div>
            </section>

            <section class="rounded-2xl bg-white p-8 shadow-sm border border-gray-100">
                <h3 class="text-xl font-bold text-primary">Detail Akun</h3>
                <div class="mt-6 space-y-4">
                    <div class="rounded-xl bg-gray-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nama Lengkap</p>
                        <p class="mt-2 text-base font-semibold text-gray-900">{{ $user?->name }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email Login</p>
                        <p class="mt-2 text-base font-semibold text-gray-900">{{ $user?->email }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Role Sistem</p>
                        <p class="mt-2 text-base font-semibold text-gray-900">{{ $role }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">ID User</p>
                        <p class="mt-2 text-base font-semibold text-gray-900">#{{ $user?->id }}</p>
                    </div>
                    @if ($user?->member)
                        <div class="rounded-xl bg-gray-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Terkait Anggota</p>
                            <p class="mt-2 text-base font-semibold text-gray-900">{{ $user->member->name }} - {{ $user->member->nik }}</p>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-8">
                    @csrf
                    <button class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700">
                        <i class="fas fa-right-from-bracket"></i>
                        Logout dari aplikasi
                    </button>
                </form>
            </section>
        </div>
    </div>
</body>
</html>
