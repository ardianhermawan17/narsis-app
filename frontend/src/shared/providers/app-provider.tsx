'use client'

import type { ReactNode } from 'react'
import { Provider as UrqlProvider } from 'urql'
import { urqlClient } from '@/shared/lib/urql-client'
import { ShadcnProvider } from '@/shared/providers/shadcn-provider'

interface AppProviderProps {
  children: ReactNode
}

export function AppProvider({ children }: AppProviderProps) {
  return (
    <ShadcnProvider>
      <UrqlProvider value={urqlClient}>{children}</UrqlProvider>
    </ShadcnProvider>
  )
}
