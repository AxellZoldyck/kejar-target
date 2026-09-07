import Link from "next/link";
import { Target } from "lucide-react";
import { cn } from "@/lib/utils";

export function Brand({ className, compact = false }: { className?: string; compact?: boolean }) {
  return (
    <Link href="/" className={cn("inline-flex items-center gap-2.5", className)}>
      <span className="grid size-9 place-items-center rounded-xl bg-primary text-primary-foreground shadow-sm"><Target className="size-5" aria-hidden="true" /></span>
      {!compact && <span className="text-base font-bold tracking-tight">Kejar Target</span>}
    </Link>
  );
}
