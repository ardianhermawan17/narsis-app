"use client";

import { motion, AnimatePresence } from "framer-motion";
import { Button } from "@shared/components/ui/button";
import { Input } from "@shared/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@shared/components/ui/card";
import { useAuthForm } from "./use-auth-form";

export function AuthForm() {
    const { mode, toggleMode, form, onSubmit } = useAuthForm();

    const {
        register,
        formState: { errors, isSubmitting },
    } = form;

    return (
        <div className="flex items-center justify-center min-h-screen px-4">
            <Card className="w-full max-w-md">
                <CardHeader>
                    <CardTitle className="text-2xl text-center capitalize">
                        {mode}
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <form onSubmit={onSubmit} className="space-y-4">

                        {/* Email */}
                        <div>
                            <Input
                                placeholder="Email"
                                type="email"
                                {...register("email", { required: "Email is required" })}
                            />
                            {errors.email && (
                                <p className="text-sm text-red-500 mt-1">
                                    {errors.email.message}
                                </p>
                            )}
                        </div>

                        {/* Animated Display Name */}
                        <AnimatePresence>
                            {mode === "signup" && (
                                <motion.div
                                    key="display_name"
                                    initial={{ opacity: 0, height: 0 }}
                                    animate={{ opacity: 1, height: "auto" }}
                                    exit={{ opacity: 0, height: 0 }}
                                    transition={{ duration: 0.3 }}
                                >
                                    <Input
                                        placeholder="Display Name"
                                        {...register("display_name", {
                                            required:
                                                mode === "signup"
                                                    ? "Display name is required"
                                                    : false,
                                        })}
                                    />
                                    {errors.display_name && (
                                        <p className="text-sm text-red-500 mt-1">
                                            {errors.display_name.message}
                                        </p>
                                    )}
                                </motion.div>
                            )}
                        </AnimatePresence>

                        {/* Password */}
                        <div>
                            <Input
                                placeholder="Password"
                                type="password"
                                {...register("password", {
                                    required: "Password is required",
                                    minLength: {
                                        value: 6,
                                        message: "Minimum 6 characters",
                                    },
                                })}
                            />
                            {errors.password && (
                                <p className="text-sm text-red-500 mt-1">
                                    {errors.password.message}
                                </p>
                            )}
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={isSubmitting}
                        >
                            {mode === "login" ? "Login" : "Sign Up"}
                        </Button>
                    </form>

                    {/* Toggle */}
                    <div className="mt-6 text-center text-sm">
                        {mode === "login" ? (
                            <>
                                Don’t have an account?{" "}
                                <button
                                    type="button"
                                    onClick={toggleMode}
                                    className="text-primary font-medium hover:underline"
                                >
                                    Sign up
                                </button>
                            </>
                        ) : (
                            <>
                                Already have an account?{" "}
                                <button
                                    type="button"
                                    onClick={toggleMode}
                                    className="text-primary font-medium hover:underline"
                                >
                                    Login
                                </button>
                            </>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}