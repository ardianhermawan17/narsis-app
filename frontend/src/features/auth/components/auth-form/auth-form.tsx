'use client'

import { AnimatePresence, motion } from 'framer-motion'
import { Eye, EyeOff, Loader2 } from 'lucide-react'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Input } from '@/shared/components/ui/input'
import { Label } from '@/shared/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shared/components/ui/tabs'
import { useAuthForm } from "@feature/auth/components/auth-form/use-auth-form"

export function AuthForm() {
    const {
      tab,
      setTab,
      isSubmitting,
      serverError,
      loginForm,
      registerForm,
      handleLogin,
      handleRegister,
      showConfirmPassword,
      showRegisterPassword,
      showLoginPassword,
      setShowConfirmPassword,
      setShowRegisterPassword,
      setShowLoginPassword
    } = useAuthForm()

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

                    <AnimatePresence mode="wait" initial={false}>
                        {tab === 'login' ? (
                            <TabsContent value="login" className="mt-4" forceMount>
                                <motion.form
                                    key="login"
                                    initial={{ opacity: 0, y: 8 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    exit={{ opacity: 0, y: -8 }}
                                    transition={{ duration: 0.18, ease: 'easeOut' }}
                                    onSubmit={loginForm.handleSubmit(handleLogin)}
                                    className="space-y-4"
                                >
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
                                        <div className="relative">
                                            <Input
                                                id="loginPassword"
                                                type={showLoginPassword ? 'text' : 'password'}
                                                className="pr-10"
                                                {...loginForm.register('password')}
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                className="absolute right-1 top-1/2 -translate-y-1/2"
                                                onClick={() => setShowLoginPassword((v) => !v)}
                                                aria-label={showLoginPassword ? 'Hide password' : 'Show password'}
                                            >
                                                {showLoginPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                            </Button>
                                        </div>
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
                                </motion.form>
                            </TabsContent>
                        ) : (
                            <TabsContent value="register" className="mt-4" forceMount>
                                <motion.form
                                    key="register"
                                    initial={{ opacity: 0, y: 8 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    exit={{ opacity: 0, y: -8 }}
                                    transition={{ duration: 0.18, ease: 'easeOut' }}
                                    onSubmit={registerForm.handleSubmit(handleRegister)}
                                    className="space-y-4"
                                >
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
                                        <Input
                                            id="email"
                                            type="email"
                                            placeholder="you@example.com"
                                            {...registerForm.register('email')}
                                        />
                                        {registerForm.formState.errors.email && (
                                            <p className="text-sm text-destructive">{registerForm.formState.errors.email.message}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="registerPassword">Password</Label>
                                        <div className="relative">
                                            <Input
                                                id="registerPassword"
                                                type={showRegisterPassword ? 'text' : 'password'}
                                                className="pr-10"
                                                {...registerForm.register('password')}
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                className="absolute right-1 top-1/2 -translate-y-1/2"
                                                onClick={() => setShowRegisterPassword((v) => !v)}
                                                aria-label={showRegisterPassword ? 'Hide password' : 'Show password'}
                                            >
                                                {showRegisterPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                            </Button>
                                        </div>
                                        {registerForm.formState.errors.password && (
                                            <p className="text-sm text-destructive">
                                                {registerForm.formState.errors.password.message}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="confirmPassword">Confirm password</Label>
                                        <div className="relative">
                                            <Input
                                                id="confirmPassword"
                                                type={showConfirmPassword ? 'text' : 'password'}
                                                className="pr-10"
                                                {...registerForm.register('confirmPassword')}
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                className="absolute right-1 top-1/2 -translate-y-1/2"
                                                onClick={() => setShowConfirmPassword((v) => !v)}
                                                aria-label={showConfirmPassword ? 'Hide password' : 'Show password'}
                                            >
                                                {showConfirmPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                            </Button>
                                        </div>
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
                                </motion.form>
                            </TabsContent>
                        )}
                    </AnimatePresence>
                </Tabs>
            </CardContent>
        </Card>
    )
}