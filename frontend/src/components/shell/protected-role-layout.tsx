import { SessionProvider } from "@/features/auth/session-provider";
import { requireRole } from "@/lib/auth/get-session";
import type { Role } from "@/types/auth";
import { RoleShell } from "./role-shell";

export async function ProtectedRoleLayout({ role, children }: { role: Role; children: React.ReactNode }) {
  const session = await requireRole(role);
  return <SessionProvider initialSession={session}><RoleShell role={role} session={session}>{children}</RoleShell></SessionProvider>;
}
