-- schema: instaapp (assume search_path or prefix)
-- Note: IDs are Snowflake-style bigints (stored in signed bigint column).
--       We'll treat them as numeric strings in some languages when needed.

CREATE EXTENSION IF NOT EXISTS "pgcrypto"; -- for gen_random_uuid() if useful

-- Users
CREATE TABLE users (
  id              BIGINT PRIMARY KEY,
  username        VARCHAR(64) NOT NULL UNIQUE,
  email           VARCHAR(255) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  display_name    VARCHAR(128),
  bio             TEXT,
  profile_image_id BIGINT, -- FK to images added below (forward-ref fix)
  created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Posts (write model)
CREATE TABLE posts (
  id            BIGINT PRIMARY KEY,
  user_id       BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  caption       TEXT,
  visibility    VARCHAR(20) NOT NULL DEFAULT 'public', -- public/private
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  is_deleted    BOOLEAN NOT NULL DEFAULT FALSE
);
CREATE INDEX idx_posts_user_created ON posts(user_id, created_at DESC);

-- Polymorphic images table (imageable_type e.g. 'post','user')
CREATE TABLE images (
  id             BIGINT PRIMARY KEY,
  imageable_id   BIGINT NOT NULL,
  imageable_type VARCHAR(64) NOT NULL,
  storage_key    VARCHAR(512) NOT NULL, -- S3 key or CDN path
  mime_type      VARCHAR(64),
  width          INTEGER,
  height         INTEGER,
  size_bytes     BIGINT,
  alt_text       VARCHAR(512),
  is_primary     BOOLEAN DEFAULT FALSE,
  created_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);
-- index for polymorphic lookup
CREATE INDEX idx_images_imageable ON images(imageable_type, imageable_id, is_primary);

-- FK from users.profile_image_id -> images (declared here to avoid forward-reference at users creation time)
ALTER TABLE users ADD CONSTRAINT fk_users_profile_image
    FOREIGN KEY (profile_image_id) REFERENCES images(id) ON DELETE SET NULL;

-- Image fingerprints (2048-bit stored as 256 bytes)
CREATE TABLE image_fingerprints (
  id            BIGINT PRIMARY KEY,
  image_id      BIGINT NOT NULL REFERENCES images(id) ON DELETE CASCADE,
  algorithm     VARCHAR(50) NOT NULL,       -- e.g. 'pdq','phash','neural'
  hash_value    BYTEA NOT NULL,             -- enforce 256 bytes at app level
  hash_bytes    INTEGER NOT NULL DEFAULT 256,
  metadata      JSONB,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_imgfp_image ON image_fingerprints(image_id);
CREATE INDEX idx_imgfp_algo ON image_fingerprints(algorithm);

-- Comments
CREATE TABLE comments (
  id             BIGINT PRIMARY KEY,
  post_id        BIGINT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
  user_id        BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  parent_comment_id BIGINT REFERENCES comments(id) ON DELETE SET NULL,
  content        TEXT NOT NULL,
  created_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
  is_deleted     BOOLEAN NOT NULL DEFAULT FALSE
);
CREATE INDEX idx_comments_post_created ON comments(post_id, created_at DESC);
CREATE INDEX idx_comments_user ON comments(user_id);

-- Likes (unique per user/post)
CREATE TABLE likes (
  id         BIGINT PRIMARY KEY,
  post_id    BIGINT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
  user_id    BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX ux_likes_user_post ON likes(user_id, post_id);
CREATE INDEX idx_likes_post ON likes(post_id);

-- Follows
CREATE TABLE follows (
  id           BIGINT PRIMARY KEY,
  follower_id  BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  followee_id  BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX ux_follows ON follows(follower_id, followee_id);
CREATE INDEX idx_follows_followee ON follows(followee_id);

-- Sessions / refresh tokens
CREATE TABLE sessions (
  id                 BIGINT PRIMARY KEY,
  user_id            BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  refresh_token_hash VARCHAR(255) NOT NULL,
  client_info        JSONB,
  expires_at         TIMESTAMPTZ,
  created_at         TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_sessions_user ON sessions(user_id);

-- CQRS: denormalized counters for posts (read model)
CREATE TABLE post_counters (
  post_id      BIGINT PRIMARY KEY REFERENCES posts(id) ON DELETE CASCADE,
  likes_count  INTEGER NOT NULL DEFAULT 0,
  comments_count INTEGER NOT NULL DEFAULT 0,
  shares_count INTEGER NOT NULL DEFAULT 0,
  updated_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Denormalized user feed (read model) - optional; could be stored in DB or redis
CREATE TABLE user_feed (
  user_id    BIGINT NOT NULL,  -- feed owner
  post_id    BIGINT NOT NULL REFERENCES posts(id),
  score      NUMERIC NOT NULL DEFAULT 0,
  inserted_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (user_id, post_id)
);
CREATE INDEX idx_userfeed_user_score ON user_feed(user_id, score DESC);

-- GraphQL / analytics log for resource-detection & monitoring
CREATE TABLE graphql_request_log (
  id                 BIGINT PRIMARY KEY,
  path               VARCHAR(255),
  root_fields        TEXT,
  variables_summary  JSONB,
  user_id            BIGINT,
  created_at         TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_gqlreq_user ON graphql_request_log(user_id);

-- Utility: enforce binary length for hash_value maybe via CHECK (application must guarantee exact length)
ALTER TABLE image_fingerprints
  ADD CONSTRAINT chk_hash_len CHECK (octet_length(hash_value) = hash_bytes);

-- Partitioning recommendation (example for posts by created_at monthly):
-- CREATE TABLE posts_2026_03 PARTITION OF posts FOR VALUES FROM ('2026-03-01') TO ('2026-04-01');
-- (Define a partitioning strategy in production).
