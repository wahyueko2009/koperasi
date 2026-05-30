# Acuan Pengembangan Aplikasi Koperasi

Dokumen ini menjadi acuan utama saat mengembangkan aplikasi koperasi ini ke depan.

Dokumen ini disusun untuk membantu kita merancang sistem koperasi sesuai kebutuhan proses bisnis kita sendiri, tanpa bergantung pada benchmark produk tertentu.

## Arsitektur yang Dipakai

Arsitektur pengembangan yang dipakai saat ini tetap mengikuti fondasi yang sudah ada:

- XAMPP sebagai lingkungan lokal
- Apache/PHP untuk menjalankan aplikasi Laravel
- MySQL sebagai database utama
- Aplikasi berjalan di basis proyek lokal yang sudah aktif

Keputusan ini dipakai agar fokus pengembangan tetap pada penyelesaian modul, bukan pindah stack atau mengganti arsitektur di tengah jalan.

## Domain Aktivitas dan Arah Role

Aplikasi ini dirancang untuk berkembang ke dalam 3 domain aktivitas utama:

1. **Admin Koperasi**
2. **Admin Finance**
3. **Admin Bisnis**

Pembagian awal domain:

### Admin Koperasi

Fokus utama:
- Anggota
- Simpanan
- Pinjaman
- Approval dasar operasional koperasi
- Data dasar dan aktivitas inti koperasi

### Admin Finance

Fokus utama:
- Jurnal
- Buku besar
- Laba rugi
- Neraca
- Kas dan rekonsiliasi
- Kontrol laporan keuangan

### Admin Bisnis

Fokus utama:
- Unit usaha koperasi
- Retail / POS
- Produk dan stok
- Penjualan
- Piutang dari aktivitas bisnis
- Ringkasan performa unit usaha

## Strategi Pengembangan Bertahap

Strategi pengembangan aplikasi ini dilakukan secara bertahap dan berurutan.

Prinsip utamanya:

- kita tidak mengerjakan semua domain secara bersamaan,
- kita menyelesaikan domain yang sedang aktif sampai cukup usable,
- setelah domain aktif stabil, baru pindah ke domain berikutnya,
- setiap domain harus meninggalkan fondasi data, alur, dan tampilan yang cukup rapi agar tidak dibongkar ulang terlalu besar.

Urutan domain yang dipakai:

1. Admin Koperasi
2. Admin Finance
3. Admin Bisnis

Keputusan aktif saat ini:

- fokus utama kita ada di **Domain 1: Admin Koperasi**
- domain ini harus dirapikan dulu end-to-end,
- domain lain belum menjadi fokus utama sebelum domain pertama cukup beres.

Implikasi kerja:

- jika ada pekerjaan baru, prioritaskan pekerjaan yang langsung menguatkan domain Admin Koperasi,
- pekerjaan di domain Finance atau Bisnis hanya dikerjakan jika benar-benar diperlukan untuk menopang domain pertama,
- setelah domain pertama selesai lebih rapi, usable, dan alurnya jelas, barulah fokus dipindahkan ke domain berikutnya.

## Strategi Menu dan Akses

Strategi menu dan akses harus mengikuti domain dan pekerjaan yang benar-benar sudah selesai atau cukup siap dipakai.

Prinsip utamanya:

- jangan tampilkan semua menu sekaligus,
- hanya tampilkan menu yang sudah relevan dengan domain aktif,
- menu yang belum selesai sebaiknya disembunyikan,
- struktur navigasi harus membantu fokus kerja user, bukan membuat bingung.

Aturan untuk fase sekarang:

- karena domain aktif adalah **Admin Koperasi**, maka menu yang ditampilkan harus berfokus pada area ini,
- jika sebuah modul belum usable atau belum siap dipakai end-to-end, menu modul itu tidak perlu ditampilkan dulu,
- menu dari domain Finance dan Bisnis tidak perlu dimunculkan penuh sebelum domainnya menjadi fokus aktif,
- lebih baik menu sedikit tetapi jelas, daripada banyak tetapi membingungkan.

Implikasi desain:

- navigasi aplikasi harus bersifat bertahap,
- menu akan bertambah seiring domain atau modul benar-benar selesai,
- hak akses dan visibilitas menu harus mengikuti konteks login serta status kesiapan modul.

## Area Pendukung yang Harus Diingat

Walaupun inti domain hanya 3, ada area pendukung yang harus masuk pertimbangan desain sejak awal:

- Approval / manajerial
- Portal anggota
- Pengaturan sistem
- Laporan lintas unit

## Keputusan Konteks Login

Untuk tahap pengembangan berikutnya, sistem login akan mengikuti prinsip:

- Sebelum memasukkan email dan password, user memilih dulu konteks masuk:
- **Anggota**
- **Pengurus**
- Pilihan konteks login bersifat **mandatory**.
- Validasi akhir tetap ditentukan oleh data user di backend, bukan hanya pilihan di layar login.

