"use client";

import { useQuery } from "@tanstack/react-query";
import { Coins, Layers3, TrendingUp, WalletCards } from "lucide-react";
import { useState } from "react";

import { MetricCard } from "@/components/data-display/metric-card";
import { PageHeader } from "@/components/data-display/page-header";
import { ContentSkeleton, EmptyState, ErrorState } from "@/components/feedback/query-state";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { commissionsApi } from "@/lib/api/endpoints/commissions";
import { formatRupiah } from "@/lib/format";

const now=new Date().toISOString().slice(0,7);
export function EarningsView(){const [period,setPeriod]=useState(now);const query=useQuery({queryKey:["commissions",period,"mine"],queryFn:()=>commissionsApi.list({period})});if(query.isLoading)return <ContentSkeleton/>;if(query.error)return <ErrorState error={query.error} retry={()=>query.refetch()}/>;const item=query.data?.[0];return <div className="space-y-6"><PageHeader eyebrow="Pendapatan" title="Komisi saya" description="Snapshot perhitungan tidak berubah ketika supervisor membuat versi aturan baru." action={<Input className="w-44" type="month" value={period} onChange={e=>setPeriod(e.target.value)}/>} />{!item?<EmptyState title="Komisi belum dihitung" description="Estimasi tetap tersedia di dashboard; snapshot final muncul setelah kalkulasi supervisor."/>:<><div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><MetricCard icon={Coins} label="Fee produk" value={formatRupiah(item.product_fee_amount)}/><MetricCard icon={TrendingUp} label="Multiplier" value={`×${Number(item.multiplier_value).toLocaleString("id-ID")}`}/><MetricCard icon={Layers3} label="Insentif progresif" value={formatRupiah(item.progressive_incentive_amount)}/><MetricCard icon={WalletCards} label="Total komisi" value={formatRupiah(item.total_amount)} helper={`Versi ${item.calculation_version??1}`}/></div><Card><CardHeader><CardTitle>Transparansi formula</CardTitle></CardHeader><CardContent><p className="rounded-lg bg-secondary p-4 font-mono text-sm">({formatRupiah(item.product_fee_amount)} × {Number(item.multiplier_value).toLocaleString("id-ID")}) + {formatRupiah(item.progressive_incentive_amount)} = <strong>{formatRupiah(item.total_amount)}</strong></p><p className="mt-3 text-xs text-muted-foreground">Hanya aktivitas berstatus tervalidasi dalam periode {period} yang masuk ke snapshot.</p></CardContent></Card></>}</div>}
