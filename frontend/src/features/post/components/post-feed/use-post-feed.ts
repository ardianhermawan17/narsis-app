'use client'

import { useEffect, useRef, useState } from 'react'
import { postApi } from '../../api/post.api'
import type { Post } from '../../types/post.types'

const PAGE_SIZE = 5

export function usePostFeed() {
  const [posts, setPosts] = useState<Post[]>([])
  const [limit, setLimit] = useState(PAGE_SIZE)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [hasMore, setHasMore] = useState(true)
  const lastLoadedCountRef = useRef(0)
  const sentinelRef = useRef<HTMLDivElement | null>(null)
  const observerRef = useRef<IntersectionObserver | null>(null)

  useEffect(() => {
    let isCancelled = false

    const loadPosts = async () => {
      setLoading(true)
      setError(null)

      try {
        const result = await postApi.getPosts(limit)

        if (isCancelled) {
          return
        }

        if (result.error) {
          setError(result.error.message)
          setHasMore(false)
          return
        }

        const nextPosts = result.data?.posts.posts ?? []
        const hasNewPosts = nextPosts.length > lastLoadedCountRef.current

        setPosts(nextPosts)
        setHasMore(nextPosts.length >= limit && hasNewPosts)
        lastLoadedCountRef.current = nextPosts.length
      } catch {
        if (!isCancelled) {
          setError('Unable to load posts right now.')
          setHasMore(false)
        }
      } finally {
        if (!isCancelled) {
          setLoading(false)
        }
      }
    }

    void loadPosts()

    return () => {
      isCancelled = true
    }
  }, [limit])

  useEffect(() => {
    if (loading || !hasMore || !sentinelRef.current) {
      return
    }

    observerRef.current = new IntersectionObserver(
      ([entry]) => {
        if (!entry.isIntersecting) {
          return
        }

        observerRef.current?.disconnect()
        setLimit((current) => current + PAGE_SIZE)
      },
      { rootMargin: '320px 0px' }
    )

    observerRef.current.observe(sentinelRef.current)

    return () => {
      observerRef.current?.disconnect()
    }
  }, [hasMore, loading])

  return { posts, loading, error, hasMore, sentinelRef }
}