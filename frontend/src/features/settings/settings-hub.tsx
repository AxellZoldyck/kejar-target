"use client";

import { Building2, CreditCard, Package, Target, Users } from "lucide-react";
import { useState } from "react";

import { PageHeader } from "@/components/data-display/page-header";
import { Button } from "@/components/ui/button";
import { useSession } from "@/features/auth/session-provider";
import { cn } from "@/lib/utils";
import { CompanySettings } from "./company-settings";
import { ProductSettings } from "./product-settings";
import { SubscriptionSettings } from "./subscription-settings";
import { TargetSettings } from "./target-settings";
import { TeamSettings } from "./team-settings";

const tabs=[{id:"company",label:"Perusahaan",icon:Building2},{id:"teams",label:"Tim",icon:Users},{id:"products",label:"Produk",icon:Package},{id:"targets",label:"Target",icon:Target},{id:"subscription",label:"Langganan",icon:CreditCard}] as const;
type Tab=typeof tabs[number]["id"];
export function SettingsHub(){const [tab,setTab]=useState<Tab>("company");const session=useSession();const writable=session.subscription?.can_mutate??false;return <div className="space-y-6"><PageHeader eyebrow="Konfigurasi" title="Pengaturan workspace" description="Kelola fondasi perusahaan yang dipakai oleh seluruh perhitungan."/><div className="flex gap-2 overflow-x-auto pb-1">{tabs.map(item=>{const Icon=item.icon;return <Button key={item.id} variant={tab===item.id?"default":"outline"} onClick={()=>setTab(item.id)} className={cn("shrink-0")}><Icon/>{item.label}</Button>})}</div>{tab==="company"&&<CompanySettings writable={writable}/>} {tab==="teams"&&<TeamSettings writable={writable}/>} {tab==="products"&&<ProductSettings writable={writable}/>} {tab==="targets"&&<TargetSettings writable={writable}/>} {tab==="subscription"&&<SubscriptionSettings/>}</div>}
