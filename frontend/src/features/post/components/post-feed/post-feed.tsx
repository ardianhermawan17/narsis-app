'use client'

import type { RefObject } from 'react'
import type { Post } from '../../types/post.types'
import { PostCard } from '../post-card'
import { CommentDialog } from '../comment-dialog'

interface PostFeedProps {
  posts: Post[]
  activePost: Post | null
  loading: boolean
  error: string | null
  hasMore: boolean
  sentinelRef: RefObject<HTMLDivElement | null>
  onCommentDialogChange: (open: boolean) => void
}

export function PostFeedView({
  posts,
  activePost,
  loading,
  error,
  hasMore,
  sentinelRef,
  onCommentDialogChange,
}: PostFeedProps) {
  return (
    <section className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
      <div className="rounded-[2rem] border border-border/60 bg-[linear-gradient(135deg,rgba(255,255,255,0.92),rgba(240,244,255,0.88))] px-6 py-8 shadow-[0_32px_120px_-72px_rgba(37,99,235,0.55)] dark:bg-[linear-gradient(135deg,rgba(30,41,59,0.92),rgba(15,23,42,0.9))]">
        <p className="text-xs font-semibold uppercase tracking-[0.35em] text-primary">Post feed</p>
        <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">A centered stream for the moments people keep.</h1>
        <p className="mt-3 max-w-2xl text-sm leading-7 text-muted-foreground sm:text-base">
          The feed uses the current backend schema, progressive loading, and an inline gallery flow without pushing logic into the route layer.
        </p>
      </div>

      {posts.map((post) => (
        <PostCard key={post.id} post={post} />
      ))}

      {error ? (
        <div className="rounded-2xl border border-destructive/20 bg-destructive/5 px-4 py-3 text-sm text-destructive">
          {error}
        </div>
      ) : null}

      {!loading && posts.length === 0 && !error ? (
        <div className="rounded-2xl border border-border/60 bg-card px-5 py-8 text-center text-sm text-muted-foreground">
          No posts are available yet.
        </div>
      ) : null}

      <div ref={sentinelRef} className="h-8 w-full" aria-hidden="true" />

      {loading ? (
        <p className="pb-8 text-center text-sm text-muted-foreground">Loading more posts...</p>
      ) : null}

      {!hasMore && posts.length > 0 ? (
        <p className="pb-8 text-center text-sm text-muted-foreground">You&apos;re all caught up.</p>
      ) : null}

      {activePost ? (
        <CommentDialog
          key={activePost.id}
          post={activePost}
          open={Boolean(activePost)}
          onOpenChange={onCommentDialogChange}
        />
      ) : null}
    </section>
  )
}