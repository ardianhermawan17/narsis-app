# Backend Design

## Stack and Runtime

- Language: PHP 8.1+
- Data store: PostgreSQL
- API surface: GraphQL (`/graphql`) plus resource gateway endpoints (`/v1/<resource>`)
- Architecture style: minimal DDD + CQRS-ready layering

## Layered Structure

- `src/Domain`: entities and repository interfaces (User, Post, Image, Comment, Like, Feed, Session)
- `src/Application`: command/query handlers, GraphQL helpers, validations
- `src/Infrastructure`: persistence adapters, ID generation, image processing/storage, GraphQL cache/mappers
- `src/Interfaces`: HTTP controllers/middleware and GraphQL schema factories

This keeps business actions in handlers and pushes transport concerns to interface adapters.

## Core Implemented Features

### 1) Snowflake-style ID Generator

- Implemented in `Infrastructure/ID/SnowflakeGenerator.php`.
- Uses:
  - 4 bits server id (`0..15`)
  - 19 bits sequence per millisecond
  - timestamp shifted from custom epoch (`2021-01-01T00:00:00Z`)
- Protects against clock rollback and sequence overflow within same millisecond.
- Used across commands (register, login/session, create post/image/fingerprint, comments, request logs).

### 2) Image Duplicate Detection

- Fingerprint worker: `Infrastructure/Image/Copyright/ImageProcessingWorker.php`.
- Generates deterministic 2048-bit hash-like fingerprint payload (`algorithm: pdq`, `hashBytes: 256`).
- Duplicate check uses Hamming distance with configurable threshold.
- `CreatePostHandler`:
  - decodes incoming base64 image
  - computes fingerprint
  - compares against stored fingerprints
  - throws `DuplicateImageDetectedException` on threshold match
  - persists image metadata and fingerprint in one transaction
- Fingerprints are persisted in `image_fingerprints` table via `PgImageRepository`.

### 3) GraphQL AST Resource Gateway

- Main path support:
  - direct GraphQL: `/graphql`
  - resource-style gateway: `/v1/<resource>`
- Gateway adapter/middleware responsibilities:
  - map resource names to persisted/canonical GraphQL query
  - parse GraphQL AST once and cache document (`LruAstCache`)
  - extract top-level fields (`TopLevelResourceExtractor`)
  - enforce document depth/cost limits (`GraphQlDocumentLimiter`)
- Persisted resource map includes domains like `auth`, `post`, `user-post`, `user-comment`, `user-like`, `post-counters`, `feed`, `profile`.

## API and Domain Coverage

- Auth: register, login, refresh token, logout
- Post: create post, list all posts, list user posts, post counters
- Like: like/unlike, list liked posts
- Comment: add comment, list user comments
- Feed: personalized `myFeed`

## Runtime Composition

- Composition root in `public/bootstrap.php` wires repositories, handlers, schema registry, gateway adapter, and logger.
- JWT auth middleware protects private operations.
- GraphQL schema registry maps resources/fields to schema domains.

## Testing Snapshot

- Integration-oriented tests exist for auth, posts, likes, comments, and feed.
- Tests include resource-gateway coverage (`/v1/post`, `/v1/feed`, `/v1/user-post`, `/v1/post-counters`).
- Fingerprint worker behavior is directly tested (`PostsTest`).

## Design Notes

- The backend is already strong on core infrastructure features you mentioned:
  - Snowflake ID generator
  - image duplicate detector
  - GraphQL AST resource gateway for REST-like resource endpoints
- The design is set up for incremental CQRS/read-model expansion without changing the transport contract.
