<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pinjaman - Portal Anggota</title>
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
    <div class="mx-auto min-h-screen max-w-4xl bg-white shadow-xl">
        <header class="bg-primary px-6 py-5 text-white">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-200">Portal Anggota</p>
                    <h1 class="mt-2 text-2xl font-bold">Status Pinjaman</h1>
                    <p class="mt-1 text-sm text-slate-300">{{ $member->name }} | {{ $member->nik }}</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('member-portal.loans.create') }}" class="rounded-2xl bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20">
                        Ajukan Lagi
                    </a>
                    <a href="{{ route('member-portal') }}" class="rounded-2xl border border-white/15 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
                        Kembali
                    </a>
                </div>
            </div>
        </header>

        <main class="space-y-6 px-6 py-6">
            @if (session('success'))
                <section class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                    {{ session('success') }}
                </section>
            @endif

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl bg-slate-50 p-5 ring-1 ring-slate-200">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Total Pengajuan</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $loans->count() }}</p>
                </div>
                <div class="rounded-3xl bg-sky-50 p-5 ring-1 ring-sky-100">
                    <p class="text-xs uppercase tracking-[0.2em] text-sky-700">Masih Diproses</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $loans->whereIn('application_status', ['submitted', 'verified', 'treasurer_approved', 'approved'])->count() }}</p>
                </div>
                <div class="rounded-3xl bg-emerald-50 p-5 ring-1 ring-emerald-100">
                    <p class="text-xs uppercase tracking-[0.2em] text-emerald-700">Pinjaman Berjalan</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $loans->whereIn('status', ['disbursed', 'active'])->count() }}</p>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Riwayat Pengajuan</h2>
                        <p class="mt-1 text-sm text-slate-500">Tabel ringkas. Klik detail untuk melihat informasi lengkap.</p>
                    </div>
                </div>

                <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    <th class="px-5 py-4">Nomor</th>
                                    <th class="px-5 py-4">Jenis</th>
                                    <th class="px-5 py-4">Tanggal</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th class="px-5 py-4">Nominal</th>
                                    <th class="px-5 py-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($loans as $loan)
                                <tr class="{{ $loan->application_status === 'rejected' ? 'bg-rose-50/60' : '' }}">
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $loan->application_number }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $loan->loan_type_label }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ optional($loan->submission_date)->format('d M Y') ?: '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $loan->application_status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $loan->application_status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $loan->principal_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <button
                                            type="button"
                                            class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            data-open-loan-detail="{{ $loan->id }}"
                                        >
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                        @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center">
                                        <p class="text-lg font-semibold text-slate-900">Belum ada pengajuan pinjaman</p>
                                        <p class="mt-2 text-sm text-slate-500">Mulai dari pengajuan pertama Anda agar pengurus bisa memproses pinjaman.</p>
                                        <a href="{{ route('member-portal.loans.create') }}" class="mt-5 inline-flex rounded-2xl bg-accent px-5 py-3 text-sm font-semibold text-white hover:bg-teal-700">
                                            Ajukan Pinjaman
                                        </a>
                                    </td>
                                </tr>
                    @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div id="loan-detail-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/55 px-4 py-8">
        <div id="loan-detail-panel" class="w-full max-w-3xl rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200 transition-all duration-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Detail Pengajuan</p>
                    <h2 id="loan-detail-title" class="mt-2 text-2xl font-bold text-slate-900">Pinjaman</h2>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" data-maximize-modal="loan-detail" data-maximize-symbol class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-xl font-semibold leading-none text-slate-700 hover:bg-slate-50" aria-label="Maximize popup">
                        □
                    </button>
                    <button type="button" data-close-loan-detail class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-2xl leading-none text-slate-700 hover:bg-slate-50" aria-label="Close popup">
                        ×
                    </button>
                </div>
            </div>
            <div id="loan-detail-content" class="px-6 py-6"></div>
        </div>
    </div>

    <div id="loan-detail-templates" class="hidden">
        @foreach ($loans as $loan)
            <template data-loan-template="{{ $loan->id }}">
                <div data-loan-title="{{ $loan->application_number }}">
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Jenis</p>
                                <p class="mt-2 font-semibold text-slate-900">{{ $loan->loan_type_label }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Tenor</p>
                                <p class="mt-2 font-semibold text-slate-900">{{ $loan->tenor_months }} bulan</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nominal</p>
                                <p class="mt-2 font-semibold text-slate-900">Rp {{ number_format((float) $loan->principal_amount, 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</p>
                                <p class="mt-2 font-semibold {{ $loan->application_status === 'rejected' ? 'text-rose-700' : 'text-slate-900' }}">{{ $loan->application_status_label }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Sisa Pinjaman</p>
                                <p class="mt-2 font-semibold text-slate-900">{{ $loan->application_status === 'rejected' ? 'Rp 0' : 'Rp ' . number_format((float) $loan->remaining_balance, 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Metode Bayar</p>
                                <p class="mt-2 font-semibold text-slate-900">{{ str($loan->repayment_method)->replace('_', ' ')->title() }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50 px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Tujuan Pinjaman</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $loan->purpose ?: 'Belum ada keterangan tujuan pinjaman.' }}</p>
                        </div>

                        @if ($loan->application_status === 'rejected')
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-rose-700">Alasan Penolakan</p>
                                <p class="mt-2 text-sm leading-6 text-rose-700">{{ $loan->approval_notes ?: 'Pengajuan ini ditolak dan tidak dilanjutkan ke approval berikutnya.' }}</p>
                            </div>
                        @endif

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Dokumen Wajib</p>
                                <div class="mt-3 space-y-3 text-sm text-slate-700">
                                    @forelse ($loan->documents as $document)
                                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0 flex-1">
                                                    <p class="font-semibold text-slate-900">{{ $document->document_label }}</p>
                                                    <p class="mt-1 break-words text-xs text-slate-500">{{ $document->original_name ?: 'Belum ada file' }}</p>
                                                </div>
                                                <div class="flex flex-col items-start gap-3 sm:items-end">
                                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                        {{ str($document->status)->replace('_', ' ')->title() }}
                                                    </span>
                                                    @if ($document->file_path)
                                                        <button
                                                            type="button"
                                                            data-open-pdf-modal="{{ route('member-portal.loans.documents.download', $document) }}"
                                                            data-pdf-title="{{ $document->original_name ?: $document->document_label }}"
                                                            class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-700"
                                                        >
                                                            Lihat PDF
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-slate-500">Belum ada dokumen yang tercatat.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Catatan Approval</p>
                                <div class="mt-3 space-y-2 text-sm text-slate-700">
                                    @forelse ($loan->approvals as $approval)
                                        <div class="rounded-2xl bg-white px-3 py-3">
                                            <p class="font-semibold text-slate-900">{{ $approval->approval_level_label }}</p>
                                            <p class="mt-1 {{ $approval->status === 'rejected' ? 'text-rose-700' : 'text-slate-500' }}">{{ $approval->status_label }}</p>
                                            @if ($approval->official)
                                                <p class="mt-1 text-xs text-slate-500">Oleh {{ $approval->official->name }}</p>
                                            @endif
                                            @if ($approval->notes)
                                                <p class="mt-2 text-slate-700">{{ $approval->notes }}</p>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-slate-500">Belum ada catatan approval.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        @endforeach
    </div>
    <div id="pdf-preview-modal" class="fixed inset-0 z-[60] hidden items-start justify-center overflow-y-auto bg-slate-950/70 px-4 py-6">
        <div id="pdf-preview-panel" class="flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200 transition-all duration-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Preview Dokumen</p>
                    <h2 id="pdf-preview-title" class="mt-1 text-lg font-bold text-slate-900">PDF</h2>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" data-maximize-modal="pdf-preview" data-maximize-symbol class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-xl font-semibold leading-none text-slate-700 hover:bg-slate-50" aria-label="Maximize popup">
                        □
                    </button>
                    <button type="button" data-close-pdf-modal class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-2xl leading-none text-slate-700 hover:bg-slate-50" aria-label="Close popup">
                        ×
                    </button>
                </div>
            </div>
            <div class="flex-1 bg-slate-100 p-3">
                <iframe
                    id="pdf-preview-frame"
                    title="Preview PDF"
                    class="h-full w-full rounded-2xl border border-slate-200 bg-white"
                    src="about:blank"
                ></iframe>
            </div>
        </div>
    </div>
</body>
</html>

<script>
    (() => {
        const modal = document.getElementById('loan-detail-modal');
        const title = document.getElementById('loan-detail-title');
        const content = document.getElementById('loan-detail-content');
        const loanDetailPanel = document.getElementById('loan-detail-panel');
        const pdfModal = document.getElementById('pdf-preview-modal');
        const pdfTitle = document.getElementById('pdf-preview-title');
        const pdfFrame = document.getElementById('pdf-preview-frame');
        const pdfPreviewPanel = document.getElementById('pdf-preview-panel');
        const templates = new Map();

        document.querySelectorAll('[data-loan-template]').forEach((template) => {
            templates.set(template.dataset.loanTemplate, template);
        });

        const openModal = (loanId) => {
            const template = templates.get(String(loanId));
            if (!template) {
                return;
            }

            const node = template.content.firstElementChild.cloneNode(true);
            title.textContent = node.dataset.loanTitle || 'Pinjaman';
            content.innerHTML = '';
            content.appendChild(node.firstElementChild);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            content.innerHTML = '';
            loanDetailPanel.classList.remove('max-w-[96vw]', 'min-h-[92vh]');
            loanDetailPanel.classList.add('max-w-3xl');
            document.body.classList.remove('overflow-hidden');
        };

        const openPdfModal = (url, titleText) => {
            pdfTitle.textContent = titleText || 'PDF';
            pdfFrame.src = url;
            pdfModal.classList.remove('hidden');
            pdfModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        };

        const closePdfModal = () => {
            pdfModal.classList.add('hidden');
            pdfModal.classList.remove('flex');
            pdfFrame.src = 'about:blank';
            pdfPreviewPanel.classList.remove('max-w-[98vw]', 'h-[96vh]');
            pdfPreviewPanel.classList.add('max-w-5xl', 'h-[90vh]');
            document.body.classList.remove('overflow-hidden');
        };

        const toggleMaximize = (type) => {
            if (type === 'loan-detail') {
                loanDetailPanel.classList.toggle('max-w-3xl');
                loanDetailPanel.classList.toggle('max-w-[96vw]');
                loanDetailPanel.classList.toggle('min-h-[92vh]');
                return;
            }

            if (type === 'pdf-preview') {
                pdfPreviewPanel.classList.toggle('max-w-5xl');
                pdfPreviewPanel.classList.toggle('max-w-[98vw]');
                pdfPreviewPanel.classList.toggle('h-[90vh]');
                pdfPreviewPanel.classList.toggle('h-[96vh]');
            }
        };

        document.querySelectorAll('[data-open-loan-detail]').forEach((button) => {
            button.addEventListener('click', () => openModal(button.dataset.openLoanDetail));
        });

        document.querySelectorAll('[data-close-loan-detail]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-open-pdf-modal]');
            if (!trigger) {
                return;
            }

            openPdfModal(trigger.dataset.openPdfModal, trigger.dataset.pdfTitle);
        });

        document.querySelectorAll('[data-close-pdf-modal]').forEach((button) => {
            button.addEventListener('click', closePdfModal);
        });

        document.querySelectorAll('[data-maximize-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                toggleMaximize(button.dataset.maximizeModal);
                const isMaximized = button.dataset.maximizeModal === 'loan-detail'
                    ? loanDetailPanel.classList.contains('max-w-[96vw]')
                    : pdfPreviewPanel.classList.contains('max-w-[98vw]');
                button.textContent = isMaximized ? '−' : '□';
                button.setAttribute('aria-label', isMaximized ? 'Minimize popup' : 'Maximize popup');
            });
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        pdfModal.addEventListener('click', (event) => {
            if (event.target === pdfModal) {
                closePdfModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }

            if (event.key === 'Escape' && !pdfModal.classList.contains('hidden')) {
                closePdfModal();
            }
        });
    })();
</script>
