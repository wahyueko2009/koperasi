<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Pinjaman - Portal Anggota</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0F172A',
                        accent: '#0F766E',
                        paper: '#F8FAFC',
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-paper text-slate-900">
    <div class="mx-auto min-h-screen max-w-3xl bg-white shadow-xl">
        <header class="bg-primary px-6 py-5 text-white">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-200">Portal Anggota</p>
                    <h1 class="mt-2 text-2xl font-bold">Pengajuan Pinjaman</h1>
                    <p class="mt-1 text-sm text-slate-300">{{ $member->name }} | {{ $member->nik }}</p>
                </div>
                <a href="{{ route('member-portal') }}" class="rounded-2xl border border-white/15 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
                    Kembali
                </a>
            </div>
        </header>

        <main class="space-y-6 px-6 py-6">
            @if ($errors->any())
                <section class="rounded-3xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                    <p class="font-semibold">Pengajuan belum bisa dikirim.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="rounded-3xl bg-slate-50 p-5 ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Data Anggota</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nama Lengkap</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $member->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">NIK</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $member->nik }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Unit Kerja</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $member->company_unit ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">No Rekening</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $member->account_number ?: '-' }}</p>
                    </div>
                </div>
            </section>

            <form method="POST" action="{{ route('member-portal.loans.store') }}" class="space-y-6" id="loan-application-form" enctype="multipart/form-data">
                @csrf

                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Detail Pengajuan</h2>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Jenis Pinjaman</span>
                            <select name="loan_type" id="loan_type" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" required>
                                <option value="">Pilih jenis pinjaman</option>
                                <option value="emergency" @selected(old('loan_type') === 'emergency')>Emergency</option>
                                <option value="pendidikan" @selected(old('loan_type') === 'pendidikan')>Pendidikan</option>
                                <option value="serbaguna" @selected(old('loan_type') === 'serbaguna')>Serbaguna</option>
                                <option value="multiguna_plus" @selected(old('loan_type') === 'multiguna_plus')>Multiguna Plus</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Metode Pembayaran</span>
                            <select name="repayment_method" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" required>
                                <option value="">Pilih metode pembayaran</option>
                                <option value="salary_cut" @selected(old('repayment_method') === 'salary_cut')>Potong Gaji</option>
                                <option value="payroll_debit" @selected(old('repayment_method') === 'payroll_debit')>Debet Payroll</option>
                                <option value="transfer" @selected(old('repayment_method') === 'transfer')>Transfer</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Nominal Pinjaman</span>
                            <input type="number" id="principal_amount" name="principal_amount" min="100000" step="1000" value="{{ old('principal_amount') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="Contoh: 5000000" required>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Suku Bunga (%)</span>
                            <input type="number" id="interest_rate" name="interest_rate" min="0.5" max="20" step="0.1" value="{{ old('interest_rate', '1.5') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" required>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Tenor (bulan)</span>
                            <input type="number" id="tenor_months" name="tenor_months" min="1" max="60" value="{{ old('tenor_months', '12') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" required>
                        </label>
                        <div class="md:col-span-2 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Simulasi Angsuran</h3>
                                    <p class="text-sm text-slate-500">Perkiraan cicilan bulanan berdasarkan nominal, bunga, dan tenor yang Anda isi.</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Angsuran / Bulan</p>
                                        <p id="installment-monthly-payment" class="mt-2 text-lg font-bold text-slate-900">Rp 0</p>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total Bayar</p>
                                        <p id="installment-total-payment" class="mt-2 text-lg font-bold text-slate-900">Rp 0</p>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total Bunga</p>
                                        <p id="installment-total-interest" class="mt-2 text-lg font-bold text-slate-900">Rp 0</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-100">
                                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                <th class="px-4 py-3">Bulan</th>
                                                <th class="px-4 py-3 text-right">Angsuran</th>
                                                <th class="px-4 py-3 text-right">Pokok</th>
                                                <th class="px-4 py-3 text-right">Bunga</th>
                                                <th class="px-4 py-3 text-right">Sisa Pokok</th>
                                            </tr>
                                        </thead>
                                        <tbody id="installment-schedule-body" class="divide-y divide-slate-100">
                                            <tr>
                                                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Isi nominal, bunga, dan tenor untuk melihat tabel angsuran.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <label class="block md:col-span-2">
                            <span class="text-sm font-semibold text-slate-700">Tujuan Pinjaman</span>
                            <textarea name="purpose" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="Jelaskan kebutuhan pinjaman Anda" required>{{ old('purpose') }}</textarea>
                        </label>
                    </div>
                </section>

                <section id="loan-type-guide" class="hidden rounded-3xl border border-sky-200 bg-sky-50 p-5 text-sm text-sky-900">
                    <p class="font-semibold">Data tambahan akan muncul sesuai jenis pinjaman yang dipilih.</p>
                    <p class="mt-1 text-sky-800" id="loan-type-guide-text"></p>
                </section>

                <section id="section-emergency" data-loan-section="emergency" class="hidden rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Data Emergency</h2>
                    <p class="mt-1 text-sm text-slate-500">Isi data keluarga dan rumah sakit untuk kebutuhan darurat.</p>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Nama Anggota Keluarga</span>
                            <input type="text" name="family_member_name" data-dynamic-field="emergency pendidikan" value="{{ old('family_member_name') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Hubungan Keluarga</span>
                            <input type="text" name="family_relationship" data-dynamic-field="emergency pendidikan" value="{{ old('family_relationship') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="Suami, istri, anak">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Nama Rumah Sakit</span>
                            <input type="text" name="hospital_name" data-dynamic-field="emergency" value="{{ old('hospital_name') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Telepon Rumah Sakit</span>
                            <input type="text" name="hospital_phone" data-dynamic-field="emergency" value="{{ old('hospital_phone') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm font-semibold text-slate-700">Alamat Rumah Sakit</span>
                            <textarea name="hospital_address" data-dynamic-field="emergency" rows="3" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ old('hospital_address') }}</textarea>
                        </label>
                    </div>
                </section>

                <section id="section-pendidikan" data-loan-section="pendidikan" class="hidden rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Data Pendidikan</h2>
                    <p class="mt-1 text-sm text-slate-500">Isi data anak atau keluarga dan informasi sekolah yang membutuhkan pembiayaan.</p>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Nama Anggota Keluarga</span>
                            <input type="text" name="family_member_name" data-dynamic-field="emergency pendidikan" value="{{ old('family_member_name') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Hubungan Keluarga</span>
                            <input type="text" name="family_relationship" data-dynamic-field="emergency pendidikan" value="{{ old('family_relationship') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="Suami, istri, anak">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Anak Ke</span>
                            <input type="number" name="child_number" data-dynamic-field="pendidikan" min="1" max="20" value="{{ old('child_number') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Jenjang Pendidikan</span>
                            <input type="text" name="education_level" data-dynamic-field="pendidikan" value="{{ old('education_level') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="SD, SMP, SMA, Kuliah">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Nama Sekolah</span>
                            <input type="text" name="school_name" data-dynamic-field="pendidikan" value="{{ old('school_name') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Telepon Sekolah</span>
                            <input type="text" name="school_phone" data-dynamic-field="pendidikan" value="{{ old('school_phone') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm font-semibold text-slate-700">Alamat Sekolah</span>
                            <textarea name="school_address" data-dynamic-field="pendidikan" rows="3" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ old('school_address') }}</textarea>
                        </label>
                    </div>
                </section>

                <section id="section-serbaguna" data-loan-section="serbaguna" class="hidden rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Data Serbaguna</h2>
                    <p class="mt-1 text-sm text-slate-500">Pinjaman serbaguna tidak membutuhkan data tambahan khusus. Silakan lengkapi tujuan pinjaman dan catatan bila perlu.</p>
                </section>

                <section id="section-multiguna_plus" data-loan-section="multiguna_plus" class="hidden rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Data Jaminan Multiguna Plus</h2>
                    <p class="mt-1 text-sm text-slate-500">Isi informasi jaminan yang akan diajukan untuk pinjaman multiguna plus.</p>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Jenis Jaminan</span>
                            <input type="text" name="collateral_type" data-dynamic-field="multiguna_plus" value="{{ old('collateral_type') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="BPKB, sertifikat, lainnya">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Nilai Jaminan</span>
                            <input type="number" name="collateral_value" data-dynamic-field="multiguna_plus" min="0" step="1000" value="{{ old('collateral_value') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm font-semibold text-slate-700">Keterangan Jaminan</span>
                            <textarea name="collateral_description" data-dynamic-field="multiguna_plus" rows="3" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ old('collateral_description') }}</textarea>
                        </label>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Catatan Tambahan</h2>
                    <label class="mt-4 block">
                        <span class="text-sm font-semibold text-slate-700">Catatan</span>
                        <textarea name="notes" rows="3" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ old('notes') }}</textarea>
                    </label>
                </section>

                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Upload Dokumen</h2>
                    <p class="mt-1 text-sm text-slate-500">Upload lampiran wajib sesuai jenis pinjaman. Format file hanya `PDF` dengan ukuran maksimal `5 MB` per dokumen.</p>

                    <div class="mt-4 space-y-4">
                        @foreach ($documentRequirements as $loanTypeKey => $documents)
                            <div data-document-group="{{ $loanTypeKey }}" class="hidden rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-900">{{ str($loanTypeKey)->replace('_', ' ')->title() }}</p>
                                <div class="mt-4 grid gap-4">
                                    @foreach ($documents as $document)
                                        <label class="block">
                                            <span class="text-sm font-semibold text-slate-700">{{ $document['label'] }}</span>
                                            <input
                                                type="file"
                                                name="documents[{{ $document['type'] }}]"
                                                data-document-input="{{ $loanTypeKey }}"
                                                accept="application/pdf,.pdf"
                                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-700"
                                            >
                                            @error('documents.' . $document['type'])
                                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="flex items-center justify-end gap-3 pb-6">
                    <a href="{{ route('member-portal') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                        Batal
                    </a>
                    <button type="submit" class="rounded-2xl bg-accent px-5 py-3 text-sm font-semibold text-white hover:bg-teal-700">
                        Kirim Pengajuan
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loanTypeSelect = document.getElementById('loan_type');
            const principalInput = document.getElementById('principal_amount');
            const interestInput = document.getElementById('interest_rate');
            const tenorInput = document.getElementById('tenor_months');
            const sections = document.querySelectorAll('[data-loan-section]');
            const guide = document.getElementById('loan-type-guide');
            const guideText = document.getElementById('loan-type-guide-text');
            const documentGroups = document.querySelectorAll('[data-document-group]');
            const scheduleBody = document.getElementById('installment-schedule-body');
            const monthlyPaymentOutput = document.getElementById('installment-monthly-payment');
            const totalPaymentOutput = document.getElementById('installment-total-payment');
            const totalInterestOutput = document.getElementById('installment-total-interest');

            const guideCopy = {
                emergency: 'Lengkapi data keluarga dan rumah sakit untuk proses verifikasi kebutuhan darurat.',
                pendidikan: 'Lengkapi data keluarga dan sekolah agar pengajuan pendidikan bisa diverifikasi dengan cepat.',
                serbaguna: 'Pinjaman serbaguna cukup memakai detail pengajuan utama. Data tambahan tidak wajib.',
                multiguna_plus: 'Lengkapi informasi jaminan yang akan digunakan untuk pengajuan multiguna plus.',
            };

            const formatCurrency = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value || 0)))}`;

            const syncLoanTypeSections = () => {
                const activeType = loanTypeSelect.value;

                sections.forEach((section) => {
                    const isActive = section.dataset.loanSection === activeType;
                    section.classList.toggle('hidden', !isActive);

                    section.querySelectorAll('input, textarea, select').forEach((field) => {
                        field.disabled = !isActive;
                    });
                });

                documentGroups.forEach((group) => {
                    const isActive = group.dataset.documentGroup === activeType;
                    group.classList.toggle('hidden', !isActive);

                    group.querySelectorAll('input[type="file"]').forEach((field) => {
                        field.disabled = !isActive;
                    });
                });

                if (!activeType) {
                    guide.classList.add('hidden');
                    guideText.textContent = '';
                    return;
                }

                guide.classList.remove('hidden');
                guideText.textContent = guideCopy[activeType] ?? '';
            };

            const renderInstallmentSchedule = () => {
                const principal = Number(principalInput?.value || 0);
                const annualInterestRate = Number(interestInput?.value || 0);
                const months = Number(tenorInput?.value || 0);

                if (principal <= 0 || annualInterestRate < 0 || months <= 0) {
                    monthlyPaymentOutput.textContent = 'Rp 0';
                    totalPaymentOutput.textContent = 'Rp 0';
                    totalInterestOutput.textContent = 'Rp 0';
                    scheduleBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Isi nominal, bunga, dan tenor untuk melihat tabel angsuran.</td>
                        </tr>
                    `;
                    return;
                }

                const monthlyRate = annualInterestRate / 100 / 12;
                const monthlyPayment = monthlyRate === 0
                    ? principal / months
                    : principal * (monthlyRate * Math.pow(1 + monthlyRate, months)) / (Math.pow(1 + monthlyRate, months) - 1);

                let remainingBalance = principal;
                let totalInterest = 0;
                const rows = [];

                for (let month = 1; month <= months; month += 1) {
                    const interestPortion = monthlyRate === 0 ? 0 : remainingBalance * monthlyRate;
                    let principalPortion = monthlyPayment - interestPortion;

                    if (month === months) {
                        principalPortion = remainingBalance;
                    }

                    remainingBalance = Math.max(0, remainingBalance - principalPortion);
                    totalInterest += interestPortion;

                    rows.push(`
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-700">${month}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">${formatCurrency(monthlyPayment)}</td>
                            <td class="px-4 py-3 text-right text-slate-700">${formatCurrency(principalPortion)}</td>
                            <td class="px-4 py-3 text-right text-slate-700">${formatCurrency(interestPortion)}</td>
                            <td class="px-4 py-3 text-right text-slate-700">${formatCurrency(remainingBalance)}</td>
                        </tr>
                    `);
                }

                monthlyPaymentOutput.textContent = formatCurrency(monthlyPayment);
                totalPaymentOutput.textContent = formatCurrency(monthlyPayment * months);
                totalInterestOutput.textContent = formatCurrency(totalInterest);
                scheduleBody.innerHTML = rows.join('');
            };

            loanTypeSelect.addEventListener('change', syncLoanTypeSections);
            [principalInput, interestInput, tenorInput].forEach((field) => {
                field?.addEventListener('input', renderInstallmentSchedule);
            });
            syncLoanTypeSections();
            renderInstallmentSchedule();
        });
    </script>
</body>
</html>
