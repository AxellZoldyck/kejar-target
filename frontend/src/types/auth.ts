export type Role = "super_admin" | "spv" | "sales";

export type SubscriptionStatus =
  | "trialing"
  | "active"
  | "past_due"
  | "expired"
  | "cancelled";

export interface SessionUser {
  id: string;
  name: string;
  email: string;
  role: Role;
  is_active: boolean;
}

export interface SessionCompany {
  id: string;
  name: string;
  timezone: string;
  activity_label: string;
  status: "active" | "inactive";
}

export interface SessionSubscription {
  plan_code: string;
  status: SubscriptionStatus;
  trial_ends_at: string | null;
  ends_at: string | null;
  can_mutate: boolean;
}

export interface SessionTeam {
  id: string;
  name: string;
}

export interface Session {
  user: SessionUser;
  company: SessionCompany | null;
  teams: SessionTeam[];
  permissions: {
    can_mutate: boolean;
    abilities: string[];
  };
  subscription: SessionSubscription | null;
}
