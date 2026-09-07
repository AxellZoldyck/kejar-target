import type { Metadata } from "next";
import { SalesDashboardView } from "@/features/dashboard/sales-dashboard";
export const metadata: Metadata = { title: "Dashboard Sales" };
export default function Page() { return <SalesDashboardView />; }
