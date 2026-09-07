import type { Metadata } from "next";
import { EarningsView } from "@/features/commissions/earnings-view";
export const metadata:Metadata={title:"Pendapatan"};
export default function Page(){return <EarningsView/>;}
