import type { Metadata } from "next";
import { ActivityForm } from "@/features/activities/activity-form";
export const metadata: Metadata = { title: "Tambah Aktivitas" };
export default function Page(){return <ActivityForm/>;}
