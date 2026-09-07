import { Cable, CheckCircle2 } from "lucide-react";
import { PageHeader } from "@/components/data-display/page-header";
import { Card } from "@/components/ui/card";

export function ModulePlaceholder({ title, description, endpoint }: { title: string; description: string; endpoint: string }) {
  return (
    <div className="space-y-6"><PageHeader title={title} description={description} /><Card className="p-6"><div className="flex gap-4"><span className="grid size-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"><Cable className="size-5" aria-hidden="true" /></span><div><h2 className="font-semibold">Fondasi halaman siap</h2><p className="mt-1 text-sm leading-6 text-muted-foreground">Halaman ini disiapkan tanpa data palsu. Integrasi penuh akan memakai endpoint berikut saat response resource tersedia.</p><div className="mt-4 inline-flex items-center gap-2 rounded-lg bg-muted px-3 py-2 font-mono text-xs"><CheckCircle2 className="size-4 text-success" aria-hidden="true" />{endpoint}</div></div></div></Card></div>
  );
}
