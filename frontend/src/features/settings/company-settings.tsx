"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { LoaderCircle, Save } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

import { ContentSkeleton, ErrorState } from "@/components/feedback/query-state";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select } from "@/components/ui/select";
import { organizationApi } from "@/lib/api/endpoints/organization";
import { isApiError } from "@/lib/api/api-error";
import type { Company } from "@/types/domain";

export function CompanySettings({ writable }: { writable: boolean }) {
  const query = useQuery({ queryKey: ["company"], queryFn: organizationApi.company });
  if (query.isLoading) return <ContentSkeleton cards={1} />;
  if (query.error || !query.data) return <ErrorState error={query.error} retry={() => query.refetch()} />;
  return <CompanyForm key={query.data.updated_at} company={query.data} writable={writable} />;
}

function CompanyForm({ company, writable }: { company: Company; writable: boolean }) {
  const client = useQueryClient();
  const [form, setForm] = useState({ name: company.name, activity_label: company.activity_label, timezone: company.timezone });
  const mutation = useMutation({ mutationFn: () => organizationApi.updateCompany(form), onSuccess: () => { toast.success("Profil perusahaan diperbarui."); client.invalidateQueries({ queryKey: ["company"] }); client.invalidateQueries({ queryKey: ["session"] }); }, onError: error => toast.error(isApiError(error) ? error.message : "Perubahan gagal.") });
  return <Card><CardHeader><CardTitle>Profil perusahaan</CardTitle><p className="text-sm text-muted-foreground">Label aktivitas hanya mengubah istilah tampilan; entitas internal tetap konsisten.</p></CardHeader><CardContent><form className="max-w-xl space-y-5" onSubmit={e => { e.preventDefault(); mutation.mutate(); }}><div className="space-y-2"><Label htmlFor="company-name">Nama perusahaan</Label><Input id="company-name" required disabled={!writable} value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} /></div><div className="space-y-2"><Label htmlFor="activity-label">Label aktivitas</Label><Input id="activity-label" required maxLength={40} disabled={!writable} value={form.activity_label} onChange={e => setForm({ ...form, activity_label: e.target.value })} /><p className="text-xs text-muted-foreground">Contoh: SA, Aktivasi, Instalasi.</p></div><div className="space-y-2"><Label htmlFor="timezone">Zona waktu</Label><Select id="timezone" disabled={!writable} value={form.timezone} onChange={e => setForm({ ...form, timezone: e.target.value })}><option value="Asia/Jakarta">Asia/Jakarta (WIB)</option><option value="Asia/Makassar">Asia/Makassar (WITA)</option><option value="Asia/Jayapura">Asia/Jayapura (WIT)</option></Select></div><Button disabled={!writable || mutation.isPending}>{mutation.isPending ? <LoaderCircle className="animate-spin" /> : <Save />}Simpan perubahan</Button></form></CardContent></Card>;
}
