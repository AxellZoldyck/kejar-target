"use client";

import { useQuery } from "@tanstack/react-query";
import { CheckCircle2, Clock3, FilePenLine, Trophy, WalletCards, XCircle } from "lucide-react";
import Link from "next/link";

import { MetricCard } from "@/components/data-display/metric-card";
import { PageHeader } from "@/components/data-display/page-header";
import { ContentSkeleton, ErrorState } from "@/components/feedback/query-state";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useSession } from "@/features/auth/session-provider";
import { dashboardApi } from "@/lib/api/endpoints/dashboards";
import { formatNumber, formatPercent, formatRupiah } from "@/lib/format";

function Progress({ label, progress }: { label: string; progress: { target: number | null; realization: number; achievement_percent: number | null } | null }) {
  return <Card><CardHeader><CardTitle>{label}</CardTitle></CardHeader><CardContent>{progress ? <><div className="flex items-end justify-between"><p className="text-3xl font-black tabular-nums">{formatNumber(progress.realization)} <span className="text-base font-medium text-muted-foreground">/ {formatNumber(progress.target)}</span></p><strong className="text-primary">{formatPercent(progress.achievement_percent)}</strong></div><div className="mt-4 h-3 overflow-hidden rounded-full bg-secondary"><div className="h-full rounded-full bg-primary transition-all" style={{ width: `${Math.min(100, progress.achievement_percent ?? 0)}%` }} /></div></> : <p className="text-sm leading-6 text-muted-foreground">Target belum diatur oleh supervisor.</p>}</CardContent></Card>;
}

export function SalesDashboardView() {
  const session = useSession();
  const query = useQuery({ queryKey: ["sales-dashboard"], queryFn: () => dashboardApi.sales() });
  if (query.isLoading) return <ContentSkeleton cards={6} />;
  if (query.error || !query.data) return <ErrorState error={query.error} retry={() => query.refetch()} />;
  const data = query.data;
  const writable = session.subscription?.can_mutate ?? false;

  return <div className="space-y-6"><PageHeader eyebrow="Sales" title={`Halo, ${session.user.name.split(" ")[0]}`} description="Pantau progres Anda dan masukkan aktivitas selagi masih dalam jendela dua hari." action={writable?<Button asChild><Link href="/sales/sa-saya/baru">Tambah {session.company?.activity_label ?? "SA"}</Link></Button>:undefined} /><div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><MetricCard icon={FilePenLine} label="Draft" value={data.activity_counts.draft} /><MetricCard icon={Clock3} label="Menunggu" value={data.activity_counts.pending} /><MetricCard icon={CheckCircle2} label="Tervalidasi" value={data.activity_counts.validated} /><MetricCard icon={XCircle} label="Ditolak" value={data.activity_counts.rejected} /></div><div className="grid gap-4 lg:grid-cols-2"><Progress label="Target minggu ini" progress={data.weekly_target} /><Progress label="Target bulan ini" progress={data.monthly_target} /></div><div className="grid gap-4 sm:grid-cols-2"><MetricCard icon={Trophy} label="Peringkat bulan ini" value={data.leaderboard_rank ? `#${data.leaderboard_rank}` : "—"} helper="Berdasarkan aktivitas tervalidasi" /><MetricCard icon={WalletCards} label="Estimasi komisi" value={formatRupiah(data.commission_estimate)} helper="Nilai final mengikuti kalkulasi supervisor" /></div></div>;
}
