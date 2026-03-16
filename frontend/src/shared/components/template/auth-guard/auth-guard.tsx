"use client";

import {toast} from "sonner";

interface AuthGuardProps {
    children: React.ReactNode;
}

export function AuthGuard({ children }: AuthGuardProps) {
    // Do auth check here make sure use Zustand for checking. After that use "sonner" from shadcn

    return <>{children}</>;
}