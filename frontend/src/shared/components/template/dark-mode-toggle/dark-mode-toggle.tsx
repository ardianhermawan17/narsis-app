"use client"

import * as React from "react"
import { Moon, Sun } from "lucide-react"
import { cn } from "@/shared/lib/shadcn"
import { useDarkModeToggle } from "./use-dark-mode-toggle"

interface DarkModeToggleProps {
  className?: string
  /**
   * "desktop" → renders a horizontal pill (label + icon) suited for a left sidebar.
   * "mobile"  → renders a compact square icon button suited for a bottom nav bar.
   * "auto"    → (default) picks desktop on md+ screens, mobile below md via CSS.
   */
  variant?: "desktop" | "mobile" | "auto"
}

export function DarkModeToggle({ className, variant = "auto" }: DarkModeToggleProps) {
  const { mounted, isDark, toggle } = useDarkModeToggle()

  // Prevent hydration mismatch — render a neutral placeholder until mounted
  if (!mounted) {
    return (
      <div
        className={cn(
          "h-9 w-9 rounded-full bg-muted animate-pulse",
          variant === "desktop" && "w-full h-10",
          className
        )}
      />
    )
  }

  const label = isDark ? "Light mode" : "Dark mode"

  /**
   * Desktop pill — used in left-side nav
   * Shows icon + label in a rounded pill that spans full width of the nav column.
   */
  const DesktopButton = (
    <button
      onClick={toggle}
      aria-label={label}
      title={label}
      className={cn(
        // Layout
        "group flex w-full items-center gap-3 px-3 py-2 rounded-xl",
        // Colours — respects shadcn CSS vars
        "text-muted-foreground hover:text-foreground",
        "bg-transparent hover:bg-accent",
        // Transition
        "transition-all duration-200 ease-in-out",
        className
      )}
    >
            <span className="relative flex h-5 w-5 shrink-0 items-center justify-center">
                {/* Sun icon — visible in dark mode */}
              <Sun
                className={cn(
                  "absolute h-5 w-5 transition-all duration-300",
                  isDark
                    ? "scale-100 rotate-0 opacity-100"
                    : "scale-0 -rotate-90 opacity-0"
                )}
              />
              {/* Moon icon — visible in light mode */}
              <Moon
                className={cn(
                  "absolute h-5 w-5 transition-all duration-300",
                  isDark
                    ? "scale-0 rotate-90 opacity-0"
                    : "scale-100 rotate-0 opacity-100"
                )}
              />
            </span>
      <span className="text-sm font-medium">{label}</span>
    </button>
  )

  /**
   * Mobile icon — used in bottom nav bar
   * Compact square/circle with just the icon; label is sr-only for a11y.
   */
  const MobileButton = (
    <button
      onClick={toggle}
      aria-label={label}
      title={label}
      className={cn(
        // Layout
        "relative flex items-center justify-center rounded-full",
        "h-10 w-10",
        // Colours
        "text-muted-foreground hover:text-foreground",
        "bg-transparent hover:bg-accent",
        // Transition
        "transition-all duration-200 ease-in-out",
        // Active tap feedback on mobile
        "active:scale-90",
        className
      )}
    >
      <span className="sr-only">{label}</span>
      <span className="relative flex h-5 w-5 items-center justify-center">
                <Sun
                  className={cn(
                    "absolute h-5 w-5 transition-all duration-300",
                    isDark
                      ? "scale-100 rotate-0 opacity-100"
                      : "scale-0 -rotate-90 opacity-0"
                  )}
                />
                <Moon
                  className={cn(
                    "absolute h-5 w-5 transition-all duration-300",
                    isDark
                      ? "scale-0 rotate-90 opacity-0"
                      : "scale-100 rotate-0 opacity-100"
                  )}
                />
            </span>
    </button>
  )

  // Explicit variants — no responsive CSS needed
  if (variant === "desktop") return DesktopButton
  if (variant === "mobile") return MobileButton

  // "auto" — let Tailwind pick based on breakpoint
  return (
    <>
      {/* Desktop: md and above */}
      <span className="hidden md:flex w-full">
                {DesktopButton}
            </span>
      {/* Mobile: below md */}
      <span className="flex md:hidden">
                {MobileButton}
            </span>
    </>
  )
}