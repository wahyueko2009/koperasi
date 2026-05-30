<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Anggota - Koperasi Digital Mandiri</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-paper text-slate-900">
    <div class="min-h-screen bg-[linear-gradient(180deg,_#eef4ff_0%,_#f8fafc_28%,_#f8fafc_100%)]">
        <div class="mx-auto max-w-5xl px-4 py-6 lg:px-8 lg:py-8">
            <header class="overflow-hidden rounded-[2rem] bg-primary text-white shadow-xl shadow-slate-900/10">
                <div class="flex flex-col gap-6 px-6 py-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-200">Portal Anggota</p>
                        <h1 class="mt-3 text-3xl font-bold">Informasi Anggota</h1>
                        <p class="mt-2 text-sm text-slate-300">{{ $member->name }} | {{ $member->nik }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('member-portal.loans.create') }}" class="rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
                            Ajukan Pinjaman
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-2xl border border-white/15 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="mt-6 space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Pinjaman Saya</h2>
                        <p class="mt-1 text-sm text-slate-500">Ringkasan pinjaman yang masih diproses atau sedang berjalan.</p>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Jumlah Pinjaman</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($memberPortalStats['active_loan_count']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Outstanding</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">Rp {{ number_format($memberPortalStats['outstanding_loans'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Simpanan Saya</h2>
                        <p class="mt-1 text-sm text-slate-500">Total simpanan dan rincian jenis simpanan yang tercatat.</p>
                    </div>

                    <div class="mt-5 rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Total Simpanan</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">Rp {{ number_format($memberPortalStats['total_savings'], 0, ',', '.') }}</p>
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse ($savingsSummary as $saving)
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $saving['name'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $saving['description'] }}</p>
                                </div>
                                <p class="text-sm font-bold text-slate-900">Rp {{ number_format($saving['amount'], 0, ',', '.') }}</p>
                            </div>
                        @empty
                            <div class="rounded-2xl bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                Belum ada simpanan yang tercatat.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Riwayat Pengajuan</h2>
                        <p class="mt-1 text-sm text-slate-500">Ringkasan semua pengajuan pinjaman anggota dalam bentuk tabel.</p>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        <th class="px-4 py-4">Nomor</th>
                                        <th class="px-4 py-4">Jenis</th>
                                        <th class="px-4 py-4">Tanggal</th>
                                        <th class="px-4 py-4">Status</th>
                                        <th class="px-4 py-4">Nominal</th>
                                        <th class="px-4 py-4">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($loanHistoryItems as $loan)
                                    <tr class="cursor-pointer transition hover:bg-slate-50 {{ $loan->application_status === 'rejected' ? 'bg-rose-50/60 hover:bg-rose-50' : '' }}" data-open-loan-detail="{{ $loan->id }}">
                                        <td class="px-4 py-4 align-top">
                                            <p class="font-semibold text-slate-900">{{ $loan->application_number }}</p>
                                        </td>
                                        <td class="px-4 py-4 align-top text-sm text-slate-700">{{ $loan->loan_type_label }}</td>
                                        <td class="px-4 py-4 align-top text-sm text-slate-700">{{ optional($loan->submission_date)->format('d M Y') ?: '-' }}</td>
                                        <td class="px-4 py-4 align-top">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $loan->application_status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $loan->application_status_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 align-top text-sm font-semibold text-slate-900">Rp {{ number_format((float) $loan->principal_amount, 0, ',', '.') }}</td>
                                        <td class="px-4 py-4 align-top text-sm {{ $loan->application_status === 'rejected' ? 'text-rose-700' : 'text-slate-600' }}">
                                            {{ $loan->application_status === 'rejected'
                                                ? ($loan->approval_notes ?: 'Pengajuan ini tidak dilanjutkan ke tahap approval berikutnya.')
                                                : 'Pengajuan masih diproses sesuai tahapan yang berjalan.' }}
                                        </td>
                                    </tr>
                        @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                            Belum ada pengajuan pinjaman yang tercatat.
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
        @foreach ($loanHistoryItems as $loan)
            <template data-loan-template="{{ $loan->id }}">
                <div data-loan-title="{{ $loan->application_number }}">
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Jenis</p>
                                <p class="mt-2 font-semibold text-slate-900">{{ $loan->loan_type_label }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Tanggal Pengajuan</p>
                                <p class="mt-2 font-semibold text-slate-900">{{ optional($loan->submission_date)->format('d M Y') ?: '-' }}</p>
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
                    <button type="button" data-maximize-modal="pdf-preview" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-xl font-semibold leading-none text-slate-700 hover:bg-slate-50" aria-label="Maximize popup">&#9633;</button>
                    <button type="button" data-close-pdf-modal class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-2xl leading-none text-slate-700 hover:bg-slate-50" aria-label="Close popup">&times;</button>
                </div>
            </div>
            <div class="flex-1 bg-slate-100 p-3">
                <iframe id="pdf-preview-frame" title="Preview PDF" class="h-full w-full rounded-2xl border border-slate-200 bg-white" src="about:blank"></iframe>
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
        const panel = document.getElementById('loan-detail-panel');
        const pdfModal = document.getElementById('pdf-preview-modal');
        const pdfTitle = document.getElementById('pdf-preview-title');
        const pdfFrame = document.getElementById('pdf-preview-frame');
        const pdfPanel = document.getElementById('pdf-preview-panel');
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
            panel.classList.remove('max-w-[96vw]', 'min-h-[92vh]');
            panel.classList.add('max-w-3xl');
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
            pdfPanel.classList.remove('max-w-[98vw]', 'h-[96vh]');
            pdfPanel.classList.add('max-w-5xl', 'h-[90vh]');
            document.body.classList.remove('overflow-hidden');
        };

        document.querySelectorAll('[data-open-loan-detail]').forEach((row) => {
            row.addEventListener('click', () => openModal(row.dataset.openLoanDetail));
        });

        document.querySelectorAll('[data-close-loan-detail]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.querySelectorAll('[data-maximize-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                if (button.dataset.maximizeModal === 'loan-detail') {
                    panel.classList.toggle('max-w-3xl');
                    panel.classList.toggle('max-w-[96vw]');
                    panel.classList.toggle('min-h-[92vh]');
                    const maximized = panel.classList.contains('max-w-[96vw]');
                    button.innerHTML = maximized ? '&#8722;' : '&#9633;';
                    button.setAttribute('aria-label', maximized ? 'Minimize popup' : 'Maximize popup');
                    return;
                }

                pdfPanel.classList.toggle('max-w-5xl');
                pdfPanel.classList.toggle('max-w-[98vw]');
                pdfPanel.classList.toggle('h-[90vh]');
                pdfPanel.classList.toggle('h-[96vh]');
                const maximized = pdfPanel.classList.contains('max-w-[98vw]');
                button.innerHTML = maximized ? '&#8722;' : '&#9633;';
                button.setAttribute('aria-label', maximized ? 'Minimize popup' : 'Maximize popup');
            });
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
