"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Eye, Plus, Send, Trash2 } from "lucide-react";
import Link from "next/link";
import { useState } from "react";
import { toast } from "sonner";

import { ActivityStatusBadge } from "@/components/data-display/status-badge";
import { PageHeader } from "@/components/data-display/page-header";
import { ContentSkeleton, EmptyState, ErrorState } from "@/components/feedback/query-state";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Select } from "@/components/ui/select";
import { useSession } from "@/features/auth/session-provider";
import { activitiesApi } from "@/lib/api/endpoints/activities";
import { isApiError } from "@/lib/api/api-error";
import { formatDate } from "@/lib/format";
import type { ActivityStatus } from "@/types/domain";

export function MyActivitiesView() {
  const session = useSession();
  const queryClient = useQueryClient();
  const [status, setStatus] = useState<ActivityStatus | "">("");
  const query = useQuery({ queryKey: ["activities", status], queryFn: () => activitiesApi.list({ status }) });
  const mutate = useMutation({
    mutationFn: async ({ action, id }: { action: "submit" | "delete"; id: string }) => {
      if (action === "submit") await activitiesApi.submit(id);
      else await activitiesApi.remove(id);
    },
    onSuccess: (_, variables) => { toast.success(variables.action === "submit" ? "Aktivitas diajukan." : "Draft dihapus."); queryClient.invalidateQueries({ queryKey: ["activities"] }); queryClient.invalidateQueries({ queryKey: ["sales-dashboard"] }); },
    onError: (error) => toast.error(isApiError(error) ? error.message : "Tindakan gagal."),
  });
  const writable = session.subscription?.can_mutate ?? false;

  return <div className="space-y-6"><PageHeader eyebrow="Aktivitas" title={`${session.company?.activity_label ?? "SA"} Saya`} description="Simpan sebagai draft, lengkapi bukti, lalu ajukan untuk diperiksa supervisor." action={<Button asChild={writable} disabled={!writable}>{writable ? <Link href="/sales/sa-saya/baru"><Plus />Tambah aktivitas</Link> : <><Plus />Tambah aktivitas</>}</Button>} /><div className="flex max-w-xs items-center gap-3"><Select aria-label="Filter status" value={status} onChange={(event)=>setStatus(event.target.value as ActivityStatus|"")}><option value="">Semua status</option><option value="draft">Draft</option><option value="pending">Menunggu</option><option value="validated">Tervalidasi</option><option value="rejected">Ditolak</option></Select></div>{query.isLoading ? <ContentSkeleton /> : query.error ? <ErrorState error={query.error} retry={()=>query.refetch()} /> : !query.data?.length ? <EmptyState title="Belum ada aktivitas" description="Catat hasil kerja Anda agar progres target dapat divalidasi." action={writable?<Button asChild><Link href="/sales/sa-saya/baru">Buat aktivitas pertama</Link></Button>:undefined} /> : <div className="grid gap-3">{query.data.map(item=><Card className="p-4 sm:p-5" key={item.id}><div className="flex flex-col gap-4 sm:flex-row sm:items-center"><div className="min-w-0 flex-1"><div className="flex flex-wrap items-center gap-2"><h2 className="font-bold">{item.product.name}</h2><ActivityStatusBadge status={item.status} /></div><p className="mt-1 truncate text-sm text-muted-foreground">{item.customer_reference} · {formatDate(item.activity_date, session.company?.timezone)}</p>{item.rejection_reason && <p className="mt-2 text-sm text-destructive">Alasan: {item.rejection_reason}</p>}</div><div className="flex flex-wrap gap-2"><Button asChild size="sm" variant="outline"><Link href={`/sales/sa-saya/${item.id}`}><Eye />Detail</Link></Button>{writable && (item.status==="draft"||item.status==="rejected") && <Button size="sm" onClick={()=>mutate.mutate({action:"submit",id:item.id})} disabled={mutate.isPending}><Send />Ajukan</Button>}{writable && item.status==="draft" && <Button size="sm" variant="destructive" onClick={()=>{if(window.confirm("Hapus draft ini?"))mutate.mutate({action:"delete",id:item.id})}} disabled={mutate.isPending}><Trash2 />Hapus</Button>}</div></div></Card>)}</div>}</div>;
}
