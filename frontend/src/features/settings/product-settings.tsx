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
import { formatRupiah } from "@/lib/format";

const empty={name:"",code:"",product_fee_amount:""};
export function ProductSettings({writable}:{writable:boolean}){
 const client=useQueryClient();const [form,setForm]=useState(empty);const products=useQuery({queryKey:["products","all"],queryFn:()=>organizationApi.products()});
 const create=useMutation({mutationFn:()=>organizationApi.createProduct({name:form.name,code:form.code,product_fee_amount:Number(form.product_fee_amount)}),onSuccess:()=>{setForm(empty);toast.success("Produk berhasil ditambahkan.");client.invalidateQueries({queryKey:["products"]});},onError:e=>toast.error(isApiError(e)?e.message:"Produk gagal dibuat.")});
 const toggle=useMutation({mutationFn:({id,is_active}:{id:string;is_active:boolean})=>organizationApi.updateProduct(id,{is_active}),onSuccess:()=>{toast.success("Status produk diperbarui.");client.invalidateQueries({queryKey:["products"]});},onError:e=>toast.error(isApiError(e)?e.message:"Perubahan gagal.")});
 if(products.error)return <ErrorState error={products.error} retry={()=>products.refetch()}/>;
 return <div className="grid gap-4 lg:grid-cols-[.8fr_1.2fr]"><Card><CardHeader><CardTitle>Tambah produk</CardTitle></CardHeader><CardContent><form className="space-y-4" onSubmit={e=>{e.preventDefault();create.mutate()}}><div className="space-y-2"><Label htmlFor="product-name">Nama produk</Label><Input id="product-name" required disabled={!writable} value={form.name} onChange={e=>setForm({...form,name:e.target.value})}/></div><div className="space-y-2"><Label htmlFor="product-code">Kode unik</Label><Input id="product-code" required disabled={!writable} value={form.code} onChange={e=>setForm({...form,code:e.target.value.toUpperCase()})}/></div><div className="space-y-2"><Label htmlFor="product-fee">Fee produk (rupiah)</Label><Input id="product-fee" type="number" min="0" required disabled={!writable} value={form.product_fee_amount} onChange={e=>setForm({...form,product_fee_amount:e.target.value})}/></div><Button disabled={!writable||create.isPending}>{create.isPending?<LoaderCircle className="animate-spin"/>:<Plus/>}Tambah produk</Button></form></CardContent></Card><Card><CardHeader><CardTitle>Daftar produk</CardTitle></CardHeader><CardContent className="space-y-3">{!products.data?.length?<EmptyState/>:products.data.map(product=><div className="flex items-center justify-between gap-3 rounded-lg border p-3" key={product.id}><div><div className="flex items-center gap-2"><p className="font-semibold">{product.name}</p><Badge variant={product.is_active?"success":"secondary"}>{product.is_active?"Aktif":"Nonaktif"}</Badge></div><p className="mt-1 text-xs text-muted-foreground">{product.code} · fee {formatRupiah(product.product_fee_amount)}</p></div><Button size="sm" variant="outline" disabled={!writable||toggle.isPending} onClick={()=>toggle.mutate({id:product.id,is_active:!product.is_active})}>{product.is_active?"Nonaktifkan":"Aktifkan"}</Button></div>)}</CardContent></Card></div>;
}
