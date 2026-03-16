import { create } from 'zustand'
import { immer } from 'zustand/middleware/immer'
import { persist } from 'zustand/middleware'
import type { User, AuthTokenPair } from '@/shared/types/entities'

interface AuthState {
  user: User | null
  accessToken: string | null
  refreshToken: string | null
  tokenType: string | null
  expiresIn: number | null
  isAuthenticated: boolean
  setAuth: (tokens: AuthTokenPair) => void
  setUser: (user: User) => void
  clearAuth: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    immer((set) => ({
      user: null,
      accessToken: null,
      refreshToken: null,
      tokenType: null,
      expiresIn: null,
      isAuthenticated: false,

      setAuth: (tokens) =>
        set((s) => {
          s.accessToken = tokens.accessToken
          s.refreshToken = tokens.refreshToken
          s.tokenType = tokens.tokenType
          s.expiresIn = tokens.expiresIn
          s.isAuthenticated = true
        }),

      setUser: (user) =>
        set((s) => {
          s.user = user
        }),

      clearAuth: () =>
        set((s) => {
          s.user = null
          s.accessToken = null
          s.refreshToken = null
          s.tokenType = null
          s.expiresIn = null
          s.isAuthenticated = false
        }),
    })),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        accessToken: state.accessToken,
        refreshToken: state.refreshToken,
        tokenType: state.tokenType,
        expiresIn: state.expiresIn,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
)
