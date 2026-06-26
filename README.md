# NAZAR

NAZAR is an enterprise Political Intelligence and Digital Monitoring Platform for public-figure monitoring, alerts, mentions, scoring, reporting and analytics.

## Stack

- Node.js 20, Express, TypeScript
- Prisma, PostgreSQL
- Redis, BullMQ, node-cron
- React 18, Vite, TailwindCSS, TanStack Query, Zustand
- Anthropic Claude integration through `services/claude.service.ts`

## Quick start

```bash
cp .env.example .env
npm install
npm run db:migrate -w apps/api
npm run db:seed -w apps/api
npm run build
npm run start
```

## API

All responses use `{ success, data, error, meta }`.

- `GET /health`
- `POST /api/auth/login`
- `GET /api/auth/me`
- `GET /api/dashboard`
- `GET /api/mentions`
- `POST /api/mentions`

## Railway

Provision PostgreSQL and Redis plugins, configure environment variables from `.env.example`, and deploy. Nixpacks uses `railway.toml` and `Procfile`.
