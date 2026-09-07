# API Contract — Draft v1

Base path: `/api/v1`

## Format response

Berhasil:

```json
{"data": {}, "meta": {}}
```

Gagal:

```json
{
  "message": "Validation failed",
  "code": "VALIDATION_ERROR",
  "errors": {"field": ["Pesan kesalahan"]}
}
```

## Auth

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/auth/register` | Buat company + SPV + trial |
| POST | `/auth/login` | Login |
| POST | `/auth/logout` | Logout |
| GET | `/me` | Profil dan permission aktif |

## Dashboard

| Method | Endpoint | Role |
| --- | --- | --- |
| GET | `/spv/dashboard?period=` | SPV |
| GET | `/sales/dashboard?period=` | Sales |
| GET | `/admin/dashboard` | Super Admin |

## Teams dan users

| Method | Endpoint | Role |
| --- | --- | --- |
| GET/POST | `/teams` | SPV |
| GET/PATCH | `/teams/{team}` | SPV |
| GET/POST | `/sales` | SPV |
| GET/PATCH | `/sales/{sales}` | SPV |
| POST | `/teams/{team}/members` | SPV |

## Products

| Method | Endpoint | Role |
| --- | --- | --- |
| GET | `/products` | SPV, Sales |
| POST | `/products` | SPV |
| PATCH | `/products/{product}` | SPV |

## Sales Activities

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/sales-activities` | Scoped sesuai role |
| POST | `/sales-activities` | Buat draft |
| GET | `/sales-activities/{activity}` | Detail |
| PATCH | `/sales-activities/{activity}` | Edit draft/rejected milik sendiri |
| DELETE | `/sales-activities/{activity}` | Hapus draft |
| POST | `/sales-activities/{activity}/submit` | Draft/rejected → pending |
| POST | `/sales-activities/{activity}/validate` | Pending → validated; SPV |
| POST | `/sales-activities/{activity}/reject` | Pending → rejected; reason wajib |
| GET | `/sales-activities/{activity}/history` | Riwayat status |

Contoh reject:

```json
{"reason": "Bukti pemasangan belum lengkap"}
```

## Targets

| Method | Endpoint | Role |
| --- | --- | --- |
| GET | `/targets?period=&sales_id=` | SPV/Sales scoped |
| POST | `/targets` | SPV |
| PATCH | `/targets/{target}` | SPV |

## Commission

| Method | Endpoint | Role |
| --- | --- | --- |
| GET/PUT | `/commission-settings` | SPV |
| GET/PUT | `/commission-product-fees` | SPV |
| GET/PUT | `/commission-multiplier-rules` | SPV |
| GET/PUT | `/commission-progressive-rules` | SPV |
| POST | `/commissions/calculate` | SPV/system |
| GET | `/commissions?period=&sales_id=` | Scoped |
| GET | `/commissions/{commission}` | Breakdown |

## Leaderboard

`GET /leaderboard?period=2026-09&scope=company|team`

Item minimal:

```json
{
  "rank": 1,
  "sales_id": "uuid",
  "sales_name": "Nama Sales",
  "validated_count": 12,
  "target": 10,
  "achievement_percent": 120
}
```

## Subscription dan admin

| Method | Endpoint | Role |
| --- | --- | --- |
| GET | `/subscription` | SPV |
| POST | `/subscription/checkout` | SPV |
| GET | `/admin/companies` | Super Admin |
| GET/PATCH | `/admin/companies/{company}` | Super Admin |
| GET | `/admin/subscriptions` | Super Admin |
| GET | `/admin/payments` | Super Admin |

## HTTP dan concurrency

- `200/201/204` sukses.
- `401` belum login; `403` tidak berhak; `404` resource tidak ditemukan dalam scope tenant.
- `409` konflik status/concurrency, misalnya SA sudah diproses.
- Validasi menggunakan `422`.
- Endpoint transition SA wajib menggunakan transaksi dan row lock/optimistic guard.
- Endpoint kalkulasi dapat menerima `Idempotency-Key`.
