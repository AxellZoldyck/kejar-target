import type { Metadata } from "next";
import { PaymentsAdmin } from "@/features/admin/admin-lists";
export const metadata:Metadata={title:"Payments"};export default function Page(){return <PaymentsAdmin/>;}
