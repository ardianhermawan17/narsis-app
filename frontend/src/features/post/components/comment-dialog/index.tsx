'use client'

import type { Post } from '../../types/post.types'
import { useCommentDialog } from './use-comment-dialog'
import { CommentDialogView } from './comment-dialog'

interface CommentDialogProps {
  post: Post
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function CommentDialog({ post, open, onOpenChange }: CommentDialogProps) {
  const logic = useCommentDialog({ open, onOpenChange })

  return (
    <CommentDialogView
      post={post}
      open={open}
      onOpenChange={onOpenChange}
      onBackdropClick={logic.handleBackdropClick}
    />
  )
}