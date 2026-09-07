import type { Metadata } from "next";

import { Brand } from "@/components/brand";
import { CheckoutPanel } from "@/features/subscription/checkout-panel";

export const metadata: Metadata = { title: "Aktivasi paket" };

export default function CheckoutPage() {
  return <main className="surface-grid min-h-screen px-4 py-10"><div className="mx-auto max-w-xl"><Brand className="mb-8 justify-center" /><CheckoutPanel /></div></main>;
}
