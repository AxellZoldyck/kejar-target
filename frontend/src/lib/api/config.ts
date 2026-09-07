const fallbackApiUrl = "http://localhost:8000/api/v1";

export const browserApiUrl = (
  process.env.NEXT_PUBLIC_API_URL || fallbackApiUrl
).replace(/\/$/, "");

export const csrfUrl = new URL("/sanctum/csrf-cookie", browserApiUrl).toString();

export function endpointUrl(path: string, baseUrl = browserApiUrl) {
  return `${baseUrl}${path.startsWith("/") ? path : `/${path}`}`;
}
