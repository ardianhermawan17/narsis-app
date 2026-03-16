import { create } from 'zustand'

interface PostState {
  likedPostIds: Set<string>
  toggleLike: (postId: string) => void
  openCommentPostId: string | null
  setOpenCommentPostId: (id: string | null) => void
}

export const usePostStore = create<PostState>((set) => ({
  likedPostIds: new Set(),
  toggleLike: (postId) =>
    set((state) => {
      const next = new Set(state.likedPostIds)

      if (next.has(postId)) {
        next.delete(postId)
      } else {
        next.add(postId)
      }

      return { likedPostIds: next }
    }),
  openCommentPostId: null,
  setOpenCommentPostId: (id) => set({ openCommentPostId: id }),
}))