import type { Metadata } from "next";
import { SalesManagement } from "@/features/organization/sales-management";
export const metadata: Metadata={title:"Kelola Sales"};
export default function Page(){return <SalesManagement/>;}
