import { ProtectedRoleLayout } from "@/components/shell/protected-role-layout";

export default function SpvLayout({ children }: { children: React.ReactNode }) {
  return <ProtectedRoleLayout role="spv">{children}</ProtectedRoleLayout>;
}
