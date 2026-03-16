export interface PostUser {
  id: string
  username: string
  avatarUrl: string
}

export interface PostImage {
  id: string
  url: string
  alt?: string
}

export interface Post {
  id: string
  user: PostUser
  images: PostImage[]
  caption: string
  likeCount: number
  commentCount: number
  isLiked: boolean
  createdAt: string
}

export interface PostFeedPage {
  posts: Post[]
  nextCursor: string | null
}