import { PostFeed } from '@feature/post'

export default function PostPage() {
  return (
    <main className="min-h-screen bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.12),transparent_35%),linear-gradient(180deg,rgba(248,250,252,0.95),rgba(255,255,255,1))] dark:bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.2),transparent_30%),linear-gradient(180deg,rgba(2,6,23,1),rgba(15,23,42,0.96))]">
      <div className="mx-auto w-full max-w-6xl">
        <PostFeed />
      </div>
    </main>
  )
}