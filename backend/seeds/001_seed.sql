-- Seed password for seeduser is the plain-text string: password
-- Hash below is password_hash('password', PASSWORD_BCRYPT) — a well-known test vector
INSERT INTO users (id, username, email, password_hash, display_name, bio, created_at, updated_at)
VALUES (
    3250368000000001,
    'seeduser',
    'seeduser@narsis.local',
    '$2y$10$C462o3F548nnfDfIH0WQtu6PBQhg/3NjQ4dJDFaXpWxVcd2P7Tmdm',  -- password
    'Seed User',
    'Seeded account for smoke testing.',
    NOW(),
    NOW()
)
ON CONFLICT (username) DO UPDATE
    SET email         = EXCLUDED.email,
        password_hash = EXCLUDED.password_hash,
        display_name  = EXCLUDED.display_name,
        bio           = EXCLUDED.bio,
        updated_at    = NOW();
