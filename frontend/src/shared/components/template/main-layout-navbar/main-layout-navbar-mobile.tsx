'use client'

import { motion } from 'framer-motion'
import { Home, PlusSquare, User, type LucideIcon } from 'lucide-react'
import { Avatar, AvatarFallback, AvatarImage } from '@/shared/components/ui/avatar'
import { DarkModeToggle } from '@/shared/components/template/dark-mode-toggle/dark-mode-toggle'
import type { UseMainLayoutNavbarReturn } from './use-main-layout-navbar'

const ICON_MAP: Record<string, LucideIcon> = {
  Home,
  PlusSquare,
  User,
}

export function MainLayoutNavbarMobile(props: UseMainLayoutNavbarReturn) {
  return (
    <motion.nav
      initial={{ y: 100, opacity: 0 }}
      animate={{ y: 0, opacity: 1 }}
      transition={{ type: 'spring', stiffness: 360, damping: 28 }}
      className="fixed bottom-4 left-1/2 z-50 flex w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 items-center justify-between rounded-full border border-stone-300/80 bg-stone-200/90 px-3 py-2 text-stone-900 shadow-xl backdrop-blur-md dark:border-blue-300/20 dark:bg-slate-900/90 dark:text-blue-100 md:hidden"
      aria-label="Main navigation"
    >
      {props.navItems.map((item) => {
        const Icon = ICON_MAP[item.icon]
        const isProfile = item.id === 'profile'

        return (
          <div key={item.id} className="flex items-center">
            {isProfile && (
              <DarkModeToggle variant="mobile" className="mr-1 size-10 border-stone-300/90 bg-stone-100 text-stone-900 hover:bg-stone-200 dark:border-blue-300/20 dark:bg-slate-800 dark:text-blue-100 dark:hover:bg-slate-700" />
            )}
            <motion.button
              type="button"
              whileTap={{ scale: 0.85 }}
              onClick={item.onClick}
              className="relative flex size-12 items-center justify-center overflow-hidden rounded-full"
              aria-label={item.label}
              aria-current={item.isActive ? 'page' : undefined}
            >
              {item.isActive && (
                <motion.div
                  layoutId="navbar-mobile-active"
                  className="absolute inset-0 rounded-full bg-stone-900/10 dark:bg-blue-200/20"
                  transition={{ type: 'spring', stiffness: 380, damping: 30 }}
                />
              )}

              <span className="relative z-10">
                {isProfile ? (
                  <Avatar className="size-7 border border-stone-400/40 dark:border-blue-200/30">
                    <AvatarImage src={props.avatarSrc} alt="Profile avatar" />
                    <AvatarFallback>{props.avatarFallback}</AvatarFallback>
                  </Avatar>
                ) : (
                  <Icon className="size-5" />
                )}
              </span>
            </motion.button>
          </div>
        )
      })}
    </motion.nav>
  )
}