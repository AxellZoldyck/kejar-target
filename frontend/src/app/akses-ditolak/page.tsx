import Link from "next/link";
import { ShieldAlert } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function AccessDeniedPage() {
  return <main className="grid min-h-screen place-items-center px-4"><div className="max-w-md text-center"><ShieldAlert className="mx-auto size-12 text-destructive" /><h1 className="mt-5 text-2xl font-bold">Akses tidak tersedia</h1><p className="mt-2 text-sm leading-6 text-muted-foreground">Akun Anda tidak memiliki izin untuk membuka halaman ini.</p><Button asChild className="mt-6"><Link href="/">Kembali</Link></Button></div></main>;
}
