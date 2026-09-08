import type { ApiEnvelope, ApiErrorBody, PaginationMeta } from "@/types/api";
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

async function apiEnvelopeRequest<T, M = Record<string, never>>(
  path: string,
  init: ApiRequestInit = {},
): Promise<ApiEnvelope<T, M>> {
  const method = (init.method ?? "GET").toUpperCase();
  const isMutation = mutationMethods.has(method);
  const { body, csrfRetry = true, ...requestInit } = init;

  if (isMutation && typeof window !== "undefined") {
    await ensureCsrf();
  }

  const headers = new Headers(requestInit.headers);
  headers.set("Accept", "application/json");

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
    ...requestInit,
    method,
    body: serializedBody,
    headers,
    credentials: "include",
    cache: "no-store",
  });

  if (response.status === 419 && csrfRetry) {
    resetCsrf();
    await ensureCsrf();
    return apiEnvelopeRequest<T, M>(path, { ...init, csrfRetry: false });
  }

  const payload = await parseBody(response);

  if (!response.ok) {
    throw ApiError.from(response.status, payload as Partial<ApiErrorBody> | null);
  }

  if (response.status === 204) {
    return { data: undefined as T };
  }

  return payload as ApiEnvelope<T, M>;
}

export async function apiRequest<T>(
  path: string,
  init: ApiRequestInit = {},
): Promise<T> {
  return (await apiEnvelopeRequest<T>(path, init)).data;
}

export async function apiPaginatedList<T>(
  path: string,
  params: Record<string, string | number | undefined> = {},
): Promise<T[]> {
  const items: T[] = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await apiEnvelopeRequest<T[], PaginationMeta>(
      `${path}${queryString({ ...params, page, per_page: 100 })}`,
    );
    items.push(...response.data);
    lastPage = response.meta?.last_page ?? page;
    page += 1;
  } while (page <= lastPage);

  return items;
}

export function queryString(params: Record<string, string | number | undefined>) {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== "") search.set(key, String(value));
  });
  const value = search.toString();
  return value ? `?${value}` : "";
}
