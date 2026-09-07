"use client";

import { useQuery } from "@tanstack/react-query";
import { Award, CheckCircle2, Clock3, Target, Users } from "lucide-react";
import Link from "next/link";
import { useState } from "react";

import { MetricCard } from "@/components/data-display/metric-card";
import { PageHeader } from "@/components/data-display/page-header";
import { ContentSkeleton, ErrorState } from "@/components/feedback/query-state";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { dashboardApi } from "@/lib/api/endpoints/dashboards";
import { formatNumber, formatPercent } from "@/lib/format";

const currentPeriod = new Date().toISOString().slice(0, 7);

export function SpvDashboardView() {
  const [period, setPeriod] = useState(currentPeriod);
  const query = useQuery({ queryKey: ["spv-dashboard", period], queryFn: () => dashboardApi.spv(period) });
  if (query.isLoading) return <ContentSkeleton cards={6} />;
  if (query.error || !query.data) return <ErrorState error={query.error} retry={() => query.refetch()} />;
  const data = query.data;
  const chartMax = Math.max(...data.activity_last_7_days.map((item) => item.count), 1);

  return <div className="space-y-6"><PageHeader eyebrow="Supervisor" title="Ringkasan performa tim" description="Seluruh angka pencapaian berasal dari aktivitas yang sudah tervalidasi." action={<div className="flex gap-2"><Input className="w-40" type="month" value={period} onChange={(event) => setPeriod(event.target.value)} /><Button asChild><Link href="/spv/validasi">Periksa antrean</Link></Button></div>} /><div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><MetricCard icon={CheckCircle2} label="Aktivitas tervalidasi" value={formatNumber(data.total_sales)} helper="Periode terpilih" /><MetricCard icon={Target} label="Pencapaian target" value={formatPercent(data.achievement_percent)} helper={data.target ? `Target ${formatNumber(data.target)}` : "Target belum diatur"} /><MetricCard icon={Users} label="Sales aktif" value={formatNumber(data.active_sales)} /><MetricCard icon={Clock3} label="Menunggu validasi" value={formatNumber(data.pending_validation)} helper="Perlu tindakan supervisor" /></div><div className="grid gap-4 lg:grid-cols-[1.4fr_1fr]"><Card><CardHeader><CardTitle>Aktivitas tervalidasi 7 hari terakhir</CardTitle></CardHeader><CardContent><div className="flex h-56 items-end gap-3">{data.activity_last_7_days.map((item) => <div className="flex h-full flex-1 flex-col justify-end text-center" key={item.date}><span className="mb-2 text-xs font-bold tabular-nums">{item.count}</span><div className="min-h-1 rounded-t-lg bg-primary" style={{ height: `${Math.max(5, (item.count / chartMax) * 78)}%` }} /><span className="mt-2 text-[10px] text-muted-foreground">{new Date(`${item.date}T00:00:00`).toLocaleDateString("id-ID", { weekday: "short" })}</span></div>)}</div></CardContent></Card><Card><CardHeader><CardTitle className="flex items-center gap-2"><Award className="size-5 text-primary" />Top performer</CardTitle></CardHeader><CardContent className="space-y-3">{data.top_performers.length ? data.top_performers.map((item, index) => <div className="flex items-center gap-3 rounded-lg border p-3" key={item.sales_id}><span className="grid size-8 place-items-center rounded-full bg-primary/10 text-sm font-bold text-primary">{index + 1}</span><div className="min-w-0 flex-1"><p className="truncate text-sm font-semibold">{item.sales_name}</p><p className="text-xs text-muted-foreground">{item.validated_count} tervalidasi</p></div></div>) : <p className="text-sm text-muted-foreground">Belum ada aktivitas tervalidasi.</p>}</CardContent></Card></div></div>;
}
