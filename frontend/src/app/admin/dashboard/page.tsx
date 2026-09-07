import type { Metadata } from "next";
import { AdminDashboardView } from "@/features/dashboard/admin-dashboard";
export const metadata: Metadata = { title: "Dashboard Admin" };
export default function Page() { return <AdminDashboardView />; }
