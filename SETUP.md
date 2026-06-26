# Production Setup

1. Create a Railway project.
2. Add PostgreSQL and Redis plugins.
3. Set all variables from `.env.example` with production values.
4. Connect GitHub and deploy the repository.
5. Run Prisma migrations with `npm run db:migrate -w apps/api` if Railway does not execute release commands in your workflow.
6. Seed the first administrator with `npm run db:seed -w apps/api`, then rotate the seeded password immediately.

## Security Checklist

- Use a long random `JWT_SECRET`.
- Restrict `CORS_ORIGIN` to the production frontend origin.
- Store `ANTHROPIC_API_KEY` only in Railway variables.
- Use Railway-managed PostgreSQL and Redis private networking where available.
