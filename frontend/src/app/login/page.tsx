import type { Metadata } from "next";

import { Brand } from "@/components/brand";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { LoginForm } from "@/features/auth/auth-forms";

export const metadata: Metadata = { title: "Masuk" };

export default function LoginPage() {
  return <main className="surface-grid grid min-h-screen place-items-center px-4 py-10"><div className="w-full max-w-md"><Brand className="mb-8 justify-center" /><Card className="shadow-xl"><CardHeader><CardTitle className="text-2xl">Masuk ke workspace</CardTitle><p className="text-sm text-muted-foreground">Gunakan akun yang diberikan supervisor Anda.</p></CardHeader><CardContent><LoginForm /></CardContent></Card></div></main>;
}
