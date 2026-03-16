import { urqlClient } from '@/shared/lib/urql-client'
import { LOGIN_MUTATION, REGISTER_MUTATION, LOGOUT_MUTATION, ME_QUERY } from '../gql/auth.gql'
import type { User, AuthTokenPair } from '@/shared/types/entities'

export const authApi = {
  login: (vars: { usernameOrEmail: string; password: string }) =>
    urqlClient.mutation<{ login: AuthTokenPair }>(LOGIN_MUTATION, vars).toPromise(),

  register: (vars: { username: string; email: string; password: string }) =>
    urqlClient.mutation<{ register: User }>(REGISTER_MUTATION, vars).toPromise(),

  logout: (refreshToken: string) =>
    urqlClient.mutation<{ logout: boolean }>(LOGOUT_MUTATION, { refreshToken }).toPromise(),

  me: () => urqlClient.query<{ me: User }>(ME_QUERY, {}).toPromise(),
}
