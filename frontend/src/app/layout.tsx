import { Geist, Geist_Mono } from "next/font/google"

import "./globals.css"
import { cn } from "@shared/lib";
import { LibraryProvider } from "@shared/providers"
import { AuthGuard } from "@shared/components/template/auth-guard"
import { MainLayoutNavbar } from "@shared/components/template/main-layout-navbar"

const fontSans = Geist({
  subsets: ["latin"],
  variable: "--font-sans",
})

const fontMono = Geist_Mono({
  subsets: ["latin"],
  variable: "--font-mono",
})

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  return (
    <html
      lang="en"
      suppressHydrationWarning
      className={cn("antialiased", fontMono.variable, "font-sans", fontSans.variable)}
    >
      <body>
        <LibraryProvider>
          <AuthGuard>
            <MainLayoutNavbar>
              {children}
            </MainLayoutNavbar>
          </AuthGuard>
        </LibraryProvider>
      </body>
    </html>
  )
}
