<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Koperasi Digital Mandiri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#0F172A',
                        ocean: '#155DFC',
                        mint: '#10B981',
                        amber: '#F59E0B',
                        paper: '#F8FAFC'
                    },
                    boxShadow: {
                        panel: '0 24px 80px rgba(15, 23, 42, 0.14)'
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-paper text-slate-900">
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(21,93,252,0.18),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.15),_transparent_28%),linear-gradient(135deg,_#eff6ff_0%,_#f8fafc_50%,_#ecfeff_100%)]"></div>

        <div class="relative mx-auto flex min-h-screen max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid w-full items-center gap-8 lg:grid-cols-[1.15fr_0.85fr]">
                <section class="rounded-[2rem] bg-ink p-8 text-white shadow-panel sm:p-10 lg:p-12">
                    <div class="mb-10 flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white/10 ring-1 ring-white/15 backdrop-blur">
                            <svg viewBox="0 0 64 64" class="h-11 w-11" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="32" cy="32" r="26" fill="#F8FAFC"/>
                                <path d="M32 14L46 22V34C46 42.5 40.3 49.8 32 52C23.7 49.8 18 42.5 18 34V22L32 14Z" fill="#155DFC"/>
                                <path d="M32 22C36.4 22 40 25.6 40 30V31H44V34H40.7C39.8 38.7 36.3 42.5 31.7 43.7L30.9 41C34.1 40 36.5 37.4 37.3 34H26.7C27.5 37.4 29.9 40 33.1 41L32.3 43.7C27.7 42.5 24.2 38.7 23.3 34H20V31H24V30C24 25.6 27.6 22 32 22ZM32 25C29.3 25 27 27.3 27 30V31H37V30C37 27.3 34.7 25 32 25Z" fill="#10B981"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-200">Portal Resmi</p>
                            <h1 class="text-2xl font-black sm:text-3xl">Koperasi Digital Mandiri</h1>
                        </div>
                    </div>

                    <div class="max-w-2xl">
                        <p class="mb-4 text-sm font-semibold uppercase tracking-[0.3em] text-cyan-200">Sistem Informasi Koperasi</p>
                        <h2 class="text-4xl font-black leading-tight sm:text-5xl">Layanan digital koperasi yang tertata, aman, dan siap digunakan.</h2>
                        <p class="mt-5 max-w-xl text-base leading-8 text-slate-300 sm:text-lg">
                            Platform ini mendukung pengelolaan operasional koperasi secara profesional untuk kebutuhan administrasi,
                            pelayanan anggota, dan pengawasan aktivitas harian dalam satu lingkungan kerja terpadu.
                        </p>
                    </div>

                    <div class="mt-10 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-200">Komitmen Layanan</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                                <p class="text-sm font-semibold text-white">Tertib Administrasi</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Mendukung proses kerja yang rapi dan konsisten untuk pengurus serta petugas.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                                <p class="text-sm font-semibold text-white">Akses Terarah</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Setiap pengguna diarahkan ke area kerja sesuai peran dan kewenangannya.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                                <p class="text-sm font-semibold text-white">Siap Hosting</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Tampilan lebih formal untuk publikasi sistem saat dipindahkan ke server produksi.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-panel sm:p-8">
                    <div class="mb-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-ocean">Masuk</p>
                        <h3 class="mt-3 text-3xl font-black text-ink">Login ke aplikasi koperasi</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-500">
                            Gunakan akun yang sudah tersedia untuk membuka dashboard operasional koperasi.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Masuk Sebagai</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label data-login-option class="cursor-pointer rounded-2xl border px-4 py-3 transition {{ old('login_context', 'pengurus') === 'anggota' ? 'border-ocean bg-blue-50 text-ocean' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                    <input type="radio" name="login_context" value="anggota" class="sr-only" {{ old('login_context', 'pengurus') === 'anggota' ? 'checked' : '' }}>
                                    <span class="block text-sm font-bold">Anggota</span>
                                    <span class="mt-1 block text-xs">Untuk layanan simpanan, pinjaman, dan portal anggota.</span>
                                </label>
                                <label data-login-option class="cursor-pointer rounded-2xl border px-4 py-3 transition {{ old('login_context', 'pengurus') === 'pengurus' ? 'border-ocean bg-blue-50 text-ocean' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                    <input type="radio" name="login_context" value="pengurus" class="sr-only" {{ old('login_context', 'pengurus') === 'pengurus' ? 'checked' : '' }}>
                                    <span class="block text-sm font-bold">Pengurus</span>
                                    <span class="mt-1 block text-xs">Untuk operasional koperasi, approval, dan pengelolaan sistem.</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="login" class="mb-2 block text-sm font-semibold text-slate-700">Login</label>
                            <input
                                id="login"
                                name="login"
                                type="text"
                                value="{{ old('login') }}"
                                required
                                autofocus
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-ocean focus:bg-white focus:ring-4 focus:ring-blue-100"
                                placeholder="Masukkan login"
                            >
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                            <div class="relative">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-slate-900 outline-none transition focus:border-ocean focus:bg-white focus:ring-4 focus:ring-blue-100"
                                    placeholder="Masukkan password"
                                >
                                <button
                                    type="button"
                                    id="toggle-password"
                                    class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 transition hover:text-slate-600"
                                    aria-label="Tampilkan password"
                                    aria-pressed="false"
                                >
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-ocean focus:ring-ocean">
                            Ingat saya pada perangkat ini
                        </label>

                        <button
                            type="submit"
                            class="w-full rounded-2xl bg-ink px-5 py-3.5 text-sm font-bold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-300"
                        >
                            Lanjut Masuk
                        </button>
                    </form>

                    <div class="mt-6 rounded-2xl bg-amber-50 px-4 py-4 text-sm leading-7 text-amber-900">
                        Jika login berhasil, sistem akan mengarahkan Anda sesuai konteks yang dipilih: portal anggota atau area pengurus.
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const options = document.querySelectorAll('[data-login-option]');
            const passwordInput = document.getElementById('password');
            const togglePasswordButton = document.getElementById('toggle-password');
            const togglePasswordIcon = togglePasswordButton?.querySelector('i');

            const refreshState = () => {
                options.forEach((option) => {
                    const input = option.querySelector('input[type="radio"]');
                    option.classList.remove('border-ocean', 'bg-blue-50', 'text-ocean', 'border-slate-200', 'bg-slate-50', 'text-slate-600');

                    if (input.checked) {
                        option.classList.add('border-ocean', 'bg-blue-50', 'text-ocean');
                    } else {
                        option.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
                    }
                });
            };

            options.forEach((option) => {
                option.addEventListener('click', () => {
                    const input = option.querySelector('input[type="radio"]');
                    input.checked = true;
                    refreshState();
                });
            });

            togglePasswordButton?.addEventListener('click', () => {
                const isPasswordVisible = passwordInput?.type === 'text';

                if (! passwordInput || ! togglePasswordIcon) {
                    return;
                }

                passwordInput.type = isPasswordVisible ? 'password' : 'text';
                togglePasswordButton.setAttribute('aria-pressed', isPasswordVisible ? 'false' : 'true');
                togglePasswordButton.setAttribute('aria-label', isPasswordVisible ? 'Tampilkan password' : 'Sembunyikan password');
                togglePasswordIcon.className = isPasswordVisible ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
            });

            refreshState();
        })();
    </script>
</body>
</html>
