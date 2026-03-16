import { useRouter } from 'next/navigation'
import { authApi } from '../api/auth.api'
import { useAuthStore } from '../store/auth.store'
import type { LoginFormValues, RegisterFormValues } from '../types'

export function useAuth() {
  const router = useRouter()
  const { user, isAuthenticated, accessToken, refreshToken, setAuth, setUser, clearAuth } =
    useAuthStore()

  const login = async (values: LoginFormValues): Promise<void> => {
    const { data, error } = await authApi.login(values)
    if (error) throw new Error(error.message)
    if (!data?.login) throw new Error('Login failed: no token returned')

    setAuth(data.login)

    const { data: meData } = await authApi.me()
    if (meData?.me) setUser(meData.me)

    router.replace('/feed')
  }

  const register = async (values: RegisterFormValues): Promise<void> => {
    const { data, error } = await authApi.register({
      username: values.username,
      email: values.email,
      password: values.password,
    })
    if (error) throw new Error(error.message)
    if (!data?.register) throw new Error('Registration failed')

    await login({ usernameOrEmail: values.email, password: values.password })
  }

  const logout = async (): Promise<void> => {
    if (refreshToken) {
      await authApi.logout(refreshToken)
    }
    clearAuth()
    router.replace('/login')
  }

  return { user, isAuthenticated, accessToken, login, register, logout }
}
