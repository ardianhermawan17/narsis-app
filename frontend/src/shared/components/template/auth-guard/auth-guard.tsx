'use client'

import { useEffect, useRef } from 'react'
import type { ReactNode } from 'react'
import { usePathname, useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { useAuthStore } from '@/features/auth/store/auth.store'

interface AuthGuardProps {
    children: ReactNode
}

export function AuthGuard({ children }: AuthGuardProps) {
    const router = useRouter()
    const pathname = usePathname()
    const isAuthenticated = useAuthStore((s) => s.isAuthenticated)
    const hasWarned = useRef(false)

    useEffect(() => {
        if (isAuthenticated) {
            hasWarned.current = false
            return
        }

        if (!hasWarned.current) {
            toast.warning('Please login first')
            hasWarned.current = true
        }

        if (pathname !== '/auth') {
            router.replace('/auth')
        }
    }, [isAuthenticated, pathname, router])

    if (!isAuthenticated && pathname !== '/auth') {
        return null
    }

    return <>{children}</>
}