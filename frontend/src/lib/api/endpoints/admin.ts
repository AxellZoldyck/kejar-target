import type { AdminCompany, AdminSubscription, Payment } from "@/types/domain";
import { apiPaginatedList, apiRequest } from "../browser-client";

export const adminApi = {
  companies: (search?: string) => apiPaginatedList<AdminCompany>("/admin/companies", { search }),
  updateCompany: (id: string, input: { name?: string; status?: "active" | "inactive" }) =>
    apiRequest<AdminCompany>(`/admin/companies/${id}`, { method: "PATCH", body: input }),
  subscriptions: (status?: string) => apiPaginatedList<AdminSubscription>("/admin/subscriptions", { status }),
  payments: (status?: string) => apiPaginatedList<Payment>("/admin/payments", { status }),
};
