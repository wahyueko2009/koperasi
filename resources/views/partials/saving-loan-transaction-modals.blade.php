<div id="transaction-modal-root" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/55 px-4 py-8">
    <div class="w-full max-w-2xl rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Transaksi Simpan Pinjam</p>
                <h2 class="text-2xl font-bold text-slate-900" id="transaction-modal-title">Form Transaksi</h2>
            </div>
            <button type="button" id="transaction-modal-close" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-slate-500 hover:bg-slate-50">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="transaction-modal-content" class="px-6 py-6"></div>
    </div>
</div>

<div id="transaction-modal-templates" class="hidden">
    <template data-transaction-template="saving">
        <div data-title="Setoran Simpanan">
            <form method="POST" action="{{ route('simpan-pinjam.saving.store') }}" class="space-y-4">
                @csrf
                <div class="rounded-[1.5rem] bg-teal-50 px-5 py-4 text-sm text-teal-700">
                    Gunakan form ini untuk mencatat setoran simpanan anggota aktif sesuai jenis simpanannya.
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Anggota <span class="text-red-500">*</span></label>
                    <select name="member_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
                        <option value="">Pilih anggota</option>
                        @foreach (($eligibleMembers ?? []) as $member)
                            <option value="{{ $member->id }}">{{ $member->name }} - {{ $member->nik }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Simpanan <span class="text-red-500">*</span></label>
                    <select name="saving_type_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
                        <option value="">Pilih jenis simpanan</option>
                        @foreach (($savingTypes ?? []) as $savingType)
                            <option value="{{ $savingType->id }}">{{ $savingType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-[1fr_auto]">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nominal <span class="text-red-500">*</span></label>
                        <input type="number" min="1000" step="0.01" name="amount" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Masukkan nominal setoran" required>
                    </div>
                    <div class="sm:pt-8">
                        <div class="rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600">Rp</div>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Metode Pembayaran <span class="text-red-500">*</span></label>
                    <select name="payment_method" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
                        <option value="">Pilih metode pembayaran</option>
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer</option>
                        <option value="salary_cut">Potong Gaji</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                    <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan (opsional)"></textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <button type="button" data-close-transaction-modal class="rounded-2xl border border-slate-300 px-4 py-3 font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button class="rounded-2xl bg-teal-600 px-4 py-3 font-semibold text-white hover:bg-teal-700">Simpan Setoran</button>
                </div>
            </form>
        </div>
    </template>

    <template data-transaction-template="loan">
        <div data-title="Pengajuan Pinjaman">
            <form method="POST" action="{{ route('simpan-pinjam.loan.store') }}" class="space-y-4">
                @csrf
                <div class="rounded-[1.5rem] bg-sky-50 px-5 py-4 text-sm text-sky-700">
                    Pengajuan baru akan mengikuti alur pinjaman koperasi dan menyesuaikan data wajib berdasarkan jenis pinjaman.
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Anggota <span class="text-red-500">*</span></label>
                    <select name="member_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
                        <option value="">Pilih anggota</option>
                        @foreach (($eligibleMembers ?? []) as $member)
                            <option value="{{ $member->id }}">{{ $member->name }} - {{ $member->nik }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Pinjaman <span class="text-red-500">*</span></label>
                        <select name="loan_type" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
                            <option value="">Pilih jenis pinjaman</option>
                            <option value="emergency">Emergency</option>
                            <option value="pendidikan">Pendidikan</option>
                            <option value="serbaguna">Serbaguna</option>
                            <option value="multiguna_plus">Multiguna Plus</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Metode Angsuran <span class="text-red-500">*</span></label>
                        <select name="repayment_method" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
                            <option value="">Pilih metode angsuran</option>
                            <option value="salary_cut">Potong Gaji</option>
                            <option value="payroll_debit">Debet Rekening Payroll</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Pokok Pinjaman <span class="text-red-500">*</span></label>
                    <input type="number" min="100000" step="0.01" name="principal_amount" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Masukkan nominal pinjaman" required>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Bunga (%) <span class="text-red-500">*</span></label>
                        <input type="number" min="0.5" max="20" step="0.01" name="interest_rate" class="w-full rounded-2xl border border-slate-300 px-4 py-3" value="12" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tenor (bulan) <span class="text-red-500">*</span></label>
                        <input type="number" min="1" max="60" name="tenor_months" class="w-full rounded-2xl border border-slate-300 px-4 py-3" value="12" required>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Tujuan Penggunaan Dana <span class="text-red-500">*</span></label>
                    <textarea name="purpose" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Jelaskan tujuan pinjaman" required></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Keluarga / Tertanggung</label>
                        <input type="text" name="family_member_name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Untuk emergency / pendidikan">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Hubungan Keluarga</label>
                        <input type="text" name="family_relationship" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Anak / Istri / Suami">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Jenjang Pendidikan</label>
                        <input type="text" name="education_level" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Khusus pinjaman pendidikan">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Sekolah</label>
                        <input type="text" name="school_name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Khusus pinjaman pendidikan">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Rumah Sakit</label>
                        <input type="text" name="hospital_name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Khusus pinjaman emergency">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Jaminan</label>
                        <input type="text" name="collateral_type" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Khusus multiguna plus">
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi Jaminan / Keterangan Tambahan</label>
                    <textarea name="collateral_description" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Isi jika diperlukan untuk jaminan atau keterangan pengajuan"></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                    <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan (opsional)"></textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <button type="button" data-close-transaction-modal class="rounded-2xl border border-slate-300 px-4 py-3 font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button class="rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-700">Buat Pengajuan</button>
                </div>
            </form>
        </div>
    </template>

    <template data-transaction-template="payment">
        <div data-title="Pembayaran Pinjaman">
            <form method="POST" action="{{ route('simpan-pinjam.payment.store') }}" class="space-y-4">
                @csrf
                <div class="rounded-[1.5rem] bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                    Pilih pinjaman aktif lalu catat pokok dan bunga yang dibayarkan pada transaksi ini.
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Pinjaman Aktif <span class="text-red-500">*</span></label>
                    <select name="loan_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
                        <option value="">Pilih pinjaman</option>
                        @foreach (($activeLoans ?? $activeLoansForPayment ?? []) as $loan)
                            <option value="{{ $loan->id }}">
                                #{{ $loan->id }} - {{ $loan->member->name }} - Sisa Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Pokok Dibayar <span class="text-red-500">*</span></label>
                        <input type="number" min="0" step="0.01" name="principal_paid" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="250000" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Bunga Dibayar <span class="text-red-500">*</span></label>
                        <input type="number" min="0" step="0.01" name="interest_paid" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="30000" required>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Metode Pembayaran <span class="text-red-500">*</span></label>
                    <select name="payment_method" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
                        <option value="">Pilih metode pembayaran</option>
                        <option value="salary_cut">Potong Gaji</option>
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                    <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan (opsional)"></textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <button type="button" data-close-transaction-modal class="rounded-2xl border border-slate-300 px-4 py-3 font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button class="rounded-2xl bg-emerald-600 px-4 py-3 font-semibold text-white hover:bg-emerald-700">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </template>
</div>
