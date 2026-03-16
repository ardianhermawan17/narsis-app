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

export function MainLayoutNavbarDesktop(props: UseMainLayoutNavbarReturn) {
  return (
    <motion.nav
      initial={{ x: -24, opacity: 0 }}
      animate={{ x: 0, opacity: 1 }}
      transition={{ duration: 0.3, ease: 'easeOut' }}
      className="fixed left-0 top-0 z-40 hidden h-screen w-64 border-r border-stone-300/70 bg-stone-200/85 text-stone-900 backdrop-blur-md dark:border-blue-300/15 dark:bg-slate-900/90 dark:text-slate-100 md:block"
      aria-label="Main navigation"
    >
      <div className="flex h-full flex-col px-4 py-6">
        <button
          type="button"
          className="mb-8 px-3 text-left text-2xl font-bold tracking-tight text-stone-900 dark:text-blue-100"
          onClick={props.navItems[0]?.onClick}
        >
          {props.logoText}
        </button>

        <ul className="flex flex-col gap-2">
          {props.navItems.map((item) => {
            const Icon = ICON_MAP[item.icon]
            const isProfile = item.id === 'profile'

            return (
              <li key={item.id}>
                {isProfile && (
                  <div className="">
                    <DarkModeToggle variant="desktop" className="border-stone-300/90 bg-stone-100 text-stone-900 hover:bg-stone-200 dark:border-blue-300/20 dark:bg-slate-800 dark:text-blue-100 dark:hover:bg-slate-700" />
                  </div>
                )}
                <motion.button
                  type="button"
                  onClick={item.onClick}
                  whileHover={{ x: 3 }}
                  className="relative flex w-full items-center gap-3 overflow-hidden rounded-xl px-4 py-3 text-sm font-medium text-stone-800 dark:text-slate-100"
                  aria-current={item.isActive ? 'page' : undefined}
                >
                  {item.isActive && (
                    <motion.div
                      layoutId="navbar-desktop-active"
                      className="absolute inset-0 rounded-xl bg-stone-900/10 dark:bg-blue-200/15"
                      transition={{ type: 'spring', stiffness: 400, damping: 32 }}
                    />
                  )}

                  <span className="relative z-10">
                    {isProfile ? (
                      <Avatar className="size-6 border border-black/20">
                        <AvatarImage src={props.avatarSrc} alt="Profile avatar" />
                        <AvatarFallback>{props.avatarFallback}</AvatarFallback>
                      </Avatar>
                    ) : (
                      <Icon className="size-4" />
                    )}
                  </span>
                  <span className="relative z-10">{item.label}</span>
                </motion.button>
              </li>
            )
          })}
        </ul>
      </div>
    </motion.nav>
  )
}