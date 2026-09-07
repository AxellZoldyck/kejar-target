import type { Metadata } from "next";
import { CommissionManagement } from "@/features/commissions/commission-management";
export const metadata:Metadata={title:"Komisi"};
export default function Page(){return <CommissionManagement/>;}
