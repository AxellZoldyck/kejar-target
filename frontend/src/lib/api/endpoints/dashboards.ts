import type {
  AdminDashboard,
  SalesDashboard,
  SpvDashboard,
} from "@/types/dashboard";
import { apiRequest, queryString } from "../browser-client";

export const dashboardApi = {
  admin: () => apiRequest<AdminDashboard>("/admin/dashboard"),
  spv: (period?: string) =>
    apiRequest<SpvDashboard>(`/spv/dashboard${queryString({ period })}`),
  sales: (period?: string) =>
    apiRequest<SalesDashboard>(`/sales/dashboard${queryString({ period })}`),
};
