import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function NotFound() {
  return (
    <main className="grid min-h-screen place-items-center px-4">
      <div className="max-w-md text-center">
        <p className="text-sm font-semibold text-primary">404</p>
        <h1 className="mt-2 text-3xl font-bold tracking-tight">Halaman tidak ditemukan</h1>
        <p className="mt-3 text-muted-foreground">Alamat mungkin berubah atau Anda tidak memiliki akses ke resource tersebut.</p>
        <Button asChild className="mt-6"><Link href="/"><ArrowLeft className="size-4" /> Kembali ke beranda</Link></Button>
      </div>
    </main>
  );
}
