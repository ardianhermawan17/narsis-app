'use client'

import Image from 'next/image'
import { X, ChevronLeft, ChevronRight } from 'lucide-react'
import { Button } from '@/shared/components/ui/button'
import { Card } from '@/shared/components/ui/card'
import type { MouseEventHandler } from 'react'
import type { PostImage, Post } from '../../types/post.types'

interface CommentDialogProps {
  post: Post
  open: boolean
  activeImage?: PostImage
  activeImageIndex: number
  canNavigateImages: boolean
  onPreviousImage: () => void
  onNextImage: () => void
  onOpenChange: (open: boolean) => void
  onBackdropClick: MouseEventHandler<HTMLDivElement>
}

export function CommentDialogView({
  post,
  open,
  activeImage,
  activeImageIndex,
  canNavigateImages,
  onPreviousImage,
  onNextImage,
  onOpenChange,
  onBackdropClick,
}: CommentDialogProps) {
  if (!open) {
    return null
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
      onClick={onBackdropClick}
      role="presentation"
    >
      <Card className="relative grid max-h-[90vh] w-full max-w-5xl overflow-hidden border-white/10 bg-card md:grid-cols-2">
        <Button
          type="button"
          variant="secondary"
          size="icon-sm"
          className="absolute right-4 top-4 z-10 rounded-full bg-background/90"
          onClick={() => onOpenChange(false)}
          aria-label="Close comments dialog"
        >
          <X className="size-4" />
        </Button>

        <div className="relative aspect-square bg-black md:aspect-auto md:min-h-160">
          {activeImage ? (
            <Image
              fill
              unoptimized
              loader={({ src }) => src}
              src={activeImage.url}
              alt={activeImage.alt ?? `${post.user.username} dialog image ${activeImageIndex + 1}`}
              sizes="(max-width: 768px) 100vw, 50vw"
              className="object-cover"
            />
          ) : null}

          {canNavigateImages ? (
            <>
              <Button
                type="button"
                variant="secondary"
                size="icon-sm"
                className="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-background/85"
                onClick={onPreviousImage}
                aria-label="Show previous dialog image"
              >
                <ChevronLeft className="size-4" />
              </Button>
              <Button
                type="button"
                variant="secondary"
                size="icon-sm"
                className="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-background/85"
                onClick={onNextImage}
                aria-label="Show next dialog image"
              >
                <ChevronRight className="size-4" />
              </Button>
            </>
          ) : null}
        </div>

        <div className="flex aspect-square flex-col justify-between bg-background p-6 md:min-h-160">
          <div className="space-y-4">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                Comment panel
              </p>
              <h2 className="mt-2 text-2xl font-semibold">{post.user.username}</h2>
            </div>
            <p className="text-sm leading-7 text-foreground/80">
              {post.caption || 'Comment experience will be extended here in the next iteration.'}
            </p>
          </div>

          <div className="rounded-2xl border border-dashed border-border bg-muted/30 p-4 text-sm text-muted-foreground">
            Comments and composer UI are intentionally reserved for the next pass. This dialog already keeps the required 1:1 split with the image viewer.
          </div>
        </div>
      </Card>
    </div>
  )
}