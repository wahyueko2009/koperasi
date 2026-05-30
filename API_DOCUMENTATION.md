# API Documentation - Sistem Informasi Koperasi Terintegrasi

## Pengenalan

Sistem Informasi Koperasi Terintegrasi adalah aplikasi berbasis Laravel yang mengotomatisasi pencatatan transaksi dari berbagai unit usaha koperasi (Simpan Pinjam, Retail, Outsourcing) dengan integrasi akuntansi real-time.

## Fitur Utama

### 1. **Akuntansi Otomatis (Journal Entry)**
- Setiap transaksi ekonomi menghasilkan jurnal debet/kredit secara otomatis
- Chart of Accounts (COA) yang terstruktur
- General Ledger dan Member Ledger untuk tracking detail
- Laporan Keuangan otomatis (Laporan Laba Rugi, Neraca)

### 2. **Manajemen Simpan Pinjam**
- Pencatatan simpanan (Pokok, Wajib, Sukarela)
- Manajemen pinjaman dengan bunga otomatis
- Tracking pembayaran dan sisa pinjaman
- Automated interest calculation

### 3. **Modul Retail**
- POS Entry untuk Indomaret & Photocopy
- Payment Gateway Internal (Tunai / Potong Gaji)
- Automatic piutang anggota tracking

### 4. **Manajemen Member**
- Data keanggotaan terintegrasi
- Member ledger untuk posisi hutang/piutang
- Sync SDM via Excel import (ready)

## Instalasi

```bash
# Clone repository
git clone <repo>
cd koperasi

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --seed

# Build assets
npm run build

# Start server
php artisan serve
```

## Konfigurasi Database

File `.env` sudah dikonfigurasi menggunakan SQLite untuk development. Untuk production, ubah ke MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=koperasi
DB_USERNAME=root
DB_PASSWORD=
```

## Default Users

Setelah migration & seeding:

```
Email: admin@koperasi.local
Password: password
Role: admin

Email: finance@koperasi.local
Password: password
Role: finance

Email: ahmad@example.com
Password: password
Role: member
```

## API Endpoints

### Authentication

#### Login
```
POST /api/login
Content-Type: application/json

{
  "email": "admin@koperasi.local",
  "password": "password"
}

Response:
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {...},
    "token": "token_string"
  }
}
```

#### Logout
```
POST /api/logout
Authorization: Bearer {token}
```

#### Get Current User
```
GET /api/me
Authorization: Bearer {token}
```

### Members Management

#### List Members
```
GET /api/members
Authorization: Bearer {token}
Response: Paginated member list
```

#### Get Member Detail
```
GET /api/members/{id}
Authorization: Bearer {token}
```

#### Create Member
```
POST /api/members
Authorization: Bearer {token}
Content-Type: application/json

{
  "nik": "3201234567890000",
  "name": "Nama Lengkap",
  "email": "email@example.com",
  "phone": "08123456789",
  "address": "Alamat"
}
```

#### Update Member
```
PUT /api/members/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Nama Baru",
  "status": "active|inactive|suspended"
}
```

#### Get Member Balance
```
GET /api/members/{id}/balance
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "member": {...},
    "total_savings": 1000000,
    "total_loans": 5000000,
    "total_receivable": 100000
  }
}
```

### Savings Module

#### Record Savings
```
POST /api/savings
Authorization: Bearer {token}
Content-Type: application/json

{
  "member_id": 1,
  "saving_type_id": 1,
  "amount": 100000,
  "payment_method": "cash|transfer|salary_cut",
  "notes": "Optional notes"
}

Response: Created saving dengan jurnal otomatis
Journal dipindahkan ke:
  - Debit: 1001 (Kas)
  - Credit: 2101 (Hutang Simpanan Anggota)
```

#### Get Member Savings
```
GET /api/members/{memberId}/savings
Authorization: Bearer {token}
Response: Paginated list of savings
```

### Loans Module

#### Create Loan Application
```
POST /api/loans
Authorization: Bearer {token}
Content-Type: application/json

{
  "member_id": 1,
  "principal_amount": 5000000,
  "interest_rate": 2.5,
  "tenor_months": 12
}

Response: Loan dengan status "pending"
```

#### Approve Loan
```
POST /api/loans/{id}/approve
Authorization: Bearer {token} (role: admin/finance)

Response: Loan dengan status "approved"
```

#### Disburse Loan
```
POST /api/loans/{id}/disburse
Authorization: Bearer {token} (role: admin/finance)

Journal dipindahkan:
  - Debit: 1102 (Piutang Anggota)
  - Credit: 1001 (Kas)
```

#### Record Loan Payment
```
POST /api/loans/{id}/payment
Authorization: Bearer {token}
Content-Type: application/json

{
  "principal_paid": 400000,
  "interest_paid": 100000,
  "payment_method": "salary_cut",
  "notes": "Pembayaran ke-1"
}

Response: Payment dengan jurnal otomatis

Jurnal untuk pokok:
  - Debit: 1001 (Kas)
  - Credit: 1102 (Piutang Anggota)

Jurnal untuk bunga:
  - Debit: 1001 (Kas)
  - Credit: 4101 (Pendapatan Bunga)
```

#### Get Loan Payment History
```
GET /api/loans/{id}/payments
Authorization: Bearer {token}
Response: Paginated list of payments
```

### Retail Module

#### List Retail Items
```
GET /api/retail/items?category=indomaret|photocopy
Authorization: Bearer {token}
Response: Paginated list of items
```

#### Create Retail Transaction
```
POST /api/retail/transactions
Authorization: Bearer {token}
Content-Type: application/json

{
  "member_id": 1,
  "category": "indomaret",
  "items": [
    {
      "retail_item_id": 1,
      "quantity": 2,
      "unit_price": 2500,
      "subtotal": 5000
    }
  ],
  "payment_method": "cash|salary_cut",
  "notes": "Optional"
}

Response: Transaction dengan jurnal otomatis

Untuk pembayaran CASH:
  - Debit: 1001 (Kas)
  - Credit: 4102/4103 (Pendapatan Retail)

Untuk pembayaran POTONG GAJI:
  - Debit: 1102 (Piutang Anggota)
  - Credit: 4102/4103 (Pendapatan Retail)
  - Update member balance_receivable
```

#### Get Retail Transactions
```
GET /api/retail/transactions?member_id=1&category=indomaret
Authorization: Bearer {token}
Response: Paginated list of transactions
```

### Accounting Module

#### Get Chart of Accounts
```
GET /api/accounting/coa
Authorization: Bearer {token}
Response: List of all active accounts
```

#### Get General Ledger
```
GET /api/accounting/general-ledger?account_code=1001&from_date=2024-01-01&to_date=2024-12-31
Authorization: Bearer {token}

