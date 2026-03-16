'use client'

import Image from 'next/image'
import { Heart, MessageCircle, ChevronLeft, ChevronRight } from 'lucide-react'
import { Avatar, AvatarFallback, AvatarImage } from '@/shared/components/ui/avatar'
import { Button } from '@/shared/components/ui/button'
import { Card } from '@/shared/components/ui/card'
import type { Post } from '../../types/post.types'

interface PostCardProps {
  post: Post
  activeImageIndex: number
  canNavigateImages: boolean
  isLiked: boolean
  isPending: boolean
  likeCountLabel: string
  relativeTime: string
  onPreviousImage: () => void
  onNextImage: () => void
  onLike: () => void
  onComment: () => void
}

export function PostCardView({
  post,
  activeImageIndex,
  canNavigateImages,
  isLiked,
  isPending,
  likeCountLabel,
  relativeTime,
  onPreviousImage,
  onNextImage,
  onLike,
  onComment,
}: PostCardProps) {
  const activeImage = post.images[activeImageIndex]
  const avatarFallback = post.user.username.slice(0, 1).toUpperCase()

  return (
    <Card className="overflow-hidden border-border/70 bg-card/95 shadow-[0_24px_80px_-48px_rgba(15,23,42,0.65)] backdrop-blur">
      <div className="flex items-center justify-between border-b border-border/60 px-4 py-3">
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold">{post.user.username}</p>
          <p className="text-xs text-muted-foreground">{relativeTime}</p>
        </div>
        <div className="flex items-center gap-3">
          <span className="text-right text-xs text-muted-foreground">Post</span>
          <Avatar className="size-10 border border-border/60">
            {post.user.avatarUrl ? <AvatarImage src={post.user.avatarUrl} alt={post.user.username} /> : null}
            <AvatarFallback>{avatarFallback}</AvatarFallback>
          </Avatar>
        </div>
      </div>

      <div className="relative aspect-square bg-muted/40">
        {activeImage ? (
          <Image
            fill
            unoptimized
            loader={({ src }) => src}
            src={activeImage.url}
            alt={activeImage.alt ?? `${post.user.username} post image ${activeImageIndex + 1}`}
            sizes="(max-width: 768px) 100vw, 640px"
            className="object-cover"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-muted-foreground">
            No image available
          </div>
        )}

        {canNavigateImages ? (
          <>
            <Button
              type="button"
              variant="secondary"
              size="icon-sm"
              className="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-background/85 shadow-sm backdrop-blur"
              onClick={onPreviousImage}
              aria-label="Show previous image"
            >
              <ChevronLeft className="size-4" />
            </Button>
            <Button
              type="button"
              variant="secondary"
              size="icon-sm"
              className="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-background/85 shadow-sm backdrop-blur"
              onClick={onNextImage}
              aria-label="Show next image"
            >
              <ChevronRight className="size-4" />
            </Button>
            <div className="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5 rounded-full bg-background/70 px-3 py-1 backdrop-blur">
              {post.images.map((image, index) => (
                <span
                  key={image.id}
                  className={index === activeImageIndex ? 'size-2 rounded-full bg-primary' : 'size-2 rounded-full bg-foreground/20'}
                />
              ))}
            </div>
          </>
        ) : null}
      </div>

      <div className="space-y-3 px-4 pb-4 pt-3">
        <div className="flex items-center gap-2">
          <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            className="rounded-full"
            onClick={onLike}
            disabled={isPending}
            aria-label={isLiked ? 'Unlike post' : 'Like post'}
          >
            <Heart className={isLiked ? 'size-5 fill-red-500 text-red-500' : 'size-5'} />
          </Button>
          <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            className="rounded-full"
            onClick={onComment}
            aria-label="Open comments"
          >
            <MessageCircle className="size-5" />
          </Button>
        </div>

        <div className="flex items-center gap-3 text-sm">
          <span className="font-semibold">{likeCountLabel} likes</span>
          <span className="text-muted-foreground">{post.commentCount} comments</span>
        </div>

        <p className="text-sm leading-6 text-foreground/90">
          <span className="mr-2 font-semibold">{post.user.username}</span>
          {post.caption || 'Shared a new moment.'}
        </p>
      </div>
    </Card>
  )
}