'use client'

import { useEffect } from 'react'
import type { MouseEvent } from 'react'

interface UseCommentDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function useCommentDialog({ open, onOpenChange }: UseCommentDialogProps) {
  useEffect(() => {
    if (!open) return

    const originalOverflow = document.body.style.overflow
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onOpenChange(false)
      }
    }

    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', handleKeyDown)

    return () => {
      document.body.style.overflow = originalOverflow
      window.removeEventListener('keydown', handleKeyDown)
    }
  }, [open, onOpenChange])

  const handleBackdropClick = (event: MouseEvent<HTMLDivElement>) => {
    if (event.target === event.currentTarget) {
      onOpenChange(false)
    }
  }

  return {
    handleBackdropClick,
  }
}