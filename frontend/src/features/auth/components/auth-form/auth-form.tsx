import { Loader2 } from 'lucide-react'
import type { UseFormReturn } from 'react-hook-form'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Input } from '@/shared/components/ui/input'
import { Label } from '@/shared/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shared/components/ui/tabs'
import type { LoginFormValues, RegisterFormValues } from '../../types'

interface AuthFormProps {
    tab: 'login' | 'register'
    setTab: (tab: 'login' | 'register') => void
    isSubmitting: boolean
    serverError: string | null
    loginForm: UseFormReturn<LoginFormValues>
    registerForm: UseFormReturn<RegisterFormValues>
    handleLogin: (values: LoginFormValues) => Promise<void>
    handleRegister: (values: RegisterFormValues) => Promise<void>
}

export function AuthForm({
    tab,
    setTab,
    isSubmitting,
    serverError,
    loginForm,
    registerForm,
    handleLogin,
    handleRegister,
}: AuthFormProps) {
    return (
        <Card className="w-full max-w-md">
            <CardHeader>
                <CardTitle className="text-center text-2xl">Welcome</CardTitle>
            </CardHeader>
            <CardContent>
                <Tabs value={tab} onValueChange={(value) => setTab(value as 'login' | 'register')}>
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="login">Login</TabsTrigger>
                        <TabsTrigger value="register">Register</TabsTrigger>
                    </TabsList>

                    <TabsContent value="login" className="mt-4">
                        <form onSubmit={loginForm.handleSubmit(handleLogin)} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="usernameOrEmail">Username or email</Label>
                                <Input
                                    id="usernameOrEmail"
                                    placeholder="you@example.com"
                                    {...loginForm.register('usernameOrEmail')}
                                />
                                {loginForm.formState.errors.usernameOrEmail && (
                                    <p className="text-sm text-destructive">
                                        {loginForm.formState.errors.usernameOrEmail.message}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="loginPassword">Password</Label>
                                <Input id="loginPassword" type="password" {...loginForm.register('password')} />
                                {loginForm.formState.errors.password && (
                                    <p className="text-sm text-destructive">
                                        {loginForm.formState.errors.password.message}
                                    </p>
                                )}
                            </div>

                            <Button type="submit" className="w-full" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="mr-2 size-4 animate-spin" />}
                                Login
                            </Button>

                            {serverError && <p className="text-sm text-destructive">{serverError}</p>}
                        </form>
                    </TabsContent>

                    <TabsContent value="register" className="mt-4">
                        <form onSubmit={registerForm.handleSubmit(handleRegister)} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="username">Username</Label>
                                <Input id="username" placeholder="yourusername" {...registerForm.register('username')} />
                                {registerForm.formState.errors.username && (
                                    <p className="text-sm text-destructive">
                                        {registerForm.formState.errors.username.message}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" placeholder="you@example.com" {...registerForm.register('email')} />
                                {registerForm.formState.errors.email && (
                                    <p className="text-sm text-destructive">{registerForm.formState.errors.email.message}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="registerPassword">Password</Label>
                                <Input
                                    id="registerPassword"
                                    type="password"
                                    {...registerForm.register('password')}
                                />
                                {registerForm.formState.errors.password && (
                                    <p className="text-sm text-destructive">
                                        {registerForm.formState.errors.password.message}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="confirmPassword">Confirm password</Label>
                                <Input
                                    id="confirmPassword"
                                    type="password"
                                    {...registerForm.register('confirmPassword')}
                                />
                                {registerForm.formState.errors.confirmPassword && (
                                    <p className="text-sm text-destructive">
                                        {registerForm.formState.errors.confirmPassword.message}
                                    </p>
                                )}
                            </div>

                            <Button type="submit" className="w-full" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="mr-2 size-4 animate-spin" />}
                                Register
                            </Button>

                            {serverError && <p className="text-sm text-destructive">{serverError}</p>}
                        </form>
                    </TabsContent>
                </Tabs>
            </CardContent>
        </Card>
    )
}