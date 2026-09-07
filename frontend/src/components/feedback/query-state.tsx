"use client";

import { AlertCircle, Inbox } from "lucide-react";
import { isApiError } from "@/lib/api/api-error";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

export function ContentSkeleton({ cards = 3 }: { cards?: number }) {
  return <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Memuat data">{Array.from({ length: cards }, (_, index) => <Skeleton className="h-32" key={index} />)}</div>;
}

export function ErrorState({ error, retry }: { error: unknown; retry?: () => void }) {
  const message = isApiError(error) ? error.message : "Data belum dapat dimuat. Pastikan layanan API aktif lalu coba lagi.";
  return (
    <Card className="border-destructive/20 p-8 text-center">
      <AlertCircle className="mx-auto size-8 text-destructive" aria-hidden="true" />
      <h2 className="mt-3 font-semibold">Tidak dapat memuat data</h2>
      <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground">{message}</p>
      {retry && <Button variant="outline" className="mt-5" onClick={retry}>Coba lagi</Button>}
    </Card>
  );
}

export function EmptyState({ title = "Belum ada data", description = "Data akan muncul di sini setelah aktivitas pertama dibuat.", action }: { title?: string; description?: string; action?: React.ReactNode }) {
  return (
    <Card className="p-8 text-center">
      <Inbox className="mx-auto size-8 text-muted-foreground" aria-hidden="true" />
      <h2 className="mt-3 font-semibold">{title}</h2>
      <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground">{description}</p>
      {action && <div className="mt-5">{action}</div>}
    </Card>
  );
}
