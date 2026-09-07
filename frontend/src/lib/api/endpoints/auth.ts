import type { Session } from "@/types/auth";
import { apiRequest } from "../browser-client";

export interface LoginInput {
  email: string;
  password: string;
}

export interface RegisterInput {
  company_name: string;
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  timezone: string;
  team_name?: string;
}

export type AuthResult = Pick<Session, "user" | "company" | "subscription">;

export const authApi = {
  login: (input: LoginInput) =>
    apiRequest<AuthResult>("/auth/login", {
      method: "POST",
      body: input as unknown as Record<string, unknown>,
    }),
  register: (input: RegisterInput) =>
    apiRequest<AuthResult>("/auth/register", {
      method: "POST",
      body: input as unknown as Record<string, unknown>,
    }),
  logout: () => apiRequest<void>("/auth/logout", { method: "POST" }),
  me: () => apiRequest<Session>("/me"),
};
