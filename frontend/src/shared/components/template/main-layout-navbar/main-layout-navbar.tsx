'use client'

import { MainLayoutNavbarDesktop } from './main-layout-navbar-desktop'
import { MainLayoutNavbarMobile } from './main-layout-navbar-mobile'
import { useMainLayoutNavbar } from './use-main-layout-navbar'

export function MainLayoutNavbar() {
  const navProps = useMainLayoutNavbar()

  return (
    <>
      <div className="hidden md:block">
        <MainLayoutNavbarDesktop {...navProps} />
      </div>
      <div className="md:hidden">
        <MainLayoutNavbarMobile {...navProps} />
      </div>
    </>
  )
}