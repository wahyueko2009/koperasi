# BRD Modul Pengajuan Pinjaman Koperasi

Dokumen ini menjadi acuan baru untuk merapikan ulang modul pinjaman agar sesuai dengan proses pengajuan pinjaman koperasi yang nyata, berdasarkan form pengajuan pinjaman internal koperasi yang saat ini digunakan.

## 1. Tujuan

Membangun modul pengajuan pinjaman yang:

- mudah diisi oleh anggota atau admin koperasi,
- sesuai dengan proses bisnis koperasi,
- mampu membedakan jenis pinjaman dan kebutuhan datanya,
- siap diproses ke approval, pencairan, angsuran, dan pelaporan,
- tidak lagi bergantung pada form generik yang terlalu sederhana.

## 2. Masalah Sistem Saat Ini

Modul pinjaman yang ada saat ini baru menampung data:

- anggota,
- nominal pokok,
- bunga,
- tenor,
- catatan umum.

Kondisi tersebut belum cukup untuk pengajuan pinjaman koperasi karena belum mencakup:

- jenis pinjaman,
- tujuan penggunaan dana,
- data pendukung khusus per jenis pinjaman,
- metode pembayaran kembali,
- data jaminan,
- checklist lampiran,
- status proses pengajuan yang lebih rinci,
- jejak verifikasi dan persetujuan.

## 3. Sasaran Bisnis

Modul ini harus mampu menangani minimal 4 jenis pengajuan:

1. Pinjaman Emergency
2. Pinjaman Pendidikan
3. Pinjaman Serbaguna
4. Pinjaman Multiguna Plus

Masing-masing jenis pinjaman memiliki field tambahan dan syarat dokumen yang berbeda.

## 4. Aktor

### 4.1 Anggota

Hak utama:

- melihat informasi produk pinjaman,
- membuat pengajuan pinjaman,
- melengkapi data pendukung,
- melihat status pengajuan,
- melihat histori pinjaman miliknya.

### 4.2 Admin Koperasi

Hak utama:

- membuat pengajuan atas nama anggota bila diperlukan,
- memeriksa kelengkapan form,
- memvalidasi data pengajuan,
- meneruskan ke tahap approval,
- memproses pencairan,
- memantau pinjaman berjalan.

### 4.3 Pengurus / Approver

Hak utama:

- meninjau pengajuan,
- menyetujui atau menolak,
- memberi catatan approval,
- memastikan pengajuan sesuai kebijakan koperasi.

## 5. Struktur Alur Bisnis

Alur utama modul pinjaman:

1. Draft pengajuan dibuat
2. Data dan lampiran dilengkapi
3. Pengajuan diajukan
4. Verifikasi administrasi
5. Approval pengurus
6. Siap dicairkan
7. Dicairkan
8. Pinjaman berjalan
9. Lunas / selesai

Tambahan status pengecualian:

- ditolak
- dibatalkan
- perlu revisi

## 6. Data Utama Pengajuan

### 6.1 Data Anggota

Field minimal:

- nomor anggota / id anggota
- NIK
- nama lengkap
- unit kerja / company
- nomor telepon / HP
- alamat sesuai KTP
- alamat sekarang
- nomor tabungan / rekening payroll
- status anggota
- jenis keanggotaan: anggota biasa / pengurus

Catatan:

- data anggota sebisa mungkin ditarik otomatis dari master anggota,
- form pinjaman tidak boleh memaksa entry ulang data yang sebenarnya sudah tersedia di master.

### 6.2 Data Pengajuan Umum

Field minimal:

- nomor pengajuan
- tanggal pengajuan
- jenis pinjaman
- nominal pengajuan
- tenor pengembalian dalam bulan
- tujuan penggunaan dana
- metode pembayaran angsuran
- catatan pemohon
- catatan verifikator
- catatan approver

### 6.3 Metode Pembayaran Angsuran

Nilai minimal:

- potong gaji
- debet rekening payroll
- transfer

## 7. Kebutuhan Khusus per Jenis Pinjaman

### 7.1 Pinjaman Emergency

Tujuan:

- kebutuhan medis / darurat keluarga inti.

Field tambahan:

- nama keluarga pasien
- hubungan keluarga
- nama rumah sakit
- alamat rumah sakit
- telepon rumah sakit
- nilai estimasi kebutuhan / tagihan

Lampiran wajib:

- kwitansi / estimasi rumah sakit
- KTP suami / istri bila relevan

### 7.2 Pinjaman Pendidikan

Tujuan:

- biaya pendidikan anak / pasangan.

Field tambahan:

- nama anak / pasangan
- hubungan keluarga
- anak ke
- jenjang pendidikan
- nama sekolah
- alamat sekolah
- telepon sekolah
- semester / tahun ajaran
- kebutuhan biaya pendidikan

Lampiran wajib:

- kartu keluarga / akte lahir
- bukti biaya sekolah bila ada

### 7.3 Pinjaman Serbaguna

