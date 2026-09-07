"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ExternalLink, Pencil, Send } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { toast } from "sonner";

import { PageHeader } from "@/components/data-display/page-header";
import { ActivityStatusBadge } from "@/components/data-display/status-badge";
import { ContentSkeleton, ErrorState } from "@/components/feedback/query-state";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useSession } from "@/features/auth/session-provider";
import { activitiesApi } from "@/lib/api/endpoints/activities";
import { isApiError } from "@/lib/api/api-error";
import { formatDate } from "@/lib/format";

export function ActivityDetail({ id }: { id: string }) {
  const session = useSession(); const router = useRouter(); const queryClient = useQueryClient();
  const activity = useQuery({queryKey:["activity",id],queryFn:()=>activitiesApi.show(id)});
  const history = useQuery({queryKey:["activity-history",id],queryFn:()=>activitiesApi.history(id)});
  const submit = useMutation({mutationFn:()=>activitiesApi.submit(id),onSuccess:()=>{toast.success("Aktivitas diajukan.");queryClient.invalidateQueries({queryKey:["activity",id]});queryClient.invalidateQueries({queryKey:["activities"]});},onError:(error)=>toast.error(isApiError(error)?error.message:"Gagal mengajukan aktivitas.")});
  if(activity.isLoading)return <ContentSkeleton />; if(activity.error||!activity.data)return <ErrorState error={activity.error} retry={()=>activity.refetch()} />;
  const item=activity.data; const editable=(item.status==="draft"||item.status==="rejected")&&(session.subscription?.can_mutate??false);
  return <div className="space-y-6"><PageHeader eyebrow="Detail aktivitas" title={item.product.name} description={`${item.customer_reference} · ${formatDate(item.activity_date,session.company?.timezone)}`} action={<div className="flex gap-2">{editable&&<Button asChild variant="outline"><Link href={`/sales/sa-saya/${id}/edit`}><Pencil/>Edit</Link></Button>}{editable&&<Button onClick={()=>submit.mutate()} disabled={submit.isPending}><Send/>Ajukan</Button>}</div>} /><div className="grid gap-4 lg:grid-cols-[1.2fr_.8fr]"><Card><CardHeader><div className="flex items-center justify-between"><CardTitle>Informasi</CardTitle><ActivityStatusBadge status={item.status}/></div></CardHeader><CardContent><dl className="grid gap-5 text-sm sm:grid-cols-2"><div><dt className="text-muted-foreground">Produk</dt><dd className="mt-1 font-semibold">{item.product.name} {item.product.code&&`(${item.product.code})`}</dd></div><div><dt className="text-muted-foreground">Tanggal</dt><dd className="mt-1 font-semibold">{formatDate(item.activity_date,session.company?.timezone)}</dd></div><div className="sm:col-span-2"><dt className="text-muted-foreground">Catatan</dt><dd className="mt-1 whitespace-pre-wrap">{item.notes||"—"}</dd></div>{item.rejection_reason&&<div className="rounded-lg bg-destructive/5 p-3 text-destructive sm:col-span-2"><dt className="font-semibold">Alasan penolakan</dt><dd className="mt-1">{item.rejection_reason}</dd></div>}</dl>{item.evidence_url&&<Button asChild className="mt-5" variant="outline"><a href={item.evidence_url} target="_blank" rel="noreferrer"><ExternalLink/>Buka bukti</a></Button>}</CardContent></Card><Card><CardHeader><CardTitle>Riwayat status</CardTitle></CardHeader><CardContent className="space-y-4">{history.isLoading?<p className="text-sm text-muted-foreground">Memuat…</p>:history.data?.map(entry=><div className="relative border-l-2 border-primary/20 pl-4" key={entry.id}><p className="text-sm font-semibold capitalize">{entry.to_status}</p><p className="text-xs text-muted-foreground">{entry.actor?.name??"Sistem"} · {formatDate(entry.created_at,session.company?.timezone)}</p>{entry.reason&&<p className="mt-1 text-sm">{entry.reason}</p>}</div>)}</CardContent></Card></div><Button variant="ghost" onClick={()=>router.push("/sales/sa-saya")}>← Kembali ke daftar</Button></div>;
}
