# Product Requirements Document

## 1. Tujuan

Menyediakan aplikasi multi-tenant yang memungkinkan perusahaan melacak aktivitas penjualan, memvalidasi hasil, mengukur target, dan menghitung komisi secara akurat.

## 2. Role dan akses

| Fitur | Super Admin | SPV | Sales |
| --- | --- | --- | --- |
| Kelola perusahaan/subscription | Ya | Tidak | Tidak |
| Kelola tim dan sales | Pantau | Ya, perusahaan sendiri | Tidak |
| Atur target/produk/komisi | Tidak | Ya | Lihat |
| Input SA | Tidak | Opsional nanti | Ya |
| Validasi SA | Tidak | Ya, tim sendiri | Tidak |
| Dashboard/leaderboard | Global | Tim/perusahaan | Diri dan tim yang diizinkan |

## 3. Modul autentikasi dan tenant

- Pengguna login melalui Laravel Sanctum.
- Semua pengguna bisnis terhubung ke tepat satu perusahaan pada MVP.
- Backend menentukan tenant dari user terautentikasi, bukan dari input bebas klien.
- User nonaktif atau subscription tidak aktif tidak dapat melakukan operasi bisnis yang dilindungi.

## 4. Dashboard

### SPV

- Total penjualan tim, target, dan persentase capaian.
- Jumlah sales aktif.
- Grafik aktivitas 7 hari.
- Top performer.
- Jumlah SA menunggu validasi.

### Sales

- Target bulanan dan mingguan.
- Total SA tervalidasi dan pending.
- Estimasi komisi dan pendapatan.
- Posisi leaderboard.
- Tombol cepat tambah SA.

### Super Admin

- Jumlah perusahaan, user, subscription aktif/trial/expired.
- Daftar pembayaran dan status layanan.

## 5. Sales Activity (SA)

- Sales dapat membuat banyak SA selama masih dalam periode input yang diizinkan (default maksimal 2 hari dari tanggal aktivitas).
- Form minimal: produk, tanggal aktivitas, identitas/referensi pelanggan, catatan opsional, dan bukti opsional.
- Status: `draft`, `pending`, `validated`, `rejected`.
- Draft dapat diedit/dihapus oleh pembuat.
- Submit mengubah draft/rejected menjadi pending.
- SPV hanya dapat memvalidasi/reject SA milik timnya.
- Reject wajib memiliki alasan.
- Rejected dapat diperbaiki dan dikirim ulang.
- Validated terkunci dan tidak dapat dibatalkan pada MVP.
- Setiap transisi status dicatat.

## 6. Target

- SPV dapat menetapkan target bulanan dan mingguan per sales.
- Nilai target dapat berbeda antar-sales.
- Capaian hanya menghitung SA `validated` pada periode target.
- Target yang sudah berjalan tetap memiliki audit siapa dan kapan mengubahnya.

## 7. Komisi

Formula utama:

`total = (product_fee × multiplier) + progressive_incentive`

- Product Fee ditentukan per produk.
- Multiplier dapat dinonaktifkan; jika aktif dipilih berdasarkan jumlah SA tervalidasi.
- Progressive Incentive dapat dinonaktifkan; jika aktif dihitung berdasarkan urutan SA per produk dalam periode.
- Contoh tier progresif: SA #1 Rp50.000, SA #2 Rp50.000, SA #3 Rp100.000; total progresif untuk 3 SA = Rp200.000.
- Hasil disimpan sebagai snapshot per sales dan periode.

## 8. Leaderboard dan gamification

- Peringkat utama berdasarkan jumlah SA tervalidasi pada periode.
- Tie-breaker: nilai penjualan bila tersedia, lalu waktu tercapainya jumlah tersebut lebih dahulu.
- Filter periode: minggu berjalan dan bulan berjalan.
- MVP menggunakan ranking dan progress; achievement kompleks ditunda.

## 9. Subscription

- Paket awal: Starter dengan trial 3 hari.
- Status: `trialing`, `active`, `past_due`, `expired`, `cancelled`.
- Auto-charge merupakan target desain; payment gateway aktual berada di luar MVP awal.
- Saat trial/subscription berakhir, akses mutasi bisnis diblokir; data tetap aman dan dapat dilihat sesuai kebijakan produk.

## 10. Navigasi

- Public: Landing, Pricing, Register, Login, Checkout.
- SPV: Dashboard, Sales, Validasi, Leaderboard, Komisi, Pengaturan.
- Sales: Dashboard, Leaderboard, SA Saya, Pendapatan, Profil.
- Super Admin: Dashboard, Companies, Subscriptions, Payments.

## 11. Non-functional requirements

- API mengembalikan error terstruktur dan status HTTP yang tepat.
- Nilai uang disimpan sebagai integer rupiah atau decimal tetap, tidak menggunakan float.
- Timestamp disimpan UTC dan ditampilkan sesuai zona waktu perusahaan.
- Endpoint daftar mendukung pagination.
- Aksi sensitif memiliki audit log.
- Pengujian backend mencakup tenant isolation dan aturan komisi.

## 12. Acceptance criteria MVP

MVP dianggap siap ketika satu perusahaan dapat mendaftar, membuat tim dan sales, mengatur produk/target/komisi, sales mengirim SA, SPV memvalidasi, lalu dashboard, leaderboard, target, dan komisi memperlihatkan hasil yang konsisten tanpa kebocoran data antar-perusahaan.
