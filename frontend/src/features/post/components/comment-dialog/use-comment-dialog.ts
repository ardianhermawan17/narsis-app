'use client'

import { useEffect, useState } from 'react'
import type { MouseEvent } from 'react'
import type { Post } from '../../types/post.types'

interface UseCommentDialogProps {
  post: Post
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function useCommentDialog({ post, open, onOpenChange }: UseCommentDialogProps) {
  const [activeImageIndex, setActiveImageIndex] = useState(0)

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
    activeImageIndex,
    activeImage: post.images[activeImageIndex],
    canNavigateImages: post.images.length > 1,
    goToPreviousImage: () =>
      setActiveImageIndex((current) => (current === 0 ? post.images.length - 1 : current - 1)),
    goToNextImage: () =>
      setActiveImageIndex((current) => (current === post.images.length - 1 ? 0 : current + 1)),
    handleBackdropClick,
  }
}