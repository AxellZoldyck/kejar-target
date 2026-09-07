import type { Metadata } from "next";
import { ValidationQueue } from "@/features/activities/validation-queue";
export const metadata: Metadata = { title: "Validasi Aktivitas" };
export default function Page(){return <ValidationQueue/>;}
