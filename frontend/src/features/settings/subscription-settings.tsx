"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { CreditCard, LoaderCircle } from "lucide-react";
import { toast } from "sonner";

import { ErrorState } from "@/components/feedback/query-state";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { subscriptionApi } from "@/lib/api/endpoints/subscription";
import { isApiError } from "@/lib/api/api-error";
import { formatDate } from "@/lib/format";

export function SubscriptionSettings(){const query=useQuery({queryKey:["subscription"],queryFn:subscriptionApi.show});const checkout=useMutation({mutationFn:()=>subscriptionApi.checkout(),onSuccess:r=>toast.info(r.message),onError:e=>toast.error(isApiError(e)?e.message:"Permintaan gagal.")});if(query.error)return <ErrorState error={query.error} retry={()=>query.refetch()}/>;const item=query.data;return <Card className="max-w-2xl"><CardHeader><CardTitle className="flex items-center gap-2"><CreditCard className="size-5 text-primary"/>Langganan</CardTitle></CardHeader><CardContent className="space-y-5">{item?<dl className="grid gap-4 text-sm sm:grid-cols-2"><div><dt className="text-muted-foreground">Paket</dt><dd className="mt-1 font-semibold uppercase">{item.plan_code}</dd></div><div><dt className="text-muted-foreground">Status</dt><dd className="mt-1"><Badge variant={item.can_mutate?"success":"warning"}>{item.status}</Badge></dd></div><div><dt className="text-muted-foreground">Trial berakhir</dt><dd className="mt-1 font-semibold">{formatDate(item.trial_ends_at)}</dd></div><div><dt className="text-muted-foreground">Langganan berakhir</dt><dd className="mt-1 font-semibold">{formatDate(item.ends_at)}</dd></div></dl>:<p className="text-sm text-muted-foreground">Belum ada langganan untuk perusahaan ini.</p>}<Button onClick={()=>checkout.mutate()} disabled={checkout.isPending}>{checkout.isPending?<LoaderCircle className="animate-spin"/>:<CreditCard/>}Minta aktivasi Starter</Button><p className="text-xs leading-5 text-muted-foreground">Integrasi gateway belum diaktifkan; permintaan tidak membuat pembayaran atau status aktif palsu.</p></CardContent></Card>}
