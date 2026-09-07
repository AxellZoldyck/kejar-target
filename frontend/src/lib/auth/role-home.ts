import type { Role } from "@/types/auth";

export const roleHome: Record<Role, string> = {
  super_admin: "/admin/dashboard",
  spv: "/spv/dashboard",
  sales: "/sales/dashboard",
};
