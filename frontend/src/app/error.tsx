"use client";

import { useEffect } from "react";
import { AlertTriangle } from "lucide-react";

import { Button } from "@/components/ui/button";

export default function GlobalError({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  useEffect(() => { console.error(error); }, [error]);
  return (
    <main className="grid min-h-screen place-items-center px-4">
      <div className="max-w-md text-center">
        <div className="mx-auto mb-5 grid size-14 place-items-center rounded-2xl bg-destructive/10 text-destructive">
          <AlertTriangle className="size-7" aria-hidden="true" />
        </div>
        <h1 className="text-2xl font-bold tracking-tight">Ada kendala yang tidak terduga</h1>
        <p className="mt-2 text-sm leading-6 text-muted-foreground">
          Muat ulang bagian ini. Jika kendala berlanjut, pastikan API Kejar Target sedang aktif.
        </p>
        <Button className="mt-6" onClick={reset}>Coba lagi</Button>
      </div>
    </main>
  );
}
