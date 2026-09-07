"use client";

import { useQuery } from "@tanstack/react-query";
import { createContext, useContext } from "react";

import { authApi } from "@/lib/api/endpoints/auth";
import type { Session } from "@/types/auth";

const SessionContext = createContext<Session | null>(null);

export function SessionProvider({ initialSession, children }: { initialSession: Session; children: React.ReactNode }) {
  const { data } = useQuery({
    queryKey: ["session"],
    queryFn: authApi.me,
    initialData: initialSession,
    staleTime: 60_000,
  });

  return <SessionContext.Provider value={data}>{children}</SessionContext.Provider>;
}

export function useSession() {
  const session = useContext(SessionContext);
  if (!session) throw new Error("useSession harus digunakan di dalam SessionProvider.");
  return session;
}