Prinsip penting:

- Satu orang bisa memiliki **dua konteks sekaligus**:
- sebagai **Anggota**
- sebagai **Pengurus**
- Jika seorang pengurus ingin menggunakan layanan koperasi seperti simpanan atau pinjaman pribadi, maka ia tetap masuk dalam konteks **Anggota**.
- Jika orang yang sama menjalankan operasional koperasi, approval, atau tugas jabatan, maka ia masuk dalam konteks **Pengurus**.

Implikasi desain:

- Data **Anggota** tetap terpisah dari data **Pengurus**.
- Data **Pengurus** dipakai untuk jabatan, approval, dan operasional organisasi.
- Satu akun login dapat dihubungkan ke:
- data anggota
- data pengurus
- Sistem akan menentukan akses dan alur berdasarkan konteks yang dipilih saat login dan relasi yang valid pada akun tersebut.

Catatan:
- Area-area ini tidak harus langsung menjadi role terpisah.
- Untuk tahap sekarang, area pendukung boleh tetap menempel pada domain yang paling relevan.
- Saat sistem semakin matang, area ini bisa dipecah menjadi hak akses yang lebih spesifik.

## Prinsip Pengembangan

Prinsip yang dipakai saat menentukan prioritas:

1. Selesaikan modul yang paling terlihat hasilnya lebih dulu.
2. Dahulukan modul yang menjadi fondasi modul lain.
3. Pastikan setiap fitur punya alur end-to-end, bukan hanya tampilan.
4. Setiap transaksi bisnis harus siap terhubung ke jurnal dan laporan.
5. Hindari membuka terlalu banyak modul sekaligus.

## Urutan Modul

Urutan pengembangan utama:

1. Modul Anggota
2. Modul Simpanan
3. Modul Pinjaman
4. Modul Akuntansi dan Laporan
5. Dashboard
6. Portal Anggota
7. Retail/POS
8. Role, Approval, dan Audit Trail
9. Multi Unit / Multi Cabang / Integrasi Lanjutan

Alasan urutan ini:
- **Anggota** paling cepat membuat aplikasi terlihat nyata dan menjadi fondasi transaksi.
- **Simpanan** adalah modul inti paling sederhana setelah anggota.
- **Pinjaman** penting, tetapi lebih kompleks dan bergantung pada data anggota yang rapi.
- **Akuntansi** harus diperkuat setelah transaksi inti stabil agar tidak sering dibongkar ulang.
- **Dashboard** akan lebih bermakna kalau data inti sudah benar.
- **Portal Anggota** dan **Retail/POS** sebaiknya hadir setelah core koperasi stabil.

## Fokus Tahap Sekarang

Tahap aktif yang menjadi acuan kita saat ini adalah:

## Domain Aktif Sekarang: Admin Koperasi

Semua pekerjaan utama yang sedang berjalan harus mendukung domain ini terlebih dahulu.

Cakupan domain aktif saat ini:

- data anggota
- simpanan
- pinjaman
- approval dasar operasional koperasi
- portal anggota pada level dasar

Domain ini menjadi fondasi sebelum kita masuk lebih serius ke:

- Admin Finance
- Admin Bisnis

## Tahap 1: Modul Anggota

Modul ini dikerjakan sampai usable sebelum pindah fokus penuh ke modul berikutnya.

### Tujuan

Membuat pengelolaan anggota menjadi rapi, mudah dipakai, dan siap menjadi dasar untuk simpanan, pinjaman, serta portal anggota.

### Cakupan Utama

- Daftar anggota
- Tambah anggota
- Edit anggota
- Detail anggota
- Status anggota
- Pencarian dan filter
- Ringkasan transaksi anggota
- Riwayat simpanan, pinjaman, dan piutang anggota

### Hasil yang Harus Terlihat

- Admin bisa melihat daftar anggota yang jelas dan mudah dicari.
- Admin bisa menambah dan memperbarui data anggota tanpa error.
- Setiap anggota punya halaman detail yang berguna, bukan hanya biodata.
- Halaman detail menampilkan status, saldo penting, dan histori transaksi utama.

### Data Minimal yang Harus Rapi

- Nomor anggota
- NIK atau identitas
- Nama
- Email
- Nomor telepon
- Alamat
- Tanggal bergabung
- Status anggota

### Fitur Prioritas Modul Anggota

#### Prioritas A

- Halaman daftar anggota
- Form tambah anggota
- Form edit anggota
- Validasi data wajib
- Search berdasarkan nama, nomor anggota, atau NIK
- Filter berdasarkan status anggota

#### Prioritas B

- Halaman detail anggota
- Ringkasan saldo simpanan
- Outstanding pinjaman
- Piutang retail anggota
- Histori transaksi terbaru anggota

#### Prioritas C

- Pagination yang rapi
- Badge status yang jelas
- Aksi nonaktifkan atau suspend anggota
- Catatan internal anggota

## Definition of Done Modul Anggota

