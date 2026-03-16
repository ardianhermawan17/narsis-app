import { MainLayoutNavbarDesktop } from "./main-layout-navbar-desktop"
import { MainLayoutNavbarMobile } from "./main-layout-navbar-mobile"
import { useMainLayoutNavbar } from "@shared/components/template/main-layout-navbar/use-main-layout-navbar"

export function MainLayoutNavbar({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const {} = useMainLayoutNavbar()
  return (
    <>
      <MainLayoutNavbarDesktop>
        {children}
      </MainLayoutNavbarDesktop>
      <MainLayoutNavbarMobile >
        {children}
      </MainLayoutNavbarMobile>
    </>
  )
}