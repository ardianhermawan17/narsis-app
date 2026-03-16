'use client'

import { AuthForm as AuthFormUI } from './auth-form'
import { useAuthForm } from './use-auth-form'

export function AuthForm() {
  const props = useAuthForm()
  return <AuthFormUI {...props} />
}
