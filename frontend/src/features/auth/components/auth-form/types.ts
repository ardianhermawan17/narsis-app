export type AuthMode = "login" | "signup";

export interface AuthFormValues {
    email: string;
    password: string;
    display_name?: string;
}