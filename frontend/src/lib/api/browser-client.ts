import type { ApiEnvelope, ApiErrorBody } from "@/types/api";
import { ApiError } from "./api-error";
import { csrfToken, ensureCsrf, resetCsrf } from "./csrf";
import { endpointUrl } from "./config";

type RequestBody = BodyInit | Record<string, unknown> | undefined;

interface ApiRequestInit extends Omit<RequestInit, "body"> {
  body?: RequestBody;
  csrfRetry?: boolean;
}

const mutationMethods = new Set(["POST", "PUT", "PATCH", "DELETE"]);

function isNativeBody(body: RequestBody): body is BodyInit {
  return (
    typeof body === "string" ||
    body instanceof FormData ||
    body instanceof URLSearchParams ||
    body instanceof Blob ||
    body instanceof ArrayBuffer
  );
}

async function parseBody(response: Response): Promise<unknown> {
  if (response.status === 204) return undefined;
  const text = await response.text();
  if (!text) return undefined;

  try {
    return JSON.parse(text) as unknown;
  } catch {
    return { message: text };
  }
}

export async function apiRequest<T>(
  path: string,
  init: ApiRequestInit = {},
): Promise<T> {
  const method = (init.method ?? "GET").toUpperCase();
  const isMutation = mutationMethods.has(method);

  if (isMutation && typeof window !== "undefined") {
    await ensureCsrf();
  }

  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");

  const body = init.body;
  let serializedBody: BodyInit | undefined;

  if (body !== undefined) {
    if (isNativeBody(body)) {
      serializedBody = body;
    } else {
      headers.set("Content-Type", "application/json");
      serializedBody = JSON.stringify(body);
    }
  }

  if (isMutation) {
    const token = csrfToken();
    if (token) headers.set("X-XSRF-TOKEN", token);
  }

  const response = await fetch(endpointUrl(path), {
    ...init,
    method,
    body: serializedBody,
    headers,
    credentials: "include",
    cache: "no-store",
  });

  if (response.status === 419 && init.csrfRetry !== false) {
    resetCsrf();
    await ensureCsrf();
    return apiRequest<T>(path, { ...init, csrfRetry: false });
  }

  const payload = await parseBody(response);

  if (!response.ok) {
    throw ApiError.from(response.status, payload as Partial<ApiErrorBody> | null);
  }

  if (response.status === 204) return undefined as T;

  return (payload as ApiEnvelope<T>).data;
}

export function queryString(params: Record<string, string | number | undefined>) {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== "") search.set(key, String(value));
  });
  const value = search.toString();
  return value ? `?${value}` : "";
}
