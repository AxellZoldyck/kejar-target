import type { Metadata, Viewport } from "next";

import { AppProviders } from "./providers";
import "./globals.css";

const appName = process.env.NEXT_PUBLIC_APP_NAME || "Kejar Target";

export const metadata: Metadata = {
  title: {
    default: `${appName} — Sales tracker yang transparan`,
    template: `%s | ${appName}`,
  },
  description:
    "Kelola aktivitas sales, target, leaderboard, dan komisi dalam satu sumber data tervalidasi.",
  applicationName: appName,
  manifest: "/manifest.webmanifest",
};

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  viewportFit: "cover",
  themeColor: "#1d4ed8",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id">
      <body>
        <AppProviders>{children}</AppProviders>
      </body>
    </html>
  );
}
