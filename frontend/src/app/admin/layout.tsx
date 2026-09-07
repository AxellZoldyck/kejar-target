import { ProtectedRoleLayout } from "@/components/shell/protected-role-layout";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  return <ProtectedRoleLayout role="super_admin">{children}</ProtectedRoleLayout>;
}
