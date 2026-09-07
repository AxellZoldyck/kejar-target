import { csrfUrl } from "./config";

let csrfRequest: Promise<void> | null = null;

function cookie(name: string) {
  if (typeof document === "undefined") return null;

  const match = document.cookie
    .split("; ")
    .find((item) => item.startsWith(`${name}=`));

  return match ? decodeURIComponent(match.slice(name.length + 1)) : null;
}

export function csrfToken() {
  return cookie("XSRF-TOKEN");
}

export function resetCsrf() {
  csrfRequest = null;
}

export async function ensureCsrf() {
  if (!csrfRequest) {
    csrfRequest = fetch(csrfUrl, {
      method: "GET",
      credentials: "include",
      headers: { Accept: "application/json" },
      cache: "no-store",
    })
      .then((response) => {
        if (!response.ok && response.status !== 204) {
          throw new Error("Gagal menyiapkan proteksi CSRF.");
        }
      })
      .catch((error: unknown) => {
        csrfRequest = null;
        throw error;
      });
  }

  await csrfRequest;
}
