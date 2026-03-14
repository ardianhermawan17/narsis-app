# Narsis Backend

Minimal DDD + CQRS-ready backend with working authentication endpoints.

## Requirements

- PHP >= 8.1
- PostgreSQL
- Docker + docker compose (optional local runtime)

## Environment

Copy and edit environment values:

```bash
cp .env.example .env
```

Required keys:

- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET`, `JWT_TTL`
- `SERVER_ID`

## Build and run (from repository root)

```bash
docker-compose build narsis-app-backend-php
docker-compose up -d narsis-app-postgress narsis-app-backend-php
docker-compose exec narsis-app-backend-php composer install
```

## Run migration

Apply [migrations/001_initial.sql](migrations/001_initial.sql) against `narsisdb`.

Example from container:

```bash
docker-compose exec -T narsis-app-postgress psql -U narsis -d narsisdb < backend/migrations/001_initial.sql
```

## Run seed

Apply [seeds/001_seed.sql](seeds/001_seed.sql) for local smoke tests.

```bash
docker-compose exec -T narsis-app-postgress psql -U narsis -d narsisdb < backend/seeds/001_seed.sql
```

## API endpoints

- `POST /api/register`
  - Body: `{ "username": "john", "email": "john@mail.com", "password": "secret123" }`
  - Returns `201` with created user payload
- `POST /api/login`
  - Body: `{ "usernameOrEmail": "john", "password": "secret123" }`
  - Returns `200` with `{ "accessToken": "..." }`
- `GET /api/profile`
  - Header: `Authorization: Bearer <accessToken>`
  - Returns authenticated user profile

## GraphQL endpoint

- `POST /graphql`
  - Query: `query { me { id username email createdAt } }`
  - Header: `Authorization: Bearer <accessToken>` required for `me`

Login and register are also available as GraphQL mutations:

- `mutation { register(username: "john", email: "john@mail.com", password: "secret123") { id username email } }`
- `mutation { login(usernameOrEmail: "john", password: "secret123") }`

## Notes

- Access token uses short TTL (`JWT_TTL`, default 900 seconds).
- Refresh token persistence is not implemented yet. TODO: add session-based refresh tokens using `sessions` table.
