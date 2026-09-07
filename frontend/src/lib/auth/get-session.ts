import "server-only";

import { cache } from "react";
import { redirect } from "next/navigation";

import { ApiError } from "@/lib/api/api-error";
import { serverApiRequest } from "@/lib/api/server-client";
import type { Role, Session } from "@/types/auth";
import { roleHome } from "./role-home";

export const getSession = cache(async (): Promise<Session | null> => {
  try {
    return await serverApiRequest<Session>("/me");
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) return null;
    if (error instanceof ApiError && error.code === "ACCOUNT_INACTIVE") {
      redirect("/login?inactive=1");
    }
    throw error;
  }
});

export async function requireRole(role: Role) {
  const session = await getSession();

  if (!session) redirect("/login");
  if (!session.user.is_active) redirect("/login?inactive=1");
  if (session.company && session.company.status !== "active") {
    redirect("/akses-ditolak?reason=company-inactive");
  }
  if (session.user.role !== role) redirect(roleHome[session.user.role]);

  return session;
}
