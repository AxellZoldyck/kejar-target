import type { Metadata } from "next";
import { ActivityForm } from "@/features/activities/activity-form";
export const metadata: Metadata = { title: "Edit Aktivitas" };
export default async function Page({params}:{params:Promise<{id:string}>}){const {id}=await params;return <ActivityForm activityId={id}/>;}
