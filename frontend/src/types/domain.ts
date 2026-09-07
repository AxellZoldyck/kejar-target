export type ActivityStatus =
  | "draft"
  | "pending"
  | "validated"
  | "rejected";

export interface SalesActivity {
  id: string;
  team_id: string;
  sales_id: string;
  product_id: string;
  activity_date: string;
  customer_reference: string;
  notes: string | null;
  status: ActivityStatus;
  rejection_reason: string | null;
  product: { id: string; name: string; code?: string | null };
  sales?: { id: string; name: string };
  team?: { id: string; name: string };
  submitted_at?: string | null;
  validated_at?: string | null;
  has_evidence: boolean;
  evidence_url: string | null;
  created_at: string;
  updated_at: string;
}

export interface ActivityHistory {
  id: string;
  from_status: ActivityStatus | null;
  to_status: ActivityStatus;
  actor?: { id: string; name: string };
  reason: string | null;
  created_at: string;
}

export interface LeaderboardEntry {
  rank: number;
  sales_id: string;
  sales_name: string;
  validated_count: number;
  target: number | null;
  achievement_percent: number | null;
}

export interface Team {
  id: string;
  name: string;
  is_active: boolean;
  supervisor?: { id: string; name: string; email: string };
  active_members_count?: number;
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: string;
  name: string;
  code: string | null;
  product_fee_amount: number;
  is_active: boolean;
}

export interface SalesUser {
  id: string;
  name: string;
  email: string;
  is_active: boolean;
  team?: { id: string; name: string } | null;
}

export interface Target {
  id: string;
  sales_id: string;
  sales?: { id: string; name: string };
  type: "weekly" | "monthly";
  period_start: string;
  period_end: string;
  target_value: number;
  validated_count: number | null;
  achievement_percent: number | null;
  configured: boolean;
  created_at: string;
  updated_at: string;
}

export interface Commission {
  id: string;
  period: string;
  sales?: { id: string; name: string };
  product_fee_amount: number;
  multiplier_value: number | string;
  progressive_incentive_amount: number;
  total_amount: number;
  calculated_at: string;
  team?: { id: string; name: string };
  formula_snapshot?: Record<string, unknown>;
  calculation_version?: number;
}

export interface CommissionSetting {
  id: string;
  version: number;
  multiplier_enabled: boolean;
  progressive_enabled: boolean;
  progressive_overflow_behavior: "zero" | "repeat_last";
  effective_from: string;
  effective_until: string | null;
  is_active: boolean;
  product_fees: Array<{
    id: string;
    product_id: string;
    product_name: string | null;
    fee_amount: number;
  }>;
  multiplier_rules: Array<{
    id: string;
    min_sa: number;
    max_sa: number | null;
    multiplier_value: string | number;
  }>;
  progressive_rules: Array<{
    id: string;
    product_id: string;
    product_name: string | null;
    sequence_number: number;
    incentive_amount: number;
  }>;
}

export interface Company {
  id: string;
  name: string;
  slug: string;
  activity_label: string;
  timezone: string;
  status: "active" | "inactive";
  created_at: string;
  updated_at: string;
}

export interface Subscription {
  id: string;
  plan_code: string;
  status: string;
  trial_ends_at: string | null;
  starts_at: string | null;
  ends_at: string | null;
  can_mutate?: boolean;
}

export interface AdminCompany {
  id: string;
  name: string;
  slug: string;
  status: "active" | "inactive";
  users_count?: number;
  activity_label: string;
  timezone: string;
  created_at: string;
  updated_at: string;
  subscription?: {
    id: string;
    plan_code: string;
    status: string;
    trial_ends_at: string | null;
    ends_at: string | null;
  } | null;
}

export interface Payment {
  id: string;
  amount: number;
  status: string;
  provider_reference: string | null;
  paid_at: string | null;
  created_at: string;
  company?: { id: string; name: string };
  subscription?: { id: string; plan_code: string };
}

export interface AdminSubscription extends Subscription {
  company: { id: string; name: string; slug: string };
  can_mutate: boolean;
}
