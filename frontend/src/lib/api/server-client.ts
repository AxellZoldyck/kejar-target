import "server-only";

import { cookies } from "next/headers";

import type { ApiEnvelope, ApiErrorBody } from "@/types/api";
import { ApiError } from "./api-error";
import { endpointUrl } from "./config";

const internalApiUrl = (
  process.env.API_INTERNAL_URL ||
  process.env.NEXT_PUBLIC_API_URL ||
  "http://localhost:8000/api/v1"
).replace(/\/$/, "");
const frontendOrigin = (
  process.env.NEXT_PUBLIC_APP_URL || "http://localhost:3000"
).replace(/\/$/, "");

export async function serverApiRequest<T>(path: string): Promise<T> {
  const cookieStore = await cookies();
  const response = await fetch(endpointUrl(path, internalApiUrl), {
    headers: 
if (path === "/me") {
  console.info("[auth/me]", {
    status: response.status,
    hasSessionCookie: cookieStore.has(
      process.env.SESSION_COOKIE_NAME || "laravel-session"
    ),
  });
}
{
      Accept: "application/json",
      Cookie: cookieStore.toString(),
      // Sanctum classifies cookie requests from Origin/Referer. Server-side
      // rendering must preserve that first-party context explicitly.
      Origin: frontendOrigin,
      Referer: `${frontendOrigin}/`,
    },
    cache: "no-store",
  });

  const text = await response.text();
  const payload = text ? (JSON.parse(text) as unknown) : undefined;

  if (!response.ok) {
    throw ApiError.from(response.status, payload as Partial<ApiErrorBody> | null);
  }

  return (payload as ApiEnvelope<T>).data;
}
