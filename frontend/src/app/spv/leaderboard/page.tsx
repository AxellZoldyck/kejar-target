import type { Metadata } from "next";
import { LeaderboardView } from "@/features/reporting/leaderboard-view";
export const metadata: Metadata = { title: "Leaderboard" };
export default function Page() { return <LeaderboardView />; }
