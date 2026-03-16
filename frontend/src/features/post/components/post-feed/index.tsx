'use client'

import { usePostStore } from '../../store/post.store'
import { usePostFeed } from './use-post-feed'
import { PostFeedView } from './post-feed'

export function PostFeed() {
  const { posts, loading, error, hasMore, sentinelRef } = usePostFeed()
  const openCommentPostId = usePostStore((state) => state.openCommentPostId)
  const setOpenCommentPostId = usePostStore((state) => state.setOpenCommentPostId)

  const activePost = posts.find((post) => post.id === openCommentPostId) ?? null

  return (
    <PostFeedView
      posts={posts}
      activePost={activePost}
      loading={loading}
      error={error}
      hasMore={hasMore}
      sentinelRef={sentinelRef}
      onCommentDialogChange={(open) => {
        if (!open) {
          setOpenCommentPostId(null)
        }
      }}
    />
  )
}