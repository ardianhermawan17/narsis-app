import { cacheExchange, createClient, fetchExchange } from '@urql/core'
import { authExchange } from '@urql/exchange-auth'
import { useAuthStore } from '@/features/auth/store/auth.store'
import type { AuthTokenPair } from '@/shared/types/entities'

const GRAPHQL_URL = process.env.NEXT_PUBLIC_GRAPHQL_URL ?? 'http://localhost:8080/graphql'

const REFRESH_TOKEN_MUTATION = `
  mutation RefreshToken($refreshToken: String!) {
    refreshToken(refreshToken: $refreshToken) {
      accessToken
      refreshToken
      tokenType
      expiresIn
    }
  }
`

const redirectToLogin = () => {
  if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
    window.location.replace('/login')
  }
}

const clearAuthAndRedirect = () => {
  const { clearAuth } = useAuthStore.getState()
  clearAuth()
  redirectToLogin()
}

export const urqlClient = createClient({
  url: GRAPHQL_URL,
  exchanges: [
    cacheExchange,
    authExchange(async (utils) => ({
      addAuthToOperation(operation) {
        const token = useAuthStore.getState().accessToken
        if (!token) return operation
        return utils.appendHeaders(operation, {
          Authorization: `Bearer ${token}`,
        })
      },

      didAuthError(error) {
        return error.graphQLErrors.some((e) => e.extensions?.code === 'UNAUTHENTICATED')
      },

      async refreshAuth() {
        const { refreshToken, setAuth } = useAuthStore.getState()
        if (!refreshToken) {
          clearAuthAndRedirect()
          return
        }

        try {
          const result = await utils.mutate<{ refreshToken: AuthTokenPair }>(
            REFRESH_TOKEN_MUTATION,
            { refreshToken }
          )

          if (result.data?.refreshToken) {
            setAuth(result.data.refreshToken)
            return
          }

          clearAuthAndRedirect()
        } catch {
          clearAuthAndRedirect()
        }
      },
    })),
    fetchExchange,
  ],
})
