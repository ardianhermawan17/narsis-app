import { useState } from 'react'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useAuth } from '../../hooks/use-auth'
import type { LoginFormValues, RegisterFormValues } from '../../types'

const loginSchema = z.object({
    usernameOrEmail: z.string().min(1, 'Username or email is required'),
    password: z.string().min(6, 'Password must be at least 6 characters'),
})

const registerSchema = z
    .object({
        username: z.string().min(3, 'Username must be at least 3 characters'),
        email: z.string().email('Enter a valid email'),
        password: z.string().min(6, 'Password must be at least 6 characters'),
        confirmPassword: z.string(),
    })
    .refine((d) => d.password === d.confirmPassword, {
        message: 'Passwords do not match',
        path: ['confirmPassword'],
    })

export function useAuthForm() {
    const { login, register } = useAuth()
    const [tab, setTab] = useState<'login' | 'register'>('login')
    const [isSubmitting, setIsSubmitting] = useState(false)
    const [serverError, setServerError] = useState<string | null>(null)

    const loginForm = useForm<LoginFormValues>({
        resolver: zodResolver(loginSchema),
        defaultValues: {
            usernameOrEmail: '',
            password: '',
        },
    })

    const registerForm = useForm<RegisterFormValues>({
        resolver: zodResolver(registerSchema),
        defaultValues: {
            username: '',
            email: '',
            password: '',
            confirmPassword: '',
        },
    })

    const handleLogin = async (values: LoginFormValues) => {
        setIsSubmitting(true)
        setServerError(null)
        try {
            await login(values)
        } catch (e: unknown) {
            setServerError(e instanceof Error ? e.message : 'Login failed')
        } finally {
            setIsSubmitting(false)
        }
    }

    const handleRegister = async (values: RegisterFormValues) => {
        setIsSubmitting(true)
        setServerError(null)
        try {
            await register(values)
        } catch (e: unknown) {
            setServerError(e instanceof Error ? e.message : 'Registration failed')
        } finally {
            setIsSubmitting(false)
        }
    }

    return {
        tab,
        setTab,
        isSubmitting,
        serverError,
        loginForm,
        registerForm,
        handleLogin,
        handleRegister,
    }
}