# Vercel environment variables

Configure estas variaveis em Production, Preview e Development no Vercel.

```env
DATABASE_URL=postgresql://postgres.[PROJECT_REF]:[PASSWORD]@aws-0-[REGION].pooler.supabase.com:6543/postgres?pgbouncer=true
ADMIN_PASSWORD=troque-por-uma-senha-forte
JWT_SECRET=gere-um-segredo-longo-com-32-ou-mais-caracteres
```

Opcional:

```env
NEXT_PUBLIC_APP_NAME=YellowTDS
```

Notas:

- Use a connection string do Supabase Transaction Pooler na porta `6543` para Vercel.
- Nao use a senha padrao antiga em producao. O login bloqueia se `ADMIN_PASSWORD` ou `JWT_SECRET` estiverem ausentes no Vercel.
- `.env` deve ficar apenas local. No deploy, use o painel do Vercel.
