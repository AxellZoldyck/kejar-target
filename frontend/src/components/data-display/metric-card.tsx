import type { LucideIcon } from "lucide-react";
import { Card } from "@/components/ui/card";

export function MetricCard({ label, value, helper, icon: Icon }: { label: string; value: React.ReactNode; helper?: string; icon: LucideIcon }) {
  return (
    <Card className="p-5"><div className="flex items-start justify-between gap-4"><div><p className="text-sm font-medium text-muted-foreground">{label}</p><p className="mt-2 text-2xl font-bold tabular-nums tracking-tight">{value}</p>{helper && <p className="mt-1 text-xs text-muted-foreground">{helper}</p>}</div><span className="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"><Icon className="size-5" aria-hidden="true" /></span></div></Card>
  );
}
