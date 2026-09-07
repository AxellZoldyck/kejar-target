"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { LoaderCircle, Plus } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

import { EmptyState, ErrorState } from "@/components/feedback/query-state";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { organizationApi } from "@/lib/api/endpoints/organization";
import { isApiError } from "@/lib/api/api-error";

export function TeamSettings({writable}:{writable:boolean}){
  const client=useQueryClient();const [name,setName]=useState("");const teams=useQuery({queryKey:["teams"],queryFn:organizationApi.teams});
  const create=useMutation({mutationFn:()=>organizationApi.createTeam({name}),onSuccess:()=>{setName("");toast.success("Tim berhasil dibuat.");client.invalidateQueries({queryKey:["teams"]});client.invalidateQueries({queryKey:["session"]});},onError:e=>toast.error(isApiError(e)?e.message:"Tim gagal dibuat.")});
  const toggle=useMutation({mutationFn:({id,is_active}:{id:string;is_active:boolean})=>organizationApi.updateTeam(id,{is_active}),onSuccess:()=>{toast.success("Status tim diperbarui.");client.invalidateQueries({queryKey:["teams"]});},onError:e=>toast.error(isApiError(e)?e.message:"Perubahan gagal.")});
  if(teams.error)return <ErrorState error={teams.error} retry={()=>teams.refetch()}/>;
  return <div className="grid gap-4 lg:grid-cols-[.8fr_1.2fr]"><Card><CardHeader><CardTitle>Tambah tim</CardTitle></CardHeader><CardContent><form className="space-y-4" onSubmit={e=>{e.preventDefault();create.mutate()}}><div className="space-y-2"><Label htmlFor="team-name">Nama tim</Label><Input id="team-name" required value={name} disabled={!writable} onChange={e=>setName(e.target.value)}/></div><Button disabled={!writable||create.isPending}>{create.isPending?<LoaderCircle className="animate-spin"/>:<Plus/>}Tambah tim</Button></form></CardContent></Card><Card><CardHeader><CardTitle>Daftar tim</CardTitle></CardHeader><CardContent className="space-y-3">{!teams.data?.length?<EmptyState/>:teams.data.map(team=><div className="flex items-center justify-between gap-3 rounded-lg border p-3" key={team.id}><div><div className="flex items-center gap-2"><p className="font-semibold">{team.name}</p><Badge variant={team.is_active?"success":"secondary"}>{team.is_active?"Aktif":"Nonaktif"}</Badge></div><p className="mt-1 text-xs text-muted-foreground">{team.active_members_count??0} anggota aktif</p></div><Button size="sm" variant="outline" disabled={!writable||toggle.isPending} onClick={()=>toggle.mutate({id:team.id,is_active:!team.is_active})}>{team.is_active?"Nonaktifkan":"Aktifkan"}</Button></div>)}</CardContent></Card></div>;
}
