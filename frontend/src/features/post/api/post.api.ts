import type { CombinedError } from '@urql/core'
import { urqlClient } from '@/shared/lib/urql-client'
import { GET_POSTS } from '../graphql/queries'
import { LIKE_POST, UNLIKE_POST } from '../graphql/mutations'
import type { Post, PostFeedPage } from '../types/post.types'

interface FeedPostImage {
  id: string
  storageKey: string
  altText?: string | null
  isPrimary: boolean
}

interface FeedPostRecord {
  id: string
  userId: string
  caption?: string | null
  likesCount: number
  createdAt: string
  images: FeedPostImage[]
}

interface FeedCounterRecord {
  postId: string
  likesCount: number
  commentsCount: number
}

interface FeedUserRecord {
  id: string
  username: string
  displayName?: string | null
}

interface FeedQueryResult {
  myFeed: FeedPostRecord[]
  postCounters: FeedCounterRecord[]
  userLike: Array<{ id: string }>
  me?: FeedUserRecord | null
}

const STORAGE_URL = (process.env.NEXT_PUBLIC_STORAGE_URL ?? '').replace(/\/$/, '')
const PAGE_SIZE = 5

const buildImageUrl = (storageKey: string) => {
  if (!storageKey) return ''
  if (/^https?:\/\//.test(storageKey)) return storageKey

  const normalizedKey = storageKey.replace(/^\/+/, '')
  return STORAGE_URL ? `${STORAGE_URL}/${normalizedKey}` : `/${normalizedKey}`
}

const buildUser = (userId: string, currentUser?: FeedUserRecord | null) => {
  if (currentUser?.id === userId) {
    return {
      id: currentUser.id,
      username: currentUser.displayName?.trim() || currentUser.username,
      avatarUrl: '',
    }
  }

  return {
    id: userId,
    username: `user-${userId.slice(-4) || userId}`,
    avatarUrl: '',
  }
}

const mapFeedData = (data: FeedQueryResult, limit: number): PostFeedPage => {
  const countersByPostId = new Map(data.postCounters.map((counter) => [counter.postId, counter]))
  const likedPostIds = new Set(data.userLike.map((post) => post.id))

  const posts: Post[] = data.myFeed.map((post) => {
    const counter = countersByPostId.get(post.id)

    return {
      id: post.id,
      user: buildUser(post.userId, data.me),
      images: post.images.map((image) => ({
        id: image.id,
        url: buildImageUrl(image.storageKey),
        alt: image.altText ?? undefined,
      })),
      caption: post.caption ?? '',
      likeCount: counter?.likesCount ?? post.likesCount,
      commentCount: counter?.commentsCount ?? 0,
      isLiked: likedPostIds.has(post.id),
      createdAt: post.createdAt,
    }
  })

  return {
    posts,
    nextCursor: posts.length >= limit ? String(limit + PAGE_SIZE) : null,
  }
}

type FeedApiResult = {
  data?: { posts: PostFeedPage }
  error?: CombinedError
}

export const postApi = {
  async getPosts(limit: number): Promise<FeedApiResult> {
    const result = await urqlClient.query<FeedQueryResult>(GET_POSTS, { limit }).toPromise()

    if (!result.data) {
      return { error: result.error }
    }

    return {
      data: { posts: mapFeedData(result.data, limit) },
      error: result.error,
    }
  },

  likePost(postId: string) {
    return urqlClient.mutation(LIKE_POST, { postId }).toPromise()
  },

  unlikePost(postId: string) {
    return urqlClient.mutation(UNLIKE_POST, { postId }).toPromise()
  },
}