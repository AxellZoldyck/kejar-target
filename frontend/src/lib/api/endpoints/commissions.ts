import type { Commission, CommissionSetting } from "@/types/domain";
import { apiPaginatedList, apiRequest } from "../browser-client";

export const commissionsApi = {
  list: (params: { period?: string; sales_id?: string } = {}) =>
    apiPaginatedList<Commission>("/commissions", { ...params }),
  show: (id: string) => apiRequest<Commission>(`/commissions/${id}`),
  calculate: (sales_id: string, period: string) =>
    apiRequest<Commission>("/commissions/calculate", { method: "POST", body: { sales_id, period } }),
  setting: () => apiRequest<CommissionSetting | null>("/commission-settings"),
  updateSetting: (input: Pick<CommissionSetting, "multiplier_enabled" | "progressive_enabled" | "progressive_overflow_behavior">) =>
    apiRequest<CommissionSetting>("/commission-settings", { method: "PUT", body: input }),
  replaceFees: (fees: Array<{ product_id: string; fee_amount: number }>) =>
    apiRequest<CommissionSetting>("/commission-product-fees", { method: "PUT", body: { fees } }),
  replaceMultipliers: (rules: Array<{ min_sa: number; max_sa: number | null; multiplier_value: string }>) =>
    apiRequest<CommissionSetting>("/commission-multiplier-rules", { method: "PUT", body: { rules } }),
  replaceProgressives: (rules: Array<{ product_id: string; sequence_number: number; incentive_amount: number }>) =>
    apiRequest<CommissionSetting>("/commission-progressive-rules", { method: "PUT", body: { rules } }),
};
