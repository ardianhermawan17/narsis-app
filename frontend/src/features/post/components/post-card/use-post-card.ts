'use client'

import { useMemo, useState } from 'react'
import { postApi } from '../../api/post.api'
import { usePostStore } from '../../store/post.store'

const relativeTimeFormatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' })
const compactCountFormatter = new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 })

const formatRelativeTime = (createdAt: string) => {
  const timestamp = new Date(createdAt).getTime()

  if (Number.isNaN(timestamp)) {
    return 'Just now'
  }

  const elapsedSeconds = Math.round((timestamp - Date.now()) / 1000)
  const units: Array<[Intl.RelativeTimeFormatUnit, number]> = [
    ['year', 60 * 60 * 24 * 365],
    ['month', 60 * 60 * 24 * 30],
    ['week', 60 * 60 * 24 * 7],
    ['day', 60 * 60 * 24],
    ['hour', 60 * 60],
    ['minute', 60],
  ]

  for (const [unit, secondsPerUnit] of units) {
    if (Math.abs(elapsedSeconds) >= secondsPerUnit) {
      return relativeTimeFormatter.format(Math.round(elapsedSeconds / secondsPerUnit), unit)
    }
  }

  return 'Just now'
}

const formatCompactCount = (count: number) => compactCountFormatter.format(Math.max(count, 0))

interface UsePostCardProps {
  postId: string
  serverIsLiked: boolean
  likeCount: number
  createdAt: string
  imageCount: number
}

export function usePostCard({
  postId,
  serverIsLiked,
  likeCount,
  createdAt,
  imageCount,
}: UsePostCardProps) {
  const { likedPostIds, toggleLike, setOpenCommentPostId } = usePostStore()
  const [isPending, setIsPending] = useState(false)
  const [activeImageIndex, setActiveImageIndex] = useState(0)

  const isLiked = likedPostIds.has(postId) ? !serverIsLiked : serverIsLiked
  const optimisticDelta = isLiked === serverIsLiked ? 0 : isLiked ? 1 : -1

  const likeCountLabel = useMemo(
    () => formatCompactCount(likeCount + optimisticDelta),
    [likeCount, optimisticDelta]
  )

  const relativeTime = useMemo(() => formatRelativeTime(createdAt), [createdAt])

  const goToPreviousImage = () => {
    setActiveImageIndex((current) => (current === 0 ? imageCount - 1 : current - 1))
  }

  const goToNextImage = () => {
    setActiveImageIndex((current) => (current === imageCount - 1 ? 0 : current + 1))
  }

  const handleLike = async () => {
    if (isPending) return

    const nextIsLiked = !isLiked
    toggleLike(postId)
    setIsPending(true)

    try {
      if (nextIsLiked) {
        await postApi.likePost(postId)
      } else {
        await postApi.unlikePost(postId)
      }
    } catch {
      toggleLike(postId)
    } finally {
      setIsPending(false)
    }
  }

  const handleCommentOpen = () => {
    setOpenCommentPostId(postId)
  }

  return {
    activeImageIndex,
    isLiked,
    isPending,
    likeCountLabel,
    relativeTime,
    canNavigateImages: imageCount > 1,
    goToPreviousImage,
    goToNextImage,
    handleLike,
    handleCommentOpen,
  }
}