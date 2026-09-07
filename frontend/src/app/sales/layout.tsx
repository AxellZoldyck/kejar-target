import { ProtectedRoleLayout } from "@/components/shell/protected-role-layout";

export default function SalesLayout({ children }: { children: React.ReactNode }) {
  return <ProtectedRoleLayout role="sales">{children}</ProtectedRoleLayout>;
}
