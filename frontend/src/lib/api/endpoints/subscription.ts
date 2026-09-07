import type { Subscription } from "@/types/domain";
import { apiRequest } from "../browser-client";

export interface CheckoutResult {
  status: "action_required" | string;
  message: string;
  checkout_url?: string | null;
}

export const subscriptionApi = {
  show: () => apiRequest<Subscription | null>("/subscription"),
  checkout: (plan_code = "starter") => apiRequest<CheckoutResult>("/subscription/checkout", { method: "POST", body: { plan_code } }),
};
