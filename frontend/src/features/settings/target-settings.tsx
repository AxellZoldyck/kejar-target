"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { LoaderCircle, Plus, Save } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

import { EmptyState, ErrorState } from "@/components/feedback/query-state";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select } from "@/components/ui/select";
import { organizationApi } from "@/lib/api/endpoints/organization";
import { isApiError } from "@/lib/api/api-error";
import { formatNumber, formatPercent } from "@/lib/format";
import type { Target } from "@/types/domain";

function dateOnly(date:Date){return date.toISOString().slice(0,10)}
function periodRange(type:"weekly"|"monthly",value:string){
 if(type==="monthly"){const [year,month]=value.split("-").map(Number);return {period_start:`${value}-01`,period_end:`${value}-${String(new Date(Date.UTC(year,month,0)).getUTCDate()).padStart(2,"0")}`};}
 const anchor=new Date(`${value}T00:00:00Z`);const day=anchor.getUTCDay();const monday=new Date(anchor);monday.setUTCDate(anchor.getUTCDate()+(day===0?-6:1-day));const sunday=new Date(monday);sunday.setUTCDate(monday.getUTCDate()+6);return {period_start:dateOnly(monday),period_end:dateOnly(sunday)};
}
function TargetRow({target,writable,onSaved}:{target:Target;writable:boolean;onSaved:()=>void}){const [value,setValue]=useState(String(target.target_value));const mutation=useMutation({mutationFn:()=>organizationApi.updateTarget(target.id,Number(value)),onSuccess:()=>{toast.success("Target diperbarui.");onSaved();},onError:e=>toast.error(isApiError(e)?e.message:"Perubahan gagal.")});return <div className="grid items-center gap-3 rounded-lg border p-3 sm:grid-cols-[1fr_auto_auto]"><div><p className="font-semibold">{target.sales?.name} · <span className="capitalize">{target.type}</span></p><p className="text-xs text-muted-foreground">{target.period_start} — {target.period_end} · realisasi {formatNumber(target.validated_count)} ({formatPercent(target.achievement_percent)})</p></div><Input className="w-28" type="number" min="0" value={value} disabled={!writable} onChange={e=>setValue(e.target.value)}/><Button size="sm" variant="outline" disabled={!writable||mutation.isPending||Number(value)===target.target_value} onClick={()=>mutation.mutate()}><Save/>Simpan</Button></div>}

export function TargetSettings({writable}:{writable:boolean}){
 const client=useQueryClient();const today=new Date().toISOString().slice(0,10);const [form,setForm]=useState({sales_id:"",type:"monthly" as "weekly"|"monthly",period:today.slice(0,7),target_value:""});
 const sales=useQuery({queryKey:["sales",""],queryFn:()=>organizationApi.sales()});const targets=useQuery({queryKey:["targets"],queryFn:()=>organizationApi.targets()});
 const create=useMutation({mutationFn:()=>organizationApi.createTarget({sales_id:form.sales_id,type:form.type,...periodRange(form.type,form.period),target_value:Number(form.target_value)}),onSuccess:()=>{setForm({...form,target_value:""});toast.success("Target berhasil ditetapkan.");client.invalidateQueries({queryKey:["targets"]});},onError:e=>toast.error(isApiError(e)?e.message:"Target gagal disimpan.")});
 if(sales.error||targets.error)return <ErrorState error={sales.error??targets.error} retry={()=>{sales.refetch();targets.refetch()}}/>;
 return <div className="space-y-4"><Card><CardHeader><CardTitle>Tetapkan target</CardTitle></CardHeader><CardContent><form className="grid gap-4 md:grid-cols-4" onSubmit={e=>{e.preventDefault();create.mutate()}}><div className="space-y-2"><Label htmlFor="target-sales">Sales</Label><Select id="target-sales" required disabled={!writable} value={form.sales_id} onChange={e=>setForm({...form,sales_id:e.target.value})}><option value="">Pilih sales</option>{sales.data?.filter(s=>s.is_active).map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</Select></div><div className="space-y-2"><Label htmlFor="target-type">Tipe</Label><Select id="target-type" value={form.type} disabled={!writable} onChange={e=>{const type=e.target.value as "weekly"|"monthly";setForm({...form,type,period:type==="monthly"?today.slice(0,7):today})}}><option value="monthly">Bulanan</option><option value="weekly">Mingguan</option></Select></div><div className="space-y-2"><Label htmlFor="target-period">Periode</Label><Input id="target-period" required type={form.type==="monthly"?"month":"date"} disabled={!writable} value={form.period} onChange={e=>setForm({...form,period:e.target.value})}/></div><div className="space-y-2"><Label htmlFor="target-value">Jumlah target</Label><div className="flex gap-2"><Input id="target-value" required type="number" min="0" disabled={!writable} value={form.target_value} onChange={e=>setForm({...form,target_value:e.target.value})}/><Button disabled={!writable||create.isPending}>{create.isPending?<LoaderCircle className="animate-spin"/>:<Plus/>}</Button></div></div></form></CardContent></Card><Card><CardHeader><CardTitle>Target tersimpan</CardTitle></CardHeader><CardContent className="space-y-3">{!targets.data?.length?<EmptyState title="Belum ada target"/>:targets.data.map(target=><TargetRow key={target.id} target={target} writable={writable} onSaved={()=>client.invalidateQueries({queryKey:["targets"]})}/>)}</CardContent></Card></div>;
}
