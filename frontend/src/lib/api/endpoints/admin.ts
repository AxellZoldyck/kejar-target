import type { AdminCompany, AdminSubscription, Payment } from "@/types/domain";
import { apiRequest, queryString } from "../browser-client";

export const adminApi = {
  companies: (search?: string) => apiRequest<AdminCompany[]>(`/admin/companies${queryString({ search, per_page: 100 })}`),
  updateCompany: (id: string, input: { name?: string; status?: "active" | "inactive" }) =>
    apiRequest<AdminCompany>(`/admin/companies/${id}`, { method: "PATCH", body: input }),
  subscriptions: (status?: string) => apiRequest<AdminSubscription[]>(`/admin/subscriptions${queryString({ status, per_page: 100 })}`),
  payments: (status?: string) => apiRequest<Payment[]>(`/admin/payments${queryString({ status, per_page: 100 })}`),
};
