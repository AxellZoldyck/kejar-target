import type { LucideIcon } from "lucide-react";
import {
  Building2,
  ClipboardCheck,
  CreditCard,
  FileText,
  LayoutDashboard,
  ReceiptText,
  Settings,
  Trophy,
  UserCircle,
  Users,
  WalletCards,
} from "lucide-react";

import type { Role } from "@/types/auth";

export interface NavItem {
  href: string;
  label: string;
  icon: LucideIcon;
}

export const navigation: Record<Role, NavItem[]> = {
  super_admin: [
    { href: "/admin/dashboard", label: "Dashboard", icon: LayoutDashboard },
    { href: "/admin/companies", label: "Companies", icon: Building2 },
    { href: "/admin/subscriptions", label: "Subscriptions", icon: CreditCard },
    { href: "/admin/payments", label: "Payments", icon: ReceiptText },
  ],
  spv: [
    { href: "/spv/dashboard", label: "Dashboard", icon: LayoutDashboard },
    { href: "/spv/sales", label: "Sales", icon: Users },
    { href: "/spv/validasi", label: "Validasi", icon: ClipboardCheck },
    { href: "/spv/leaderboard", label: "Leaderboard", icon: Trophy },
    { href: "/spv/komisi", label: "Komisi", icon: WalletCards },
    { href: "/spv/pengaturan", label: "Pengaturan", icon: Settings },
  ],
  sales: [
    { href: "/sales/dashboard", label: "Dashboard", icon: LayoutDashboard },
    { href: "/sales/leaderboard", label: "Leaderboard", icon: Trophy },
    { href: "/sales/sa-saya", label: "SA Saya", icon: FileText },
    { href: "/sales/pendapatan", label: "Pendapatan", icon: WalletCards },
    { href: "/sales/profil", label: "Profil", icon: UserCircle },
  ],
};
