"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Check, ExternalLink, X } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

import { PageHeader } from "@/components/data-display/page-header";
import { ContentSkeleton, EmptyState, ErrorState } from "@/components/feedback/query-state";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import { activitiesApi } from "@/lib/api/endpoints/activities";
import { isApiError } from "@/lib/api/api-error";
import { formatDate } from "@/lib/format";

export function ValidationQueue() {
  const client=useQueryClient(); const [rejecting,setRejecting]=useState<string|null>(null); const [reason,setReason]=useState("");
  const query=useQuery({queryKey:["activities","pending"],queryFn:()=>activitiesApi.list({status:"pending"})});
  const mutation=useMutation({mutationFn:({id,action}:{id:string;action:"validate"|"reject"})=>action==="validate"?activitiesApi.validate(id):activitiesApi.reject(id,reason),onSuccess:(_,vars)=>{toast.success(vars.action==="validate"?"Aktivitas divalidasi.":"Aktivitas ditolak.");setRejecting(null);setReason("");client.invalidateQueries({queryKey:["activities"]});client.invalidateQueries({queryKey:["spv-dashboard"]});client.invalidateQueries({queryKey:["leaderboard"]});},onError:error=>toast.error(isApiError(error)?error.message:"Tindakan gagal.")});
  return <div className="space-y-6"><PageHeader eyebrow="Supervisor" title="Antrean validasi" description="Periksa bukti dan konteks aktivitas sebelum menyetujui. Keputusan disimpan ke riwayat audit." />{query.isLoading?<ContentSkeleton/>:query.error?<ErrorState error={query.error} retry={()=>query.refetch()}/>:!query.data?.length?<EmptyState title="Antrean bersih" description="Tidak ada aktivitas yang menunggu validasi."/>:<div className="grid gap-4">{query.data.map(item=><Card className="p-5" key={item.id}><div className="flex flex-col gap-4 lg:flex-row lg:items-start"><div className="min-w-0 flex-1"><div className="flex flex-wrap gap-x-3 gap-y-1"><h2 className="font-bold">{item.product.name}</h2><span className="text-sm text-muted-foreground">oleh {item.sales?.name}</span></div><p className="mt-1 text-sm">{item.customer_reference} · {formatDate(item.activity_date)}</p>{item.notes&&<p className="mt-3 whitespace-pre-wrap rounded-lg bg-secondary/60 p-3 text-sm text-muted-foreground">{item.notes}</p>}{item.evidence_url&&<Button asChild size="sm" variant="link" className="mt-2 px-0"><a href={item.evidence_url} target="_blank" rel="noreferrer"><ExternalLink/>Periksa bukti</a></Button>}</div><div className="flex shrink-0 gap-2"><Button variant="outline" onClick={()=>{setRejecting(item.id);setReason("")}}><X/>Tolak</Button><Button onClick={()=>mutation.mutate({id:item.id,action:"validate"})} disabled={mutation.isPending}><Check/>Validasi</Button></div></div>{rejecting===item.id&&<div className="mt-4 border-t pt-4"><label className="text-sm font-semibold" htmlFor={`reason-${item.id}`}>Alasan penolakan</label><Textarea id={`reason-${item.id}`} className="mt-2" value={reason} onChange={e=>setReason(e.target.value)} placeholder="Jelaskan perbaikan yang diperlukan…"/><div className="mt-3 flex justify-end gap-2"><Button variant="ghost" onClick={()=>setRejecting(null)}>Batal</Button><Button variant="destructive" disabled={!reason.trim()||mutation.isPending} onClick={()=>mutation.mutate({id:item.id,action:"reject"})}>Konfirmasi tolak</Button></div></div>}</Card>)}</div>}</div>;
}
