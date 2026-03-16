import React from "react"
import { MainLayoutNavbar } from "@shared/components/template/main-layout-navbar"

export default function MainLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  return (
    <div className="min-h-screen bg-background pb-20 md:pb-0 md:pl-64">
      <MainLayoutNavbar />
      <main>{children}</main>
    </div>
  )
}
