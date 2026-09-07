# Kejar Target

SaaS sales tracker dan gamification untuk membantu Supervisor (SPV) mengelola tim sales, memvalidasi Sales Activity (SA), memantau target, leaderboard, dan menghitung komisi secara transparan.

## Stack

- Frontend: Next.js, TypeScript, Tailwind CSS, shadcn/ui, PWA
- Backend: Laravel REST API, Laravel Sanctum
- Database: PostgreSQL (Supabase)
- Arsitektur: Browser → Next.js → Laravel API → PostgreSQL/Supabase

## Struktur

```text
kejar-target/
├── backend/              # Laravel API
├── frontend/             # Next.js responsive/PWA
├── docs/                 # Product dan technical documentation
├── .editorconfig
├── .gitignore
├── docker-compose.yml
└── README.md
```

## Dokumen utama

Mulai dari [docs/README.md](docs/README.md). Urutan implementasi yang disarankan:

1. Product Brief dan PRD
2. Business Rules dan User Flows
3. ERD dan Architecture
4. API Contract
5. Backlog

## Menjalankan proyek

Folder `backend` dan `frontend` berisi petunjuk bootstrap. Framework belum di-vendor agar repo tetap ringan dan tidak mengunci versi sebelum instalasi.

## Prinsip penting

- Laravel adalah satu-satunya pemilik aturan bisnis dan akses data aplikasi.
- Semua data bisnis wajib terisolasi berdasarkan `company_id`.
- Hanya SA berstatus `validated` yang memengaruhi target, leaderboard, dan komisi.
- Nilai komisi yang sudah dihitung disimpan sebagai snapshot agar perubahan aturan tidak mengubah histori.
