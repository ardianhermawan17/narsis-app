"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { useRouter } from "next/navigation";

import type { AuthMode, AuthFormValues } from "./types";
import {
    loginFailure,
    clearAuthError,
} from "@feature/auth/stores/auth-slice";

import {
    useLoginMutation,
    useSignupMutation,
} from "@feature/auth/api/auth-api";
import {useAppDispatch} from "@shared/config/redux/hooks";

// NEED TO CHANGE FROM REDUX TO ZUSTAND (if possible) Just make sure it using GraphQL instead of REST API

export function useAuthForm() {
    const router = useRouter();
    const dispatch = useAppDispatch();
    const [mode, setMode] = useState<AuthMode>("login");

    const [loginMutation] = useLoginMutation();
    const [signupMutation] = useSignupMutation();

    const form = useForm<AuthFormValues>({
        defaultValues: {
            email: "",
            password: "",
            display_name: "",
        },
    });

    const toggleMode = () => {
        dispatch(clearAuthError());
        setMode((prev) => (prev === "login" ? "signup" : "login"));
    };

    const onSubmitHandler = async (values: AuthFormValues) => {
        const toastId = toast.loading(
            mode === "login" ? "Logging in..." : "Creating account..."
        );
        
        try {
            if (mode === "login") {
                await loginMutation({
                    email: values.email,
                    password: values.password,
                }).unwrap();
            } else {
                await signupMutation({
                    email: values.email,
                    password: values.password,
                    display_name: values.display_name!,
                }).unwrap();
            }

            toast.success(
                mode === "login"
                    ? "Login successful!"
                    : "Account created successfully!",
                { id: toastId }
            );

            // Redirect after success
            router.replace("/draft-history");
        //     eslint-disable-next-line @typescript-eslint/no-explicit-any
        } catch (error: any) {
            const message =
                error?.data?.message || "Authentication failed";

            dispatch(loginFailure(message));

            toast.error(message, { id: toastId });
        }
    };

    return {
        mode,
        toggleMode,
        form,
        onSubmit: form.handleSubmit(onSubmitHandler),
    };
}