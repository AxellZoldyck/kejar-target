import type { ActivityStatus } from "@/types/domain";
import { Badge } from "@/components/ui/badge";

const copy: Record<ActivityStatus, { label: string; variant: "secondary" | "warning" | "success" | "destructive" }> = {
  draft: { label: "Draft", variant: "secondary" },
  pending: { label: "Menunggu", variant: "warning" },
  validated: { label: "Tervalidasi", variant: "success" },
  rejected: { label: "Ditolak", variant: "destructive" },
};

export function ActivityStatusBadge({ status }: { status: ActivityStatus }) {
  const item = copy[status];
  return <Badge variant={item.variant}>{item.label}</Badge>;
}
