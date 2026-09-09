# Backlog MVP

## Definition of Done umum

- Acceptance criteria terpenuhi.
- Authorization dan tenant scope diuji.
- Validation/error state tersedia.
- Migration dan API contract diperbarui.
- Test utama lulus.

## Fase 0 — Fondasi

- [ ] Bootstrap Laravel API dan Next.js TypeScript.
- [ ] Konfigurasi PostgreSQL/Supabase dan environment examples.
- [ ] Lint, formatter, test runner, CI dasar.
- [ ] Health endpoint dan API versioning.

## Fase 1 — Auth, company, dan role

- [ ] Company, user, role, active status.
- [ ] Register company + SPV + trial 3 hari.
- [ ] Login/logout/me dengan Sanctum.
- [ ] Tenant scope dan policy test.
- [ ] Route layout `/admin`, `/spv`, `/sales`.

## Fase 2 — Team, Sales, dan Product

- [ ] CRUD team dan membership.
- [ ] CRUD/invite Sales.
- [ ] CRUD Product dan Product Fee.
- [ ] Pengaturan label SA.

## Fase 3 — Sales Activity

- [ ] Migration/model SA dan status history.
- [ ] Create/edit/delete draft.
- [ ] Submit dan batas input 2 hari.
- [ ] Queue validasi SPV.
- [ ] Validate/reject dengan alasan dan transaksi.
- [ ] UI mobile-first Sales dan detail SPV.

## Fase 4 — Target dan dashboard

- [ ] Target weekly/monthly per sales.
- [ ] Capaian berbasis validated SA.
- [ ] Dashboard SPV: KPI, 7 hari, top performer, pending.
- [ ] Dashboard Sales: target, status SA, ranking, estimasi komisi.

## Fase 5 — Komisi

- [ ] Commission settings/versioning.
- [ ] Product Fee rules.
- [ ] Multiplier rules dan validasi overlap.
- [ ] Progressive rules berdasarkan rentang SA global lintas produk.
- [ ] CommissionService idempotent dan snapshot.
- [ ] Breakdown komisi serta test boundary.

## Fase 6 — Leaderboard dan gamification ringan

- [ ] Ranking weekly/monthly.
- [ ] Tie-breaker konsisten.
- [ ] Progress bar, posisi, dan top performer.
- [ ] Empty/loading/error states.

## Fase 7 — Subscription dan Super Admin

- [ ] Subscription state dan read-only gate.
- [ ] Admin Companies, Subscriptions, Payments.
- [ ] Checkout placeholder/manual activation MVP.
- [ ] Webhook-ready payment design.

## Fase 8 — Hardening dan release

- [ ] Audit logs.
- [ ] Rate limiting, upload validation, CORS.
- [ ] Feature/integration test kritis.
- [ ] PWA manifest/icons/service worker.
- [ ] Seed demo, monitoring, backup, deployment guide.
- [ ] UAT satu company: setup → SA → validate → target/rank/commission.

## Setelah MVP

- [ ] Payment gateway dan auto-charge nyata.
- [ ] Achievement, XP, missions, streak.
- [ ] Export/reporting lanjutan.
- [ ] Notification WhatsApp/email/push.
- [ ] Multi-team supervisor dan permission granular.
- [ ] White-label dan integrasi CRM.
