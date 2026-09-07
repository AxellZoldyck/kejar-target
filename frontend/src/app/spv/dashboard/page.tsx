import type { Metadata } from "next";
import { SpvDashboardView } from "@/features/dashboard/spv-dashboard";
export const metadata: Metadata = { title: "Dashboard Supervisor" };
export default function Page() { return <SpvDashboardView />; }
