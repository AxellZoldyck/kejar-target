"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { Check, LoaderCircle } from "lucide-react";
import Link from "next/link";
import { toast } from "sonner";

import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { subscriptionApi } from "@/lib/api/endpoints/subscription";
import { isApiError } from "@/lib/api/api-error";
import { formatDate } from "@/lib/format";

export function CheckoutPanel() {
  const subscription = useQuery({ queryKey: ["subscription"], queryFn: subscriptionApi.show, retry: false });
  const checkout = useMutation({
    mutationFn: () => subscriptionApi.checkout("starter"),
    onSuccess: (result) => toast.info(result.message),
    onError: (error) => toast.error(isApiError(error) ? error.message : "Permintaan aktivasi gagal."),
  });
  const unauthenticated = isApiError(subscription.error) && subscription.error.status === 401;

  return (
    <Card className="mx-auto max-w-xl overflow-hidden">
      <div className="bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground">Paket Starter</div>
      <CardHeader><CardTitle className="text-2xl">Aktifkan workspace Anda</CardTitle></CardHeader>
      <CardContent className="space-y-6">
        <div className="space-y-3 text-sm"><p className="flex gap-2"><Check className="size-5 text-success" />Sales, target, validasi, leaderboard, dan komisi.</p><p className="flex gap-2"><Check className="size-5 text-success" />Akses seluruh tim dalam satu perusahaan.</p><p className="flex gap-2"><Check className="size-5 text-success" />Data tetap dapat dibaca saat paket berakhir.</p></div>
        {subscription.data && <Alert>Status saat ini: <strong>{subscription.data.status}</strong>{subscription.data.trial_ends_at ? ` · trial sampai ${formatDate(subscription.data.trial_ends_at)}` : ""}</Alert>}
        {unauthenticated ? (
          <Button asChild className="w-full" size="lg"><Link href="/login">Masuk sebagai supervisor</Link></Button>
        ) : (
          <Button className="w-full" size="lg" disabled={checkout.isPending || subscription.isLoading} onClick={() => checkout.mutate()}>{checkout.isPending && <LoaderCircle className="animate-spin" />}Minta aktivasi Starter</Button>
        )}
        <p className="text-xs leading-5 text-muted-foreground">Gateway pembayaran belum dikonfigurasi. Tombol ini mengirim permintaan aktivasi yang tercatat tanpa membuat transaksi pembayaran palsu.</p>
      </CardContent>
    </Card>
  );
}
