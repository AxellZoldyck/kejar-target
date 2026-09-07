"use client";

import { useQuery } from "@tanstack/react-query";
import { Building2, CircleDollarSign, CreditCard, Users } from "lucide-react";

import { MetricCard } from "@/components/data-display/metric-card";
import { PageHeader } from "@/components/data-display/page-header";
import { ContentSkeleton, ErrorState } from "@/components/feedback/query-state";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { dashboardApi } from "@/lib/api/endpoints/dashboards";
import { formatNumber, formatRupiah } from "@/lib/format";

export function AdminDashboardView() {
  const query = useQuery({ queryKey: ["admin-dashboard"], queryFn: dashboardApi.admin });
  if (query.isLoading) return <ContentSkeleton cards={4} />;
  if (query.error || !query.data) return <ErrorState error={query.error} retry={() => query.refetch()} />;
  const data = query.data;
  return <div className="space-y-6"><PageHeader eyebrow="Platform" title="Dashboard super admin" description="Ikhtisar tenant, pengguna, langganan, dan pembayaran seluruh platform." /><div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><MetricCard icon={Building2} label="Perusahaan" value={formatNumber(data.companies_total)} /><MetricCard icon={Users} label="Pengguna" value={formatNumber(data.users_total)} /><MetricCard icon={CreditCard} label="Langganan aktif" value={formatNumber(data.subscriptions.active)} helper={`${data.subscriptions.trialing} trial`} /><MetricCard icon={CircleDollarSign} label="Total pembayaran" value={formatRupiah(data.payments_total)} /></div><Card><CardHeader><CardTitle>Distribusi status langganan</CardTitle></CardHeader><CardContent><div className="grid gap-3 sm:grid-cols-5">{Object.entries(data.subscriptions).map(([status,count])=><div className="rounded-lg border p-4" key={status}><p className="text-xs font-semibold uppercase text-muted-foreground">{status.replace("_"," ")}</p><p className="mt-2 text-2xl font-black tabular-nums">{count}</p></div>)}</div></CardContent></Card></div>;
}
