# GraphQL Resolver Entrypoints

Place query and mutation resolvers in this directory and map them to Application handlers.

Current resolvers:

- `MeResolver` for `Query.me`
- `RegisterResolver` for `Mutation.register`
- `LoginResolver` for `Mutation.login`

GraphQL structure split:

- `src/Interfaces/GraphQL/Query/` contains query type definitions
- `src/Interfaces/GraphQL/Mutation/` contains mutation type definitions
- `src/Interfaces/GraphQL/Type/` contains shared GraphQL object types
- `src/Interfaces/GraphQL/Schema/` composes the final GraphQL schema

Postman schema import options:

- File import: `backend/graphql.schema`
- URL import: `GET /graphql/schema` on the running backend (for local: `http://localhost:8080/graphql/schema`)
