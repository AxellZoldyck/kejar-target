import type { Metadata } from "next";
import { CompaniesAdmin } from "@/features/admin/admin-lists";
export const metadata:Metadata={title:"Companies"};export default function Page(){return <CompaniesAdmin/>;}
