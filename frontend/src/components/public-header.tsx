import Link from "next/link";

import { Brand } from "@/components/brand";
import { buttonVariants } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export function PublicHeader() {
  return (
    <header className="border-b bg-card/85 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
        <Brand />
        <nav className="flex items-center gap-2" aria-label="Navigasi utama">
          <Link className="hidden px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground sm:block" href="/pricing">Harga</Link>
          <Link className={cn(buttonVariants({ variant: "ghost", size: "sm" }))} href="/login">Masuk</Link>
          <Link className={cn(buttonVariants({ size: "sm" }))} href="/register">Mulai gratis</Link>
        </nav>
      </div>
    </header>
  );
}
