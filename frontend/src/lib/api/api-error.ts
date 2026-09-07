import type { ApiErrorBody } from "@/types/api";

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    message: string,
    public readonly errors: Record<string, string[]> = {},
  ) {
    super(message);
    this.name = "ApiError";
  }

  static from(status: number, body: Partial<ApiErrorBody> | null) {
    const errors = body?.errors ?? {};
    const firstValidationMessage = Object.values(errors).find(
      (messages) => Array.isArray(messages) && messages.length > 0,
    )?.[0];

    return new ApiError(
      status,
      body?.code ?? `HTTP_${status}`,
      firstValidationMessage ??
        body?.message ??
        "Permintaan tidak dapat diproses.",
      errors,
    );
  }
}

export function isApiError(error: unknown): error is ApiError {
  return error instanceof ApiError;
}
