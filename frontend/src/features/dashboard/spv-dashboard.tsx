"use client";

import { useQuery } from "@tanstack/react-query";
import {
  ArrowRight,
  Award,
  CalendarDays,
  CheckCircle2,
  Clock3,
  Target,
  Users,
} from "lucide-react";
import Link from "next/link";
import { useState } from "react";

import { MetricCard } from "@/components/data-display/metric-card";
import { PageHeader } from "@/components/data-display/page-header";
import { ContentSkeleton, ErrorState } from "@/components/feedback/query-state";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { dashboardApi } from "@/lib/api/endpoints/dashboards";
import { formatNumber, formatPercent } from "@/lib/format";

const currentPeriod = new Date().toISOString().slice(0, 7);

export function SpvDashboardView() {
  const [period, setPeriod] = useState(currentPeriod);
  const query = useQuery({
    queryKey: ["spv-dashboard", period],
    queryFn: () => dashboardApi.spv(period),
  });

  if (query.isLoading) return <ContentSkeleton cards={6} />;
  if (query.error || !query.data) {
    return <ErrorState error={query.error} retry={() => query.refetch()} />;
  }

  const data = query.data;
  const chartMax = Math.max(
    ...data.activity_last_7_days.map((item) => item.count),
    1,
  );
  const targetProgress = Math.min(
    100,
    Math.max(0, data.achievement_percent ?? 0),
  );

  return (
    <div className="space-y-6 sm:space-y-8">
      <PageHeader
        eyebrow="Supervisor"
        title="Ringkasan performa tim"
        description="Seluruh angka pencapaian berasal dari aktivitas yang sudah tervalidasi."
        action={
          <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
            <label className="relative block w-full sm:w-auto" htmlFor="spv-dashboard-period">
              <span className="sr-only">Pilih periode dashboard</span>
              <CalendarDays
                aria-hidden="true"
                className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
              />
              <Input
                className="w-full pl-9 sm:w-44"
                id="spv-dashboard-period"
                type="month"
                value={period}
                onChange={(event) => setPeriod(event.target.value)}
              />
            </label>
            <Button asChild className="w-full sm:w-auto">
              <Link href="/spv/validasi">
                Periksa antrean
                <ArrowRight aria-hidden="true" />
              </Link>
            </Button>
          </div>
        }
      />

      <Card className="relative overflow-hidden rounded-2xl border-primary/15">
        <div
          aria-hidden="true"
          className="pointer-events-none absolute -right-20 -top-24 size-64 rounded-full bg-primary/5"
        />
        <CardContent className="relative p-5 sm:p-6 lg:p-7">
          <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
            <div>
              <div className="flex items-center gap-3">
                <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                  <Target aria-hidden="true" className="size-5" />
                </span>
                <div>
                  <p className="text-xs font-bold uppercase tracking-[0.14em] text-primary">
                    Progress target utama
                  </p>
                  <h2 className="mt-1 text-lg font-semibold tracking-tight sm:text-xl">
                    Target tim periode terpilih
                  </h2>
                </div>
              </div>
              <p className="mt-4 max-w-2xl text-sm leading-6 text-muted-foreground">
                {data.target !== null
                  ? "Perbandingan aktivitas tervalidasi dengan target bulanan tim."
                  : "Target bulanan tim belum diatur untuk periode ini."}
              </p>
            </div>

            <div className="lg:min-w-48 lg:text-right">
              <p className="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                Pencapaian
              </p>
              <p className="mt-1 text-3xl font-bold tabular-nums tracking-tight text-primary sm:text-4xl">
                {formatPercent(data.achievement_percent)}
              </p>
            </div>
          </div>

          <div className="mt-7">
            <div className="mb-3 flex flex-col gap-1 text-sm sm:flex-row sm:items-center sm:justify-between">
              <p>
                <strong className="text-base tabular-nums">
                  {formatNumber(data.total_sales)}
                </strong>{" "}
                <span className="text-muted-foreground">aktivitas tervalidasi</span>
              </p>
              <p className="font-medium text-muted-foreground">
                {data.target !== null
                  ? `Target ${formatNumber(data.target)}`
                  : "Target belum diatur"}
              </p>
            </div>
            <div
              aria-label="Pencapaian target tim"
              aria-valuemax={100}
              aria-valuemin={0}
              aria-valuenow={
                data.achievement_percent === null ? undefined : targetProgress
              }
              aria-valuetext={formatPercent(data.achievement_percent)}
              className="h-2.5 overflow-hidden rounded-full bg-secondary"
              role="progressbar"
            >
              <div
                className="h-full rounded-full bg-primary transition-[width] duration-500"
                style={{ width: `${targetProgress}%` }}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <MetricCard
          icon={CheckCircle2}
          label="Aktivitas tervalidasi"
          value={formatNumber(data.total_sales)}
          helper="Periode terpilih"
        />
        <MetricCard
          icon={Target}
          label="Target tim"
          value={formatNumber(data.target)}
          helper={data.target !== null ? "Periode terpilih" : "Belum diatur"}
        />
        <MetricCard
          icon={Users}
          label="Sales aktif"
          value={formatNumber(data.active_sales)}
          helper="Anggota tim saat ini"
        />
        <MetricCard
          icon={Clock3}
          label="Menunggu validasi"
          value={formatNumber(data.pending_validation)}
          helper="Perlu tindakan supervisor"
        />
      </div>

      <div className="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(19rem,0.8fr)]">
        <Card className="overflow-hidden rounded-2xl">
          <CardHeader className="border-b pb-4">
            <div className="flex items-start justify-between gap-4">
              <div>
                <CardTitle>Aktivitas tervalidasi 7 hari terakhir</CardTitle>
                <CardDescription className="mt-1">
                  Volume aktivitas harian terbaru dari seluruh tim.
                </CardDescription>
              </div>
              <span className="shrink-0 rounded-full bg-secondary px-2.5 py-1 text-xs font-semibold text-secondary-foreground">
                7 hari
              </span>
            </div>
          </CardHeader>
          <CardContent className="pt-5">
            <figure>
              <div className="grid h-64 grid-cols-7 gap-2 sm:gap-3">
                {data.activity_last_7_days.map((item) => {
                  const activityDate = new Date(`${item.date}T00:00:00`);
                  const dayLabel = activityDate.toLocaleDateString("id-ID", {
                    weekday: "short",
                  });
                  const fullDateLabel = activityDate.toLocaleDateString("id-ID", {
                    dateStyle: "long",
                  });
                  const barHeight =
                    item.count === 0
                      ? "2px"
                      : `${Math.max(6, (item.count / chartMax) * 100)}%`;

                  return (
                    <div
                      aria-label={`${fullDateLabel}: ${formatNumber(item.count)} aktivitas tervalidasi`}
                      className="flex min-w-0 flex-col justify-end"
                      key={item.date}
                      role="img"
                    >
                      <span className="mb-2 text-center text-xs font-bold tabular-nums">
                        {formatNumber(item.count)}
                      </span>
                      <div className="flex h-44 items-end rounded-lg bg-secondary/60 p-1">
                        <div
                          aria-hidden="true"
                          className="w-full rounded-md bg-primary/75 transition-colors hover:bg-primary"
                          style={{ height: barHeight }}
                        />
                      </div>
                      <span className="mt-2 truncate text-center text-[10px] font-medium uppercase text-muted-foreground sm:text-xs">
                        {dayLabel}
                      </span>
                    </div>
                  );
                })}
              </div>
              <figcaption className="sr-only">
                Grafik jumlah aktivitas tervalidasi selama tujuh hari terakhir.
              </figcaption>
            </figure>
          </CardContent>
        </Card>

        <Card className="overflow-hidden rounded-2xl">
          <CardHeader className="border-b pb-4">
            <div className="flex items-start gap-3">
              <span className="grid size-9 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                <Award aria-hidden="true" className="size-5" />
              </span>
              <div>
                <CardTitle>Top performer</CardTitle>
                <CardDescription className="mt-1">
                  Berdasarkan aktivitas tervalidasi pada periode ini.
                </CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="p-0">
            {data.top_performers.length ? (
              <ol className="divide-y">
                {data.top_performers.map((item, index) => (
                  <li className="flex items-center gap-3 px-5 py-4" key={item.sales_id}>
                    <span
                      className={
                        index === 0
                          ? "grid size-9 shrink-0 place-items-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                          : "grid size-9 shrink-0 place-items-center rounded-full bg-secondary text-sm font-bold text-secondary-foreground"
                      }
                    >
                      {index + 1}
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-semibold">{item.sales_name}</p>
                      <p className="mt-0.5 text-xs text-muted-foreground">Peringkat {index + 1}</p>
                    </div>
                    <div className="shrink-0 text-right">
                      <p className="text-sm font-bold tabular-nums">
                        {formatNumber(item.validated_count)}
                      </p>
                      <p className="text-[11px] text-muted-foreground">tervalidasi</p>
                    </div>
                  </li>
                ))}
              </ol>
            ) : (
              <div className="grid min-h-64 place-items-center px-5 py-8 text-center">
                <div>
                  <Award
                    aria-hidden="true"
                    className="mx-auto size-8 text-muted-foreground/60"
                  />
                  <p className="mt-3 text-sm text-muted-foreground">
                    Belum ada aktivitas tervalidasi.
                  </p>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
