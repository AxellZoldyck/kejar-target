"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { LogOut, Menu, Plus, X } from "lucide-react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useState } from "react";

import { Brand } from "@/components/brand";
import { Button } from "@/components/ui/button";
import { authApi } from "@/lib/api/endpoints/auth";
import { cn } from "@/lib/utils";
import type { Role, Session } from "@/types/auth";
import { navigation, type NavItem } from "./nav-config";

function NavigationLink({ item, onNavigate }: { item: NavItem; onNavigate?: () => void }) {
  const pathname = usePathname();
  const active = pathname === item.href || (item.href.split("/").length > 2 && pathname.startsWith(`${item.href}/`));
  const Icon = item.icon;
  return (
    <Link href={item.href} onClick={onNavigate} className={cn("flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors", active ? "bg-primary text-primary-foreground shadow-sm" : "text-muted-foreground hover:bg-accent hover:text-foreground")}>
      <Icon className="size-4.5" aria-hidden="true" />{item.label}
    </Link>
  );
}

function SubscriptionBanner({ session }: { session: Session }) {
  if (!session.subscription || session.subscription.can_mutate) return null;
  return (
    <div className="border-b border-amber-200 bg-amber-50 px-4 py-2 text-center text-xs font-medium text-amber-900">
      Langganan tidak aktif. Data tetap aman dalam mode baca-saja. <Link className="underline" href="/checkout">Lihat opsi aktivasi</Link>
    </div>
  );
}

export function RoleShell({ role, session, children }: { role: Role; session: Session; children: React.ReactNode }) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const router = useRouter();
  const queryClient = useQueryClient();
  const items = navigation[role];
  const logout = useMutation({
    mutationFn: authApi.logout,
    onSettled: () => {
      queryClient.clear();
      router.replace("/login");
      router.refresh();
    },
  });

  return (
    <div className="min-h-screen bg-background">
      <SubscriptionBanner session={session} />
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 border-r bg-card lg:flex lg:flex-col">
        <div className="flex h-16 items-center border-b px-5"><Brand /></div>
        <nav className="flex-1 space-y-1 p-3">{items.map((item) => <NavigationLink item={item} key={item.href} />)}</nav>
        <div className="border-t p-4">
          <p className="truncate text-sm font-semibold">{session.user.name}</p>
          <p className="truncate text-xs text-muted-foreground">{session.company?.name ?? "System workspace"}</p>
          <Button variant="ghost" className="mt-3 w-full justify-start text-muted-foreground" onClick={() => logout.mutate()} disabled={logout.isPending}>
            <LogOut /> {logout.isPending ? "Keluar…" : "Keluar"}
          </Button>
        </div>
      </aside>

      <div className="lg:pl-64">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b bg-card/95 px-4 backdrop-blur md:px-6">
          <div className="flex items-center gap-3 lg:hidden">
            <Button variant="ghost" size="icon" aria-label="Buka navigasi" onClick={() => setMobileOpen(true)}><Menu /></Button>
            <Brand compact />
          </div>
          <div className="hidden lg:block">
            <p className="text-xs font-medium uppercase tracking-[0.14em] text-muted-foreground">{role === "super_admin" ? "Super Admin" : role === "spv" ? "Supervisor" : "Sales workspace"}</p>
          </div>
          <div className="text-right"><p className="text-sm font-semibold">{session.user.name}</p><p className="text-xs text-muted-foreground">{session.user.email}</p></div>
        </header>
        <main className={cn("mx-auto w-full max-w-[1440px] px-4 py-6 md:px-6 md:py-8", role === "sales" && "pb-28 lg:pb-8")}>{children}</main>
      </div>

      {mobileOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <button className="absolute inset-0 bg-foreground/35" aria-label="Tutup navigasi" onClick={() => setMobileOpen(false)} />
          <aside className="relative flex h-full w-[min(20rem,88vw)] flex-col bg-card shadow-2xl">
            <div className="flex h-16 items-center justify-between border-b px-5"><Brand /><Button variant="ghost" size="icon" onClick={() => setMobileOpen(false)} aria-label="Tutup"><X /></Button></div>
            <nav className="flex-1 space-y-1 p-3">{items.map((item) => <NavigationLink item={item} key={item.href} onNavigate={() => setMobileOpen(false)} />)}</nav>
            <div className="border-t p-4"><Button variant="outline" className="w-full" onClick={() => logout.mutate()}><LogOut /> Keluar</Button></div>
          </aside>
        </div>
      )}

      {role === "sales" && <SalesBottomNav activityLabel={session.company?.activity_label ?? "SA"} writable={session.subscription?.can_mutate ?? false} />}
    </div>
  );
}

function SalesBottomNav({ activityLabel, writable }: { activityLabel: string; writable: boolean }) {
  const pathname = usePathname();
  const items = navigation.sales.slice(0, 4);
  return (
    <nav className="safe-bottom fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t bg-card/95 px-2 pt-2 shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden">
      {items.slice(0, 2).map((item) => <MobileLink item={item} active={pathname.startsWith(item.href)} key={item.href} />)}
      {writable ? <Link href="/sales/sa-saya/baru" className="-mt-5 flex flex-col items-center gap-1 text-[10px] font-semibold text-primary"><span className="grid size-12 place-items-center rounded-full bg-primary text-primary-foreground shadow-lg ring-4 ring-card"><Plus className="size-5" /></span>Tambah {activityLabel}</Link> : <span aria-disabled="true" className="-mt-5 flex flex-col items-center gap-1 text-[10px] font-semibold text-muted-foreground"><span className="grid size-12 place-items-center rounded-full bg-secondary text-muted-foreground shadow ring-4 ring-card"><Plus className="size-5" /></span>Baca saja</span>}
      {items.slice(2, 4).map((item) => <MobileLink item={item} active={pathname.startsWith(item.href)} key={item.href} />)}
    </nav>
  );
}

function MobileLink({ item, active }: { item: NavItem; active: boolean }) {
  const Icon = item.icon;
  return <Link href={item.href} className={cn("flex flex-col items-center gap-1 rounded-lg py-1 text-[10px] font-medium", active ? "text-primary" : "text-muted-foreground")}><Icon className="size-5" />{item.label}</Link>;
}
