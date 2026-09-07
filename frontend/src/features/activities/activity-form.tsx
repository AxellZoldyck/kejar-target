"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { LoaderCircle, Save } from "lucide-react";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { toast } from "sonner";

import { PageHeader } from "@/components/data-display/page-header";
import { ContentSkeleton, ErrorState } from "@/components/feedback/query-state";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { useSession } from "@/features/auth/session-provider";
import { activitiesApi } from "@/lib/api/endpoints/activities";
import { organizationApi } from "@/lib/api/endpoints/organization";
import { isApiError } from "@/lib/api/api-error";
import type { Product, SalesActivity } from "@/types/domain";

interface FormState { product_id: string; activity_date: string; customer_reference: string; notes: string; evidence: File | null }

export function ActivityForm({ activityId }: { activityId?: string }) {
  const products = useQuery({ queryKey: ["products", "active"], queryFn: () => organizationApi.products(true) });
  const activity = useQuery({ queryKey: ["activity", activityId], queryFn: () => activitiesApi.show(activityId!), enabled: Boolean(activityId) });
  if (products.isLoading || (activityId && activity.isLoading)) return <ContentSkeleton cards={2} />;
  if (products.error || activity.error) return <ErrorState error={products.error ?? activity.error} retry={() => { products.refetch(); activity.refetch(); }} />;
  return <ActivityEditor key={activity.data?.updated_at ?? "new"} activityId={activityId} products={products.data ?? []} existing={activity.data} />;
}

function ActivityEditor({ activityId, products, existing }: { activityId?: string; products: Product[]; existing?: SalesActivity }) {
  const session = useSession();
  const router = useRouter();
  const queryClient = useQueryClient();
  const [form, setForm] = useState<FormState>(() => existing ? {
    product_id: existing.product_id,
    activity_date: existing.activity_date,
    customer_reference: existing.customer_reference,
    notes: existing.notes ?? "",
    evidence: null,
  } : {
    product_id: "",
    activity_date: new Date().toISOString().slice(0, 10),
    customer_reference: "",
    notes: "",
    evidence: null,
  });
  const [error, setError] = useState<string | null>(null);
  const mutation = useMutation({
    mutationFn: async () => {
      const body = new FormData();
      body.set("product_id", form.product_id);
      body.set("activity_date", form.activity_date);
      body.set("customer_reference", form.customer_reference);
      body.set("notes", form.notes);
      if (form.evidence) body.set("evidence", form.evidence);
      return activityId ? activitiesApi.update(activityId, body) : activitiesApi.create(body);
    },
    onSuccess: (result) => {
      toast.success(activityId ? "Aktivitas diperbarui." : "Draft berhasil disimpan.");
      queryClient.invalidateQueries({ queryKey: ["activities"] });
      router.push(`/sales/sa-saya/${result.id}`);
    },
    onError: (caught) => setError(isApiError(caught) ? caught.message : "Aktivitas gagal disimpan."),
  });
  const editable = !existing || existing.status === "draft" || existing.status === "rejected";
  const writable = session.subscription?.can_mutate ?? false;
  const submit = (event: React.FormEvent) => {
    event.preventDefault(); setError(null);
    if (!form.product_id || !form.activity_date || !form.customer_reference.trim()) { setError("Produk, tanggal, dan referensi pelanggan wajib diisi."); return; }
    mutation.mutate();
  };

  return <div className="space-y-6"><PageHeader eyebrow="Aktivitas sales" title={activityId ? "Edit aktivitas" : `Tambah ${session.company?.activity_label ?? "SA"}`} description="Aktivitas hanya dapat dicatat sampai akhir hari kedua setelah tanggal aktivitas." /><Card className="max-w-2xl"><CardContent className="pt-5"><form className="space-y-5" onSubmit={submit}>{error && <Alert className="border-destructive/20 bg-destructive/5 text-destructive">{error}</Alert>}{!writable && <Alert>Langganan berada dalam mode baca-saja. Form tidak dapat disimpan.</Alert>}{!editable && <Alert>Status aktivitas ini sudah dikunci dan tidak dapat diedit.</Alert>}<div className="space-y-2"><Label htmlFor="product">Produk</Label><Select id="product" value={form.product_id} disabled={!editable || !writable} onChange={e => setForm({ ...form, product_id: e.target.value })}><option value="">Pilih produk</option>{products.map(product => <option value={product.id} key={product.id}>{product.name} ({product.code})</option>)}</Select></div><div className="space-y-2"><Label htmlFor="date">Tanggal aktivitas</Label><Input id="date" type="date" max={new Date().toISOString().slice(0, 10)} value={form.activity_date} disabled={!editable || !writable} onChange={e => setForm({ ...form, activity_date: e.target.value })} /></div><div className="space-y-2"><Label htmlFor="customer">Referensi pelanggan</Label><Input id="customer" maxLength={255} placeholder="Nama pelanggan / nomor order" value={form.customer_reference} disabled={!editable || !writable} onChange={e => setForm({ ...form, customer_reference: e.target.value })} /></div><div className="space-y-2"><Label htmlFor="notes">Catatan</Label><Textarea id="notes" maxLength={5000} placeholder="Informasi tambahan (opsional)" value={form.notes} disabled={!editable || !writable} onChange={e => setForm({ ...form, notes: e.target.value })} /></div><div className="space-y-2"><Label htmlFor="evidence">Bukti (JPG, PNG, atau PDF; maksimal 5 MB)</Label><Input id="evidence" type="file" accept="image/jpeg,image/png,application/pdf" disabled={!editable || !writable} onChange={e => setForm({ ...form, evidence: e.target.files?.[0] ?? null })} />{existing?.has_evidence && <p className="text-xs text-muted-foreground">Bukti saat ini dipertahankan jika tidak memilih file baru.</p>}</div><div className="flex gap-3"><Button type="button" variant="outline" onClick={() => router.back()}>Batal</Button><Button type="submit" disabled={!writable || !editable || mutation.isPending}>{mutation.isPending ? <LoaderCircle className="animate-spin" /> : <Save />}Simpan draft</Button></div></form></CardContent></Card></div>;
}
