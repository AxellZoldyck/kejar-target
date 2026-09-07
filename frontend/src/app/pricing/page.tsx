import { Check } from "lucide-react";
import Link from "next/link";

import { PublicHeader } from "@/components/public-header";
import { buttonVariants } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { cn } from "@/lib/utils";

export default function PricingPage() {
  return <div className="min-h-screen"><PublicHeader /><main className="mx-auto max-w-4xl px-4 py-16 sm:px-6"><div className="text-center"><p className="text-xs font-bold uppercase tracking-[.18em] text-primary">Harga sederhana</p><h1 className="mt-3 text-4xl font-black tracking-tight">Mulai dengan Starter</h1><p className="mx-auto mt-4 max-w-xl text-muted-foreground">Uji seluruh alur selama 3 hari. Harga komersial dapat ditetapkan saat gateway pembayaran diaktifkan.</p></div><Card className="mx-auto mt-10 max-w-lg overflow-hidden border-primary/25 shadow-xl"><div className="bg-primary p-6 text-primary-foreground"><p className="font-semibold">Starter</p><p className="mt-2 text-3xl font-black">Trial 3 hari</p><p className="mt-1 text-sm opacity-80">Tanpa kartu kredit</p></div><div className="space-y-4 p-6 text-sm">{["Workspace perusahaan dan tim","Manajemen sales dan produk","Target mingguan dan bulanan","Validasi aktivitas dan riwayat status","Leaderboard dan kalkulasi komisi","Mode baca-saja ketika langganan berakhir"].map(item=><p className="flex gap-3" key={item}><Check className="size-5 shrink-0 text-success" />{item}</p>)}<Link className={cn(buttonVariants({size:"lg"}),"mt-4 w-full")} href="/register">Mulai trial</Link></div></Card></main></div>;
}
