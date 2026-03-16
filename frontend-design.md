# Frontend Design

## Stack and Runtime

- Framework: Next.js 16 (App Router) with React 19
- Language: TypeScript
- State: Zustand (with persist for auth)
- API client: URQL (`@urql/core`, `@urql/exchange-auth`)
- UI: Tailwind + shared shadcn-style components
- Testing/UX tooling: Vitest + Storybook

## Folder Architecture

- `src/app`: routing and page entry points
- `src/features/auth`: auth API, store, hooks, UI
- `src/features/post`: feed API, graphql documents, post UI, local post store
- `src/shared`: shared UI components, providers, client utilities

The frontend follows a feature-first structure and keeps data logic inside feature folders.

## Current Feature Design

### Authentication (implemented)

- GraphQL mutations/queries exist for `login`, `register`, `logout`, `me`.
- Auth tokens are stored in persisted Zustand state.
- URQL auth exchange appends `Authorization` and performs token refresh.
- `useAuth` orchestrates login/register/logout and user hydration.

### Feed and Post Interaction (partially implemented)

- Feed query is implemented through `postApi.getPosts(limit)`.
- Query combines `myFeed`, `postCounters`, `userLike`, and `me`, then maps to UI models.
- Infinite scrolling is implemented in `usePostFeed` using `IntersectionObserver`.
- Like/unlike mutations exist and are used with optimistic UI in `usePostCard`.

### UI Composition

- Route `src/app/(main-layout)/post/page.tsx` renders `PostFeed`.
- Post cards, image carousel dialog, and responsive layout are implemented.
- Comment dialog currently acts as visual shell and placeholder, not full comments feature.

## API Integration Status

### Already integrated

- Auth API (login/register/logout/me)
- Token refresh flow in URQL auth exchange
- Feed read (`myFeed` + counters + like state)
- Like/unlike mutation calls

### Missing or incomplete API integration

- Create post mutation flow from frontend UI is not wired yet.
- Add comment mutation flow is not wired yet.
- Fetch/render comments thread is not wired yet.
- User profile own-posts (`userPost`) page flow is missing.
- User comments (`userComment`) and liked-posts (`userLike`) pages/flows are missing.
- Route naming mismatch risk: auth redirects use `/login` in URQL helper while app route currently is `/auth`.

## Design Risks and Notes

- Feed currently derives cursor from `limit` growth, not backend cursor token; this is simple but not true cursor pagination.
- Mapping for non-self users is synthetic (`user-xxxx`) because feed payload does not include author profile data.
- Comments area explicitly states "next iteration", indicating intentional functional gap.

## Suggested Frontend API Completion Order

1. Implement comments read/write (`userComment`, `addComment`) and wire into comment dialog.
2. Implement create post mutation and upload form using existing `PostImageInput` schema.
3. Add profile routes backed by `userPost` and `userLike` queries.
4. Normalize route contracts (`/auth` vs `/login`) for auth redirects.
5. Add integration tests around URQL operations and auth-refresh failure handling.
