# FineDining

# FineDining – Website Pemesanan Restoran Fancy

FineDining adalah website sistem reservasi dan pemesanan makanan untuk restoran berkonsep mewah (fancy dining). Aplikasi ini memungkinkan pengguna melakukan pemesanan tempat, memilih menu makanan, dan melakukan permintaan interior khusus secara langsung melalui platform digital yang elegan.

## 👥 Tim Pengembang
**Nama**: Muhammad Raihan Hidayah  
**Fakultas**: Fakultas Vokasi Universitas Brawijaya  
**NIM**: 243140701111053  
**Peran**:
- Front End Developer
- Back End Developer
- Database Designer

## 🎯 Tujuan dan Target Pengguna
Website ini dibuat untuk:
- Meningkatkan pengalaman pelanggan dalam memesan makanan dan tempat di restoran mewah
- Memudahkan admin, kasir, koki, dan pelayan dalam mengelola pemesanan

**Target Pengguna:**
- Customer
- Admin
- Chef (Koki)
- Waiter
- Kasir

## 🧭 User Flow
1. **Customer**: Registrasi → Login → Pilih Menu → Reservasi → Tambah Catatan Interior → Konfirmasi
2. **Admin**: Login → Kelola Data Reservasi dan Menu
3. **Chef**: Login → Lihat Pesanan Makanan
4. **Waiter**: Login → Pantau Pesanan → Layani Customer
5. **Kasir**: Login → Hitung Transaksi → Cetak Struk

## 🌐 Link Prototipe UI (Opsional)
_Sertakan link prototipe seperti Figma/AdobeXD jika ada._

## 🛠️ Cara Instalasi
1. Clone atau download repository ini
2. Jalankan server lokal (XAMPP/Laragon)
3. Import database `finedining.sql` melalui phpMyAdmin
4. Jalankan `index.php` dari browser melalui `http://localhost/FineDining/`

## 🧩 Struktur Folder Utama
```
FineDining/
│
├── assets/               # File gambar dan style
├── includes/config.php   # Koneksi database
├── index.php             # Halaman utama
├── login.php             # Halaman login
├── register.php          # Halaman registrasi
├── customer.php          # Dashboard customer
├── finedining.sql        # Struktur dan data awal database
└── ...
```

## 🗃️ Struktur Database (ERD Sederhana)
- **customer** ⟶ (1:n) **reservasi**
- **reservasi** ⟶ (1:n) **pesanan_makanan**
- **pesanan_makanan** ⟶ (n:1) **menu_makanan**

## 📸 Screenshots
Lihat pada dokumentasi laporan atau buka file `index.php`, `register.php`, dan `customer.php` melalui browser.

## 💬 Kontak
Untuk informasi lebih lanjut, silakan hubungi:
> Muhammad Raihan Hidayah – g3muhraihan.hidayah@email.com