Response:
{
  "success": true,
  "account": {...},
  "balance": 1000000,
  "data": [...]
}
```

#### Get Journal Entries
```
GET /api/accounting/journal-entries?reference_type=savings&status=posted
Authorization: Bearer {token}
Response: Paginated list of journal entries
```

#### Income Statement (Laporan Laba Rugi)
```
GET /api/accounting/income-statement?from_date=2024-01-01&to_date=2024-12-31
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "from_date": "2024-01-01",
    "to_date": "2024-12-31",
    "income": {
      "details": [
        {"code": "4101", "name": "Pendapatan Bunga", "balance": 500000},
        ...
      ],
      "total": 2000000
    },
    "expenses": {
      "details": [...],
      "total": 1000000
    },
    "net_income": 1000000
  }
}
```

#### Balance Sheet (Laporan Neraca)
```
GET /api/accounting/balance-sheet?as_of_date=2024-12-31
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "as_of_date": "2024-12-31",
    "assets": {
      "details": [
        {"code": "1001", "name": "Kas", "balance": 10000000},
        ...
      ],
      "total": 25000000
    },
    "liabilities": {
      "details": [...],
      "total": 15000000
    },
    "equity": {
      "details": [...],
      "total": 10000000
    }
  }
}
```

## Chart of Accounts (COA)

### Assets (1000-1999)
- **1001** - Kas
- **1102** - Piutang Anggota
- **1201** - Inventori

### Liabilities (2000-2999)
- **2101** - Hutang Simpanan Anggota
- **2102** - Hutang Pinjaman Anggota

### Equity (3000-3999)
- **3101** - Modal Koperasi
- **3102** - Saldo Laba

### Income (4000-4999)
- **4101** - Pendapatan Bunga
- **4102** - Pendapatan Indomaret
- **4103** - Pendapatan Photocopy
- **4104** - Pendapatan Outsourcing

### Expenses (5000-5999)
- **5101** - Beban Gaji
- **5102** - Beban BPJS
- **5103** - Beban Operasional
- **5104** - Beban Pemeliharaan

## Workflow Penggunaan

### 1. Pencatatan Setoran Simpanan
1. Member setor simpanan via `/api/savings`
2. Service Layer membuat jurnal otomatis:
   - Debit: Kas (1001)
   - Kredit: Hutang Simpanan (2101)
3. Member Ledger ter-update
4. Dashboard menampilkan data real-time

### 2. Pencatatan Pembelian Retail
1. Staf input transaksi via `/api/retail/transactions`
2. Service Layer membuat jurnal:
   - Debit: Kas/Piutang (tergantung metode pembayaran)
   - Kredit: Pendapatan Retail (4102/4103)
3. Member balance_receivable terupdate
4. Laporan Laba Rugi otomatis terupdate

### 3. Pencairan Pinjaman
1. Member ajukan pinjaman via `/api/loans`
2. Admin approve via `/api/loans/{id}/approve`
3. Admin disburse via `/api/loans/{id}/disburse`
4. Jurnal pemindahan aset:
   - Debit: Piutang Anggota (1102)
   - Kredit: Kas (1001)

### 4. Pembayaran Pinjaman
1. Member bayar via `/api/loans/{id}/payment`
2. Service Layer membuat 2 jurnal:
   - Pokok: Debit Kas, Kredit Piutang Anggota
   - Bunga: Debit Kas, Kredit Pendapatan Bunga
3. Loan balance terupdate otomatis

## Technologies Used

- **Laravel 12.0** - Backend Framework
- **SQLite/MySQL** - Database
- **Sanctum** - API Authentication
- **Blade** - Template Engine
- **Chart.js** - Data Visualization
- **Tailwind CSS** - Styling

## Project Structure

```
app/
├── Models/              # Database models
├── Services/            # Business logic (Journaling)
├── Http/
│   └── Controllers/
│       ├── Api/         # API Controllers
│       └── DashboardController.php
database/
├── migrations/          # Schema
└── seeders/             # Data seeding
routes/
├── api.php              # API routes
└── web.php              # Web routes
resources/
├── views/
│   └── dashboard.blade.php
├── css/
└── js/
```

## Security Notes

1. Gunakan force HTTPS di production
2. Protect sensitive endpoints dengan role-based authorization
3. Validate semua input dari client
4. Use environment variables untuk secret keys
5. Regular database backups

## Future Enhancements

- [ ] Excel import/export untuk Member Sync SDM
- [ ] Multi-currency support
- [ ] Advanced reporting dengan filters
- [ ] Automated interest calculation (monthly)
- [ ] Mobile app
- [ ] Notification system
- [ ] Audit logging
- [ ] Role-based permissions granular

## Support

Untuk pertanyaan atau issue, hubungi tim development.

---

**Last Updated**: April 8, 2026
**Version**: 1.0.0
