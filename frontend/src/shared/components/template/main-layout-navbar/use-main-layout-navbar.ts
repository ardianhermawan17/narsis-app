'use client'

import { useMemo, useState } from 'react'
import { useMediaQuery } from 'react-responsive'
import { usePathname, useRouter } from 'next/navigation'
import { useAuthStore } from '@/features/auth/store/auth.store'
import { useUIStore } from '@/shared/store/ui.store'

export interface MainLayoutNavbarItem {
  id: 'home' | 'create' | 'profile'
  label: string
  icon: 'Home' | 'PlusSquare' | 'User'
  href?: string
  isActive: boolean
  onClick: () => void
}

export interface UseMainLayoutNavbarReturn {
  isDesktop: boolean
  isMobileMenuOpen: boolean
  logoText: string
  avatarFallback: string
  avatarSrc?: string
  navItems: MainLayoutNavbarItem[]
  toggleMobileMenu: () => void
  closeMobileMenu: () => void
}

const BASE_ITEMS: Array<Omit<MainLayoutNavbarItem, 'isActive' | 'onClick'>> = [
  { id: 'home', label: 'Home', icon: 'Home', href: '/feed' },
  { id: 'create', label: 'Create', icon: 'PlusSquare' },
  { id: 'profile', label: 'Profile', icon: 'User', href: '/profile' },
]

export function useMainLayoutNavbar(): UseMainLayoutNavbarReturn {
  const router = useRouter()
  const pathname = usePathname()

  const user = useAuthStore((state) => state.user)
  const isCreateModalOpen = useUIStore((state) => state.isCreateModalOpen)
  const openCreateModal = useUIStore((state) => state.openCreateModal)

  const isDesktop = useMediaQuery({ minWidth: 768 })
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)

  const avatarFallback = useMemo(() => {
    const baseText = user?.displayName ?? user?.username ?? user?.email ?? 'Narsis'
    return baseText.trim().charAt(0).toUpperCase() || 'N'
  }, [user?.displayName, user?.email, user?.username])

  const avatarSrc = undefined

  const navItems = useMemo<MainLayoutNavbarItem[]>(() => {
    return BASE_ITEMS.map((item) => {
      const isHomeActive = item.id === 'home' && pathname.startsWith('/feed')
      const isProfileActive = item.id === 'profile' && pathname.startsWith('/profile')
      const isCreateActive = item.id === 'create' && isCreateModalOpen
      const isActive = isHomeActive || isProfileActive || isCreateActive

      const onClick = () => {
        if (item.id === 'create') {
          openCreateModal()
          setIsMobileMenuOpen(false)
          return
        }

        if (item.href) {
          router.push(item.href)
          setIsMobileMenuOpen(false)
        }
      }

      return {
        ...item,
        isActive,
        onClick,
      }
    })
  }, [isCreateModalOpen, openCreateModal, pathname, router])

  return {
    isDesktop,
    isMobileMenuOpen,
    logoText: 'Narsis',
    avatarFallback,
    avatarSrc,
    navItems,
    toggleMobileMenu: () => setIsMobileMenuOpen((prev) => !prev),
    closeMobileMenu: () => setIsMobileMenuOpen(false),
  }
}