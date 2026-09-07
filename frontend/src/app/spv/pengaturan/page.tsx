import type { Metadata } from "next";
import { SettingsHub } from "@/features/settings/settings-hub";
export const metadata:Metadata={title:"Pengaturan"};
export default function Page(){return <SettingsHub/>;}
