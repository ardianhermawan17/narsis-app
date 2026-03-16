'use client'

import type { Post } from '../../types/post.types'
import { usePostCard } from './use-post-card'
import { PostCardView } from './post-card'

interface PostCardProps {
  post: Post
}

export function PostCard({ post }: PostCardProps) {
  const logic = usePostCard({
    postId: post.id,
    serverIsLiked: post.isLiked,
    likeCount: post.likeCount,
    createdAt: post.createdAt,
    imageCount: post.images.length,
  })

  return (
    <PostCardView
      post={post}
      activeImageIndex={logic.activeImageIndex}
      canNavigateImages={logic.canNavigateImages}
      isLiked={logic.isLiked}
      isPending={logic.isPending}
      likeCountLabel={logic.likeCountLabel}
      relativeTime={logic.relativeTime}
      onPreviousImage={logic.goToPreviousImage}
      onNextImage={logic.goToNextImage}
      onLike={logic.handleLike}
      onComment={logic.handleCommentOpen}
    />
  )
}