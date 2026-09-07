"use client";

import { useQuery } from "@tanstack/react-query";
import { Medal, Trophy } from "lucide-react";
import { useState } from "react";

import { PageHeader } from "@/components/data-display/page-header";
import {
  ContentSkeleton,
  EmptyState,
  ErrorState,
} from "@/components/feedback/query-state";
import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { useSession } from "@/features/auth/session-provider";
import { reportingApi } from "@/lib/api/endpoints/reporting";
import { formatNumber, formatPercent } from "@/lib/format";

type PeriodType = "weekly" | "monthly";

const today = new Date().toISOString().slice(0, 10);
const currentMonth = today.slice(0, 7);

export function LeaderboardView() {
  const session = useSession();
  const [periodType, setPeriodType] = useState<PeriodType>("monthly");
  const [monthlyPeriod, setMonthlyPeriod] = useState(currentMonth);
  const [weeklyAnchor, setWeeklyAnchor] = useState(today);
  const [scope, setScope] = useState<"company" | "team">("company");
  const [teamId, setTeamId] = useState(session.teams[0]?.id ?? "");
  const period = periodType === "monthly" ? monthlyPeriod : weeklyAnchor;

  const query = useQuery({
    queryKey: ["leaderboard", periodType, period, scope, teamId],
    queryFn: () =>
      reportingApi.leaderboard({
        period,
        type: periodType,
        scope,
        team_id: scope === "team" ? teamId : undefined,
      }),
    enabled: scope === "company" || Boolean(teamId),
  });

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="Performa"
        title="Leaderboard"
        description="Peringkat berdasarkan aktivitas tervalidasi, lalu nilai penjualan bila tersedia, dan waktu pencapaian paling awal."
        action={
          <div className="flex flex-wrap gap-2">
            <Select
              aria-label="Tipe periode"
              value={periodType}
              onChange={(event) =>
                setPeriodType(event.target.value as PeriodType)
              }
            >
              <option value="weekly">Mingguan</option>
              <option value="monthly">Bulanan</option>
            </Select>
            <Select
              aria-label="Lingkup leaderboard"
              value={scope}
              onChange={(event) =>
                setScope(event.target.value as "company" | "team")
              }
            >
              <option value="company">Perusahaan</option>
              <option value="team" disabled={!session.teams.length}>
                Tim
              </option>
            </Select>
            {scope === "team" && (
              <Select
                aria-label="Tim"
                value={teamId}
                onChange={(event) => setTeamId(event.target.value)}
              >
                {session.teams.map((team) => (
                  <option value={team.id} key={team.id}>
                    {team.name}
                  </option>
                ))}
              </Select>
            )}
            <Input
              aria-label={
                periodType === "monthly"
                  ? "Bulan leaderboard"
                  : "Tanggal dalam minggu"
              }
              className="w-40"
              type={periodType === "monthly" ? "month" : "date"}
              value={period}
              onChange={(event) => {
                if (periodType === "monthly") {
                  setMonthlyPeriod(event.target.value);
                } else {
                  setWeeklyAnchor(event.target.value);
                }
              }}
            />
          </div>
        }
      />
      {query.isLoading ? (
        <ContentSkeleton />
      ) : query.error ? (
        <ErrorState error={query.error} retry={() => query.refetch()} />
      ) : !query.data?.length ? (
        <EmptyState
          title="Belum ada peringkat"
          description="Leaderboard akan terisi ketika sales memiliki aktivitas tervalidasi."
        />
      ) : (
        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b bg-secondary/60 text-xs uppercase text-muted-foreground">
                <tr>
                  <th className="px-5 py-3">Peringkat</th>
                  <th className="px-5 py-3">Sales</th>
                  <th className="px-5 py-3 text-right">Tervalidasi</th>
                  <th className="px-5 py-3 text-right">Target</th>
                  <th className="px-5 py-3 text-right">Pencapaian</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {query.data.map((item) => (
                  <tr className="hover:bg-secondary/30" key={item.sales_id}>
                    <td className="px-5 py-4">
                      <span className="inline-flex items-center gap-2 font-bold">
                        {item.rank <= 3 ? (
                          <Medal className="size-4 text-amber-500" />
                        ) : (
                          <Trophy className="size-4 text-muted-foreground" />
                        )}
                        #{item.rank}
                      </span>
                    </td>
                    <td className="px-5 py-4 font-semibold">
                      {item.sales_name}
                      {item.sales_id === session.user.id && (
                        <Badge className="ml-2">Anda</Badge>
                      )}
                    </td>
                    <td className="px-5 py-4 text-right font-bold tabular-nums">
                      {formatNumber(item.validated_count)}
                    </td>
                    <td className="px-5 py-4 text-right tabular-nums">
                      {formatNumber(item.target)}
                    </td>
                    <td className="px-5 py-4 text-right font-semibold text-primary">
                      {formatPercent(item.achievement_percent)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}
    </div>
  );
}
