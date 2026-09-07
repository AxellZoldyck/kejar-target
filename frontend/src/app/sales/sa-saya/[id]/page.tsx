import type { Metadata } from "next";
import { ActivityDetail } from "@/features/activities/activity-detail";
export const metadata: Metadata = { title: "Detail Aktivitas" };
export default async function Page({params}:{params:Promise<{id:string}>}){const {id}=await params;return <ActivityDetail id={id}/>;}
