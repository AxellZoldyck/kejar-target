import type { Metadata } from "next";

import { Brand } from "@/components/brand";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { RegisterForm } from "@/features/auth/auth-forms";

export const metadata: Metadata = { title: "Daftar" };

export default function RegisterPage() {
  return <main className="surface-grid grid min-h-screen place-items-center px-4 py-10"><div className="w-full max-w-2xl"><Brand className="mb-8 justify-center" /><Card className="shadow-xl"><CardHeader><CardTitle className="text-2xl">Buat workspace perusahaan</CardTitle><p className="text-sm text-muted-foreground">Akun pertama menjadi supervisor dan satu tim awal langsung tersedia.</p></CardHeader><CardContent><RegisterForm /></CardContent></Card></div></main>;
}
