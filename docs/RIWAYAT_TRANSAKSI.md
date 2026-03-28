# 📊 Dokumentasi Riwayat Transaksi

## Daftar Isi
- [Gambaran Umum](#gambaran-umum)
- [Halaman List Transaksi](#halaman-list-transaksi)
- [Halaman Detail Transaksi](#halaman-detail-transaksi)
- [Fitur-Fitur](#fitur-fitur)
- [Teknologi](#teknologi)

---

## Gambaran Umum

Modul **Riwayat Transaksi** adalah fitur untuk Owner yang memungkinkan monitoring dan analisis transaksi penjualan yang telah terjadi. Fitur ini menyediakan tampilan yang informatif dan mudah digunakan dengan berbagai filter dan opsi export.

### Lokasi File
- **Controller**: `app/Http/Controllers/Owner/TransactionHistoryController.php`
- **View Index**: `resources/views/owner/transactions/index.blade.php`
- **View Detail**: `resources/views/owner/transactions/show.blade.php`
- **Routes**: `routes/web.php` (prefix: `owner/transactions`)

### Route yang Tersedia
```php
GET  /owner/transactions          → index   (List transaksi)
GET  /owner/transactions/export   → export  (Export CSV)
GET  /owner/transactions/{id}     → show    (Detail transaksi)
```

---

## Halaman List Transaksi

### 📈 Statistik Cards

Menampilkan 4 kartu statistik utama:

1. **Total Transaksi**
   - Icon: Clipboard (biru)
   - Menampilkan jumlah total transaksi dalam periode
   - Format: angka dengan satuan "trx"

2. **Total Omzet**
   - Icon: Uang (hijau emerald)
   - Menampilkan total pendapatan dari semua transaksi
   - Format: Rupiah

3. **Rata-rata / Trx**
   - Icon: Chart bar (ungu violet)
   - Menampilkan nilai rata-rata per transaksi
   - Perhitungan: Total Omzet ÷ Total Transaksi
   - Format: Rupiah

4. **Kasir Teraktif**
   - Icon: User (orange)
   - Menampilkan nama kasir dengan transaksi terbanyak
   - Ditentukan dari jumlah transaksi dalam periode

### 🔍 Filter & Pencarian

#### 1. Filter Periode Cepat
Tombol preset untuk filter cepat:
- **Harian**: Hari ini
- **Mingguan**: 7 hari terakhir
- **Bulanan**: Bulan berjalan
- **Tahunan**: Tahun berjalan

#### 2. Custom Date Range
- Date picker untuk memilih tanggal mulai dan akhir
- Auto-submit saat tanggal dipilih
- Validasi: tidak bisa pilih tanggal masa depan
- Format: `dd MMM yyyy`

#### 3. Search Bar
- Pencarian real-time dengan debounce 500ms
- Full width di mobile untuk better UX
- Icon search untuk visual clarity
- Bisa mencari berdasarkan:
  - Kode transaksi
  - Nama kasir
  - Username kasir
- Placeholder: "Cari Kode Transaksi atau Nama Kasir..."
- Background: `bg-slate-100 dark:bg-slate-800`
- Focus state dengan ring blue

#### 4. Filter Dropdown
Dua dropdown filter tersedia:

**Filter Kasir**
- Dropdown dengan daftar kasir yang pernah melakukan transaksi
- Icon user untuk visual clarity
- Label "Kasir" di atas dropdown
- Option "Semua Kasir" untuk reset filter
- Auto-submit saat dipilih
- Grid layout: 2 kolom di mobile, side by side di desktop
- Custom dark mode styling untuk dropdown options

**Filter Metode Pembayaran**
- Dropdown dengan semua metode pembayaran aktif
- Icon credit card untuk visual clarity
- Label "Metode" di atas dropdown
- Option "Semua Metode" untuk reset filter
- Auto-submit saat dipilih
- Grid layout: 2 kolom di mobile, side by side di desktop
- Custom dark mode styling untuk dropdown options

#### 5. Tombol Export
- Export data ke format CSV
- Icon download untuk visual clarity
- Full width di mobile (grid 2 kolom dengan Reset)
- Background: `bg-slate-900 dark:bg-slate-700`
- Hover effect: scale-105
- Nama file: `riwayat-transaksi-{dateFrom}_sd_{dateTo}.csv`
- Encoding: UTF-8 (dengan BOM untuk Excel compatibility)
- Kolom yang di-export:
  - Kode
  - Kasir
  - Metode Pembayaran
  - Status
  - Jumlah Item
  - Total
  - Dibayar
  - Kembalian
  - Waktu

#### 6. Reset Filter
- Tombol "Reset" muncul saat ada filter aktif
- Icon X untuk clarity
- Grid 2 kolom dengan Export di mobile
- Background: `bg-slate-100 dark:bg-slate-800`
- Text color: red untuk indicate destructive action
- Hover effect: `bg-red-50 dark:bg-red-900/20`
- Menghapus semua filter dan kembali ke default

### 📋 Daftar Transaksi

#### Pengelompokan
Transaksi dikelompokkan berdasarkan **tanggal** dengan header yang menampilkan:
- Tanggal lengkap (format: `d F Y`)
- Jumlah transaksi pada tanggal tersebut

#### Tampilan Mobile
Kartu responsif dengan informasi:
- Kode transaksi + badge status (Lunas/Kurang)
- Grid 2 kolom untuk detail:
  - Kasir
  - Metode Pembayaran
  - Total Item
  - Total Amount
  - Paid Amount
  - Change Amount
  - Waktu (HH:mm)
- Tombol "Detail" dengan icon arrow

#### Tampilan Desktop
Tabel dengan kolom:
1. **Kode**: Kode transaksi (bold)
2. **Kasir**: Nama kasir
3. **Pembayaran**: Nama metode pembayaran
4. **Status**: Badge Lunas (hijau) atau Kurang (merah)
5. **Item**: Jumlah item dalam badge
6. **Total**: Total amount (bold, format Rupiah)
7. **Dibayar**: Paid amount (format Rupiah)
8. **Kembalian**: Change amount (format Rupiah)
9. **Waktu**: Jam transaksi (HH:mm)
10. **Aksi**: Link "Detail"

#### Status Pembayaran
- **Lunas**: `paid_amount >= total_amount`
  - Badge hijau emerald
  - Icon: Check circle
- **Kurang**: `paid_amount < total_amount`
  - Badge merah
  - Icon: Alert circle

### 📄 Pagination

Custom pagination dengan:
- Info halaman: "Halaman X dari Y · Total Z transaksi"
- Tombol Previous & Next
- Previous disabled di halaman pertama
- Next disabled di halaman terakhir
- Next button dengan highlight biru

---

## Halaman Detail Transaksi

### 🎨 Header Section

**Breadcrumb Navigation**
```
Beranda / Riwayat / Detail
```

**Judul & Info**
- Judul: "Detail Transaksi" (font besar, bold)
- Kode transaksi (monospace, bold)
- Tanggal & waktu lengkap
- Bullet indicator biru

**Action Buttons**
- **Print**: Trigger window.print() untuk mencetak
- **Kembali**: Kembali ke list transaksi

### 💳 Info Cards (4 Kartu)

#### 1. Status Pembayaran
- **Lunas**:
  - Icon: Check circle (hijau)
  - Badge hijau dengan dot animasi pulse
- **Kurang Bayar**:
  - Icon: Alert circle (merah)
  - Badge merah dengan dot animasi pulse

#### 2. Kasir
- Icon: User (biru)
- Nama kasir (bold, besar)
- Username dengan prefix '@' (kecil, abu)

#### 3. Metode Pembayaran
- Icon: Credit card (ungu)
- Nama metode pembayaran

#### 4. Total Item
- Icon: Shopping bag (orange)
- Jumlah item dengan satuan "item"

### 🛍️ Daftar Item Pembelian

#### Tampilan Mobile
Kartu per item dengan:
- Nomor urut dalam badge
- Nama menu (bold)
- Badge quantity biru dengan format "{qty}x"
- Harga satuan
- Subtotal (kanan, bold, besar)

#### Tampilan Desktop
Tabel dengan kolom:
1. **#**: Nomor urut dalam badge
2. **Menu**: Nama menu (bold)
3. **Qty**: Quantity dalam badge biru (center)
4. **Harga Satuan**: Format Rupiah (right align)
5. **Subtotal**: Format Rupiah, bold (right align)

#### State Kosong
Jika tidak ada item:
- Icon: Box (abu-abu)
- Pesan: "Tidak ada item"

### 💰 Ringkasan Pembayaran

Card dengan informasi:
- **Subtotal**: Total amount
- **Dibayar**: Paid amount (biru)
- **Kembalian**: Change amount (hijau, bold, besar)

Semua dengan pembatas border antar baris.

### ℹ️ Informasi Transaksi

Card dengan detail:
- **Kode Transaksi**: Monospace, break-all
- **Waktu Transaksi**: 
  - Tanggal lengkap (format hari, tanggal bulan tahun)
  - Jam (biru, format HH:mm:ss)
- **Total Item**: Jumlah jenis item
- **Total Quantity**: Total semua quantity (Pcs)

### 🖨️ Fitur Print

#### Print Styles
- Custom CSS untuk print media
- Hide semua elemen kecuali #print-area
- Full width, padding 20px
- Hide semua button

#### Print Layout
**Header**
- Judul: "DETAIL TRANSAKSI"
- Kode transaksi
- Tanggal & waktu

**Info Transaksi**
Tabel dengan:
- Kasir
- Metode Pembayaran
- Status

**Tabel Item**
- Header dengan border bawah bold
- Kolom: #, Menu, Qty, Harga, Subtotal
- Body dengan border

**Ringkasan**
Tabel align right dengan:
- Subtotal
- Dibayar
- Kembalian (bold, besar, border atas)

**Footer**
- Ucapan terima kasih
- Timestamp cetak

---

## Fitur-Fitur

### ✨ Fitur Utama

1. **Multi-Filter**
   - Periode (preset + custom)
   - Kasir
   - Metode pembayaran
   - Search by kode/nama

2. **Real-time Statistics**
   - Auto-calculate berdasarkan filter
   - 4 metric utama
   - Responsive cards

3. **Export Data**
   - CSV format
   - UTF-8 encoding
   - Filename dinamis
   - Semua filter applied

4. **Print Receipt**
   - Print-friendly layout
   - Professional format
   - Auto timestamp

5. **Responsive Design**
   - Mobile-first approach
   - Adaptive cards/tables
   - Touch-friendly

6. **Dark Mode Support**
   - Kontras optimal untuk semua elemen
   - Stats cards dengan background solid
   - Border dan separator yang jelas
   - Color scheme konsisten

### 🎯 User Experience

1. **Auto-submit Filters**
   - Date picker
   - Dropdown kasir
   - Dropdown payment method

2. **Debounced Search**
   - 500ms delay
   - Prevent excessive requests

3. **Visual Feedback**
   - Hover effects
   - Active states
   - Loading indicators

4. **Accessibility**
   - Semantic HTML
   - ARIA labels
   - Keyboard navigation

---

## Teknologi

### Backend
- **Laravel**: Framework PHP
- **Eloquent ORM**: Database queries
- **Carbon**: Date manipulation
- **Cache**: Query optimization

### Frontend
- **Blade**: Template engine
- **Tailwind CSS**: Utility-first CSS
- **Alpine.js**: (optional, for interactivity)
- **Heroicons**: SVG icons

### Database
- **Relationships**:
  - Transaction belongsTo User (kasir)
  - Transaction belongsTo PaymentMethod
  - Transaction hasMany TransactionDetail
  - TransactionDetail belongsTo Menu

### Optimizations
1. **Eager Loading**:
   ```php
   ->with(['user:id,name,username', 'paymentMethod:id,name'])
   ```

2. **Select Specific Columns**:
   ```php
   ->select(['id', 'transaction_code', 'user_id', ...])
   ```

3. **Query Caching**:
   ```php
   Cache::remember('payment_methods:list', now()->addMinutes(2), ...)
   ```

4. **Pagination**:
   ```php
   ->paginate(10)->withQueryString()
   ```

---

## Tips Penggunaan

### Untuk Owner

1. **Monitoring Harian**
   - Gunakan preset "Harian" untuk quick check
   - Lihat kasir teraktif hari ini
   - Check total omzet real-time

2. **Analisis Mingguan/Bulanan**
   - Pilih preset sesuai kebutuhan
   - Export data untuk analisis lebih lanjut
   - Bandingkan performa kasir

3. **Pencarian Spesifik**
   - Gunakan search bar untuk kode transaksi
   - Filter by kasir untuk evaluasi individu
   - Filter by payment method untuk analisis metode pembayaran

4. **Export & Report**
   - Export CSV untuk backup data
   - Import ke Excel untuk pivot table
   - Buat laporan custom

### Untuk Developer

1. **Extend Filters**
   - Tambahkan filter di `applyFilters()` method
   - Update form di view
   - Maintain query string

2. **Add Statistics**
   - Calculate di controller
   - Pass ke view via compact
   - Display di stats cards

3. **Custom Export Format**
   - Modify `export()` method
   - Change CSV structure
   - Add PDF export (optional)

4. **Performance**
   - Monitor N+1 queries
   - Add indexes if needed
   - Cache static data

5. **Dark Mode Consistency**
   - Gunakan `dark:bg-slate-800` untuk cards (bukan `dark:bg-slate-900/40`)
   - Border gunakan `dark:border-slate-700` (bukan `dark:border-slate-800`)
   - Background header gunakan `dark:bg-slate-900/50`
   - Fix select dropdown dengan custom CSS untuk options
   - Fix date picker calendar icon dengan filter invert
   - Test kontras dengan toggle dark mode

6. **Responsive Mobile Layout**
   - Gunakan grid system untuk consistency
   - Grid 2 kolom untuk preset buttons
   - Full width untuk semua filter elements
   - Proper spacing dengan gap-3
   - Icon + label untuk better UX
</text>

<old_text line=443>
### Version 2.1 (Current - Dark Mode Fixed)
✅ **Perbaikan Dark Mode Lengkap**
  - Stats cards dengan background `dark:bg-slate-800` (solid, tidak transparan)
  - Border konsisten `dark:border-slate-700`
  - Filter bar & search bar dengan kontras optimal
  - Date picker dengan background yang jelas
  - Transaction groups dengan warna yang sesuai
  - Info cards di halaman detail dengan kontras baik
  - Semua elemen putih sudah mengikuti dark mode

---

## Changelog

### Version 2.3 (Current - Minimalist Edition)
✅ **Simplifikasi Filter Bar - Minimalis & Modern**
  - Hapus dropdown filter Kasir dan Metode Pembayaran
  - Layout horizontal yang clean di desktop
  - Preset + Date + Actions dalam satu row
  - Search bar full width di row terpisah
  - Kurangi 45% kode dari versi sebelumnya
  - Mobile layout lebih rapi dan tidak cramped
  - Fokus ke fitur utama: Periode & Search
  - Better white space dan visual hierarchy
  - Faster render dengan less DOM elements
  - Modern minimalist design principle

### Version 2.2 (Mobile Layout Perfect)
✅ **Perbaikan Mobile Layout & Dark Mode Complete**
  - Filter bar layout simetris di mobile (grid system)
  - Preset buttons: grid 2 kolom di mobile, inline di desktop
  - Date picker: full width dengan background konsisten
  - Search bar: full width dengan proper spacing
  - Filter dropdowns: grid 2 kolom dengan icons
  - Export & Reset buttons: grid 2 kolom di mobile
  - Custom CSS untuk select dropdown dark mode
  - Fix date picker calendar icon dengan filter invert
  - Semua elemen responsive dan accessible
  - Icon visual untuk setiap filter element
  - Consistent spacing dengan gap-3

### Version 2.1 (Dark Mode Fixed)
✅ **Perbaikan Dark Mode Lengkap**
  - Stats cards dengan background `dark:bg-slate-800` (solid, tidak transparan)
  - Border konsisten `dark:border-slate-700`
  - Filter bar & search bar dengan kontras optimal
  - Date picker dengan background yang jelas
  - Transaction groups dengan warna yang sesuai
  - Info cards di halaman detail dengan kontras baik
  - Semua elemen putih sudah mengikuti dark mode

### Version 2.0
✅ Redesign halaman index dengan modern UI
✅ Tambah 4 stats cards dengan hover effects
✅ Perbaiki filter system (preset + custom)
✅ Upgrade search dengan debounce
✅ Responsive mobile & desktop views
✅ Grouping transaksi by date
✅ Redesign halaman detail lengkap
✅ Tambah fitur print receipt
✅ Status pembayaran dengan badge animasi
✅ Info cards dengan icons
✅ Better typography & spacing
✅ Dark mode support (initial)
✅ Export CSV dengan proper encoding

### Version 1.0 (Previous)
- Basic transaction list
- Simple filters
- Basic detail view
- No export feature

---

## Mobile Layout Structure

### Filter Section (Responsive Grid)
```
Mobile View (< 640px):
┌────────────────────────────┐
│ [Harian]     [Mingguan]   │  Grid 2 cols
│ [Bulanan]    [Tahunan]    │
├────────────────────────────┤
│ 📅 dd/mm/yyyy — dd/mm/yyyy│  Full width
├────────────────────────────┤
│ 🔍 Search...               │  Full width
├────────────────────────────┤
│ 👤 Kasir: [Dropdown ▼]    │  Grid 2 cols
├────────────────────────────┤
│ 💳 Metode: [Dropdown ▼]   │  Grid 2 cols
├────────────────────────────┤
│ [Export]      [Reset]     │  Grid 2 cols
└────────────────────────────┘

Desktop View (>= 640px):
┌──────────────────────────────────────────┐
│ [Harian][Mingguan][Bulanan][Tahunan]    │
├──────────────────────────────────────────┤
│ 📅 dd/mm/yyyy — dd/mm/yyyy               │
├──────────────────────────────────────────┤
│ 🔍 Search...                             │
├──────────────────────────────────────────┤
│ 👤 Kasir: [▼]    💳 Metode: [▼]         │
├──────────────────────────────────────────┤
│ [Export]                        [Reset]  │
└──────────────────────────────────────────┘
```

### Custom Dark Mode Styles
```css
/* Select dropdown options dark mode */
.select-dark-mode option {
    background-color: rgb(30 41 59) !important;
    color: rgb(226 232 240) !important;
}

/* Date picker calendar icon dark mode */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.5);
}

.dark input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.8);
}
```

---

## Screenshots

### Desktop View
```
┌─────────────────────────────────────────────────────────────┐
│  Riwayat Transaksi                                 [Export] │
├─────────────────────────────────────────────────────────────┤
│  [Harian] [Mingguan] [Bulanan] [Tahunan]   📅 dd/mm - dd/mm │
│  🔍 Search...  [Kasir ▼]  [Metode ▼]                       │
├─────────────────────────────────────────────────────────────┤
│  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐                   │
│  │ 150  │  │ 2.5M │  │ 16K  │  │ Budi │                   │
│  │ trx  │  │ Omzet│  │Rata2 │  │Kasir │                   │
│  └──────┘  └──────┘  └──────┘  └──────┘                   │
├─────────────────────────────────────────────────────────────┤
│  17 Maret 2026                               (3 transaksi) │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ TRX-001 | Budi | Cash | ✓ | 5 | 50K | 50K | 0 | 10:30│ │
│  └───────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Mobile View
```
┌──────────────────────┐
│ Riwayat Transaksi    │
├──────────────────────┤
│ [Harian] [Mingguan] │
│ [Bulanan] [Tahunan] │
│ 📅 17/03 - 17/03     │
│ 🔍 Search...         │
├──────────────────────┤
│ ┌──────┐  ┌──────┐  │
│ │ 150  │  │ 2.5M │  │
│ └──────┘  └──────┘  │
│ ┌──────┐  ┌──────┐  │
│ │ 16K  │  │ Budi │  │
│ └──────┘  └──────┘  │
├──────────────────────┤
│ 17 Maret 2026 (3)   │
│ ┌──────────────────┐ │
│ │ TRX-001     [✓]  │ │
│ │ Kasir: Budi      │ │
│ │ Total: Rp 50,000 │ │
│ │ [Detail →]       │ │
│ └──────────────────┘ │
└──────────────────────┘
```

---

## Support & Kontribusi

Untuk pertanyaan atau issue, silakan hubungi tim development atau buat ticket di sistem issue tracker.

**Dibuat dengan ❤️ untuk Kebab SK**