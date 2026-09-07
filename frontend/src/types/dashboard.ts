export interface PeriodProgress {
  target: number | null;
  realization: number;
  achievement_percent: number | null;
}

export interface SpvDashboard {
  total_sales: number;
  target: number | null;
  achievement_percent: number | null;
  active_sales: number;
  pending_validation: number;
  activity_last_7_days: Array<{ date: string; count: number }>;
  top_performers: Array<{
    sales_id: string;
    sales_name: string;
    validated_count: number;
  }>;
}

export interface SalesDashboard {
  weekly_target: PeriodProgress | null;
  monthly_target: PeriodProgress | null;
  activity_counts: {
    draft: number;
    pending: number;
    validated: number;
    rejected: number;
  };
  leaderboard_rank: number | null;
  commission_estimate: number;
}

export interface AdminDashboard {
  companies_total: number;
  users_total: number;
  subscriptions: {
    trialing: number;
    active: number;
    past_due: number;
    expired: number;
    cancelled: number;
  };
  payments_total: number;
}
