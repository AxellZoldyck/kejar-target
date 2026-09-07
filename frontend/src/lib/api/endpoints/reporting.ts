import type { LeaderboardEntry } from "@/types/domain";
import { apiRequest, queryString } from "../browser-client";

export const reportingApi = {
  leaderboard: (params: { period?: string; type?: "weekly" | "monthly"; scope?: "company" | "team"; team_id?: string } = {}) =>
    apiRequest<LeaderboardEntry[]>(`/leaderboard${queryString(params)}`),
};
