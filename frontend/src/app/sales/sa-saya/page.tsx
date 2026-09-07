import type { Metadata } from "next";
import { MyActivitiesView } from "@/features/activities/activity-list";
export const metadata: Metadata = { title: "Aktivitas Saya" };
export default function Page(){return <MyActivitiesView/>;}