Tujuan:

- kebutuhan umum anggota yang tidak masuk kategori emergency atau pendidikan.

Field tambahan:

- tujuan pinjaman rinci
- penggunaan dana
- sumber pelunasan

Lampiran minimum:

- identitas pendukung sesuai kebijakan koperasi

### 7.4 Pinjaman Multiguna Plus

Tujuan:

- kebutuhan multiguna dengan dukungan jaminan atau syarat tambahan.

Field tambahan:

- tujuan pinjaman rinci
- jenis jaminan
- nilai jaminan
- deskripsi jaminan
- dokumen pendukung jaminan

Lampiran wajib:

- dokumen jaminan
- identitas terkait jaminan

## 8. Kebutuhan Dokumen dan Lampiran

Sistem harus mendukung pencatatan lampiran untuk setiap pengajuan.

Minimal data lampiran:

- jenis lampiran
- nama file
- tanggal upload
- status validasi dokumen
- catatan verifikator

Status dokumen:

- belum diunggah
- sudah diunggah
- valid
- perlu revisi
- ditolak

## 9. Aturan Bisnis Inti

### 9.1 Validasi Dasar

- hanya anggota aktif yang dapat mengajukan pinjaman,
- anggota dengan status nonaktif atau suspend tidak boleh mengajukan,
- pengajuan tidak bisa masuk ke approval bila data wajib belum lengkap,
- pengajuan tidak bisa dicairkan bila belum disetujui.

### 9.2 Validasi Berdasarkan Jenis Pinjaman

- field khusus hanya wajib sesuai jenis pinjaman yang dipilih,
- lampiran wajib harus lengkap sebelum status naik ke approval,
- jaminan wajib muncul hanya untuk produk yang membutuhkan jaminan.

### 9.3 Aturan Proses

- satu pengajuan harus punya satu jenis pinjaman,
- perubahan jenis pinjaman setelah verifikasi harus dibatasi,
- histori perubahan status harus tercatat,
- setiap approval / penolakan wajib menyimpan nama pengurus, tanggal, dan catatan.

## 10. Perubahan yang Dibutuhkan di Aplikasi

### 10.1 Perubahan Data

Tabel pinjaman perlu diperluas atau dipecah agar bisa menampung:

- data pengajuan umum,
- data khusus per jenis pinjaman,
- data approval,
- data lampiran.

Rekomendasi arah desain:

1. Tetap pakai tabel `loans` sebagai inti pinjaman
2. Tambah tabel detail pengajuan dan tabel lampiran

Contoh arah struktur:

- `loans`
- `loan_documents`
- `loan_approvals`

Opsional jika ingin lebih rapi:

- `loan_products`
- `loan_application_histories`

### 10.2 Perubahan UI

Form pengajuan tidak lagi memakai satu modal sederhana.

UI baru sebaiknya berbentuk langkah bertahap:

1. Pilih anggota
2. Pilih jenis pinjaman
3. Isi data umum
4. Isi data khusus sesuai jenis pinjaman
5. Upload / checklist lampiran
6. Review ringkasan
7. Submit

### 10.3 Perubahan Workflow

Area pinjaman perlu dipecah menjadi halaman yang lebih jelas:

- daftar draft pengajuan
- daftar menunggu verifikasi
- daftar menunggu approval
- daftar siap cair
- daftar pinjaman berjalan
- daftar pinjaman selesai

## 11. Definition of Done

Modul pengajuan pinjaman dianggap sesuai BRD ini jika:

- jenis pinjaman bisa dipilih dengan jelas,
- field khusus tampil dinamis sesuai jenis pinjaman,
- data anggota dasar terisi otomatis dari master anggota,
- dokumen pendukung bisa dicatat / diunggah,
- status pengajuan bergerak sesuai alur bisnis,
- approval dan pencairan tercatat rapi,
- histori pinjaman dapat ditinjau dari sisi anggota maupun admin,
- desain UI lebih rapi daripada format surat manual.

## 12. Prioritas Implementasi

### Prioritas A

- rapikan BRD dan struktur data,
- tambah master jenis pinjaman,
- redesign form pengajuan,
- tambah status proses pengajuan,
- simpan tujuan pinjaman dan metode angsuran.

### Prioritas B

- field dinamis per jenis pinjaman,
- data lampiran,
- catatan verifikasi dan approval,
- daftar kerja operasional per status.

### Prioritas C

- upload file lampiran,
- notifikasi perubahan status,
- histori audit lengkap,
- tampilan portal anggota untuk tracking pengajuan.

## 13. Catatan Desain

Prinsip desain yang dipakai:

- anggota tidak mengisi ulang data yang sudah ada di master,
- form harus terasa seperti aplikasi, bukan surat ketik,
- field wajib hanya muncul saat relevan,
- UI harus mendukung kerja admin dan anggota sekaligus,
- struktur data harus siap berkembang ke approval, pencairan, dan pelaporan.
