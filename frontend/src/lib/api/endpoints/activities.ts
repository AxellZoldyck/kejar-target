import type { ActivityHistory, ActivityStatus, SalesActivity } from "@/types/domain";
import { apiRequest, queryString } from "../browser-client";

export interface ActivityFilters {
  status?: ActivityStatus | "";
  from?: string;
  to?: string;
}

export const activitiesApi = {
  list: (filters: ActivityFilters = {}) =>
    apiRequest<SalesActivity[]>(`/sales-activities${queryString({ ...filters, per_page: 100 })}`),
  show: (id: string) => apiRequest<SalesActivity>(`/sales-activities/${id}`),
  history: (id: string) => apiRequest<ActivityHistory[]>(`/sales-activities/${id}/history`),
  create: (body: FormData) => apiRequest<SalesActivity>("/sales-activities", { method: "POST", body }),
  update: (id: string, body: FormData) => {
    body.set("_method", "PATCH");
    return apiRequest<SalesActivity>(`/sales-activities/${id}`, { method: "POST", body });
  },
  remove: (id: string) => apiRequest<void>(`/sales-activities/${id}`, { method: "DELETE" }),
  submit: (id: string) => apiRequest<SalesActivity>(`/sales-activities/${id}/submit`, { method: "POST" }),
  validate: (id: string) => apiRequest<SalesActivity>(`/sales-activities/${id}/validate`, { method: "POST" }),
  reject: (id: string, reason: string) => apiRequest<SalesActivity>(`/sales-activities/${id}/reject`, { method: "POST", body: { reason } }),
};
