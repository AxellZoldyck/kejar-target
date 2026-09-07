import type { Company, Product, SalesUser, Target, Team } from "@/types/domain";
import { apiRequest, queryString } from "../browser-client";

export const organizationApi = {
  company: () => apiRequest<Company>("/company"),
  updateCompany: (input: Pick<Company, "name" | "activity_label" | "timezone">) =>
    apiRequest<Company>("/company", { method: "PATCH", body: input }),

  teams: () => apiRequest<Team[]>("/teams?per_page=100"),
  createTeam: (input: { name: string; is_active?: boolean }) =>
    apiRequest<Team>("/teams", { method: "POST", body: input }),
  updateTeam: (id: string, input: Partial<Pick<Team, "name" | "is_active">>) =>
    apiRequest<Team>(`/teams/${id}`, { method: "PATCH", body: input }),

  sales: (search?: string) =>
    apiRequest<SalesUser[]>(`/sales${queryString({ per_page: 100, search })}`),
  createSales: (input: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    team_id?: string;
  }) => apiRequest<SalesUser>("/sales", { method: "POST", body: input }),
  updateSales: (id: string, input: Record<string, unknown>) =>
    apiRequest<SalesUser>(`/sales/${id}`, { method: "PATCH", body: input }),

  products: (active?: boolean) =>
    apiRequest<Product[]>(`/products${queryString({ per_page: 100, active: active === undefined ? undefined : active ? 1 : 0 })}`),
  createProduct: (input: { name: string; code: string; product_fee_amount: number; is_active?: boolean }) =>
    apiRequest<Product>("/products", { method: "POST", body: input }),
  updateProduct: (id: string, input: Partial<Omit<Product, "id">>) =>
    apiRequest<Product>(`/products/${id}`, { method: "PATCH", body: input as Record<string, unknown> }),

  targets: (params: { period?: string; sales_id?: string; type?: string } = {}) =>
    apiRequest<Target[]>(`/targets${queryString({ ...params, per_page: 100 })}`),
  createTarget: (input: {
    sales_id: string;
    type: "weekly" | "monthly";
    period_start: string;
    period_end: string;
    target_value: number;
  }) => apiRequest<Target>("/targets", { method: "POST", body: input }),
  updateTarget: (id: string, target_value: number) =>
    apiRequest<Target>(`/targets/${id}`, { method: "PATCH", body: { target_value } }),
};
