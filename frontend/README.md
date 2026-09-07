# Frontend — Next.js PWA

Target frontend Next.js + TypeScript + Tailwind + shadcn/ui.

## Bootstrap

Jika folder belum berisi aplikasi framework, buat Next.js di folder sementara lalu pindahkan hasilnya ke sini:

```bash
npx create-next-app@latest frontend-tmp --typescript --tailwind --eslint --app --src-dir --import-alias "@/*"
```

## Route target

- `(public)`: landing, pricing, register, login, checkout
- `admin`: dashboard, companies, subscriptions, payments
- `spv`: dashboard, sales, validasi, leaderboard, komisi, pengaturan
- `sales`: dashboard, leaderboard, SA Saya, pendapatan, profil

Sales wajib dirancang mobile-first. Route guard frontend hanya untuk UX; backend tetap memverifikasi semua permission.