Modul anggota dianggap selesai jika:

- CRUD anggota berjalan dari UI dan backend.
- Validasi form konsisten di web dan API.
- Daftar anggota sudah bisa dicari dan difilter.
- Halaman detail anggota menampilkan informasi yang benar dan berguna.
- Tidak ada error query atau relasi saat membuka data anggota.
- Data anggota sudah siap dipakai oleh modul simpanan dan pinjaman.

## Rencana Setelah Modul Anggota

### Tahap 2: Modul Simpanan

Target:
- Jenis simpanan
- Setoran
- Penarikan
- Histori simpanan per anggota
- Ringkasan saldo simpanan
- Kesiapan posting jurnal

### Tahap 3: Modul Pinjaman

Target:
- Pengajuan pinjaman
- Persetujuan pinjaman
- Pencairan
- Angsuran
- Sisa pinjaman
- Histori pembayaran

Catatan arah desain:
- Modul pinjaman kemungkinan akan memakai **master jenis pinjaman / produk pinjaman** tersendiri.
- Alasan utamanya karena setiap jenis pinjaman bisa memiliki aturan berbeda, seperti:
- bunga
- tenor minimum dan maksimum
- plafon
- metode perhitungan angsuran
- biaya admin / provisi
- denda atau aturan khusus lain
- Jika keputusan ini dikonfirmasi admin, maka pengajuan pinjaman tidak lagi memasukkan seluruh parameter secara manual, tetapi memilih jenis pinjaman dari master yang sudah disiapkan.
- Status saat ini: **menunggu konfirmasi admin** sebelum implementasi struktur tabel dan perubahan UI form pinjaman.

Update arah desain:
- Konfirmasi admin sudah mengerucut: modul pinjaman harus mengikuti form pengajuan pinjaman koperasi yang nyata, bukan form generik.
- Dokumen acuan baru untuk tahap ini adalah [BRD_MODUL_PINJAMAN.md](C:/xampp/htdocs/koperasi/BRD_MODUL_PINJAMAN.md).
- Jenis pinjaman minimal yang harus didukung:
- Pinjaman Emergency
- Pinjaman Pendidikan
- Pinjaman Serbaguna
- Pinjaman Multiguna Plus
- Setiap jenis pinjaman memiliki:
- field umum,
- field khusus,
- syarat lampiran,
- kemungkinan aturan approval yang berbeda.
- Implikasi langsung ke aplikasi:
- form pinjaman saat ini perlu didesain ulang,
- struktur data pinjaman perlu diperluas,
- daftar kerja operasional pinjaman perlu dibedakan dari draft sampai pencairan,
- portal anggota nantinya harus bisa menampilkan status pengajuan pinjaman secara jelas.

### Tahap 4: Modul Akuntansi dan Laporan

Target:
- Jurnal umum
- Buku besar
- Neraca
- Laba rugi
- Konsistensi posting otomatis
- Export laporan dasar

### Tahap 5: Dashboard

Target:
- KPI utama
- Ringkasan transaksi terbaru
- Grafik simpanan, pinjaman, dan kas
- Informasi yang sinkron dengan laporan

### Tahap 6: Portal Anggota

Target:
- Login anggota
- Lihat saldo
- Lihat histori
- Ajukan pinjaman
- Lihat status pengajuan

### Tahap 7: Retail/POS

Target:
- Master produk
- Stok
- Penjualan
- Piutang retail anggota
- Integrasi jurnal

## Cara Mengambil Keputusan Saat Mengembangkan

Jika ada dua pekerjaan yang sama-sama penting, pilih yang memenuhi urutan berikut:

1. Membuka jalan untuk modul berikutnya
2. Mengurangi kemungkinan bongkar ulang
3. Membuat hasil lebih cepat terlihat oleh user
4. Meningkatkan kualitas data dan validasi
5. Memperkuat integrasi ke akuntansi

## Aturan Kerja untuk Pengembangan Berikutnya

Saat mengembangkan aplikasi ini, gunakan aturan berikut:

- Setiap perubahan harus mengikuti urutan modul di dokumen ini.
- Jangan lompat ke modul lanjutan sebelum modul aktif cukup usable.
- Untuk tahap sekarang, prioritas utama adalah **Modul Anggota**.
- Jika ada pekerjaan kecil di modul lain, tetap boleh dikerjakan hanya jika membantu penyelesaian modul aktif.
- Setiap fitur baru sebaiknya dinilai dengan pertanyaan: "Apakah ini membuat modul aktif lebih lengkap, lebih stabil, atau lebih siap dipakai?"

## Keputusan Saat Ini

Keputusan aktif proyek:

- Strategi pengembangan: **per modul**
- Pendekatan delivery: **bertahap per domain**
- Domain aktif saat ini: **Admin Koperasi**
- Modul yang diselesaikan terlebih dahulu: **Modul Anggota**
- Setelah Modul Anggota stabil, lanjut ke: **Modul Simpanan**
