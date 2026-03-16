'use client'

import Image from 'next/image'
import { X } from 'lucide-react'
import { Button } from '@/shared/components/ui/button'
import { Card } from '@/shared/components/ui/card'
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
} from '@/shared/components/ui/carousel'
import type { MouseEventHandler } from 'react'
import type { Post } from '../../types/post.types'

interface CommentDialogProps {
  post: Post
  open: boolean
  onOpenChange: (open: boolean) => void
  onBackdropClick: MouseEventHandler<HTMLDivElement>
}

export function CommentDialogView({
  post,
  open,
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

        <Carousel className="relative aspect-square bg-black md:aspect-auto md:min-h-160">
          <CarouselContent>
            {post.images.length > 0 ? (
              post.images.map((image, index) => (
                <CarouselItem key={image.id}>
                  <div className="relative aspect-square w-full">
                    <Image
                      fill
                      unoptimized
                      loader={({ src }) => src}
                      src={image.url}
                      alt={image.alt ?? `${post.user.username} dialog image ${index + 1}`}
                      sizes="(max-width: 768px) 100vw, 50vw"
                      className="object-cover"
                    />
                  </div>
                </CarouselItem>
              ))
            ) : (
              <CarouselItem>
                <div className="flex aspect-square w-full items-center justify-center text-sm text-muted-foreground">
                  No image available
                </div>
              </CarouselItem>
            )}
          </CarouselContent>

          {post.images.length > 1 ? (
            <>
              <CarouselPrevious className="left-4 top-1/2 -translate-y-1/2" />
              <CarouselNext className="right-4 top-1/2 -translate-y-1/2" />
            </>
          ) : null}
        </Carousel>

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