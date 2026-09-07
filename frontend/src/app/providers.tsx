"use client";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useState } from "react";
import { Toaster } from "sonner";

import { PwaRegistration } from "@/components/pwa-registration";

export function AppProviders({ children }: { children: React.ReactNode }) {
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 30_000,
            retry: (count, error) => {
              const status =
                typeof error === "object" && error && "status" in error
                  ? Number(error.status)
                  : 0;
              if ([401, 403, 404, 409, 422].includes(status)) return false;
              return count < 1;
            },
            refetchOnWindowFocus: false,
          },
          mutations: { retry: false },
        },
      }),
  );

  return (
    <QueryClientProvider client={queryClient}>
      {children}
      <Toaster position="top-right" richColors closeButton />
      <PwaRegistration />
    </QueryClientProvider>
  );
}
