# Vercel environment variables

Configure estas variaveis em Production, Preview e Development no Vercel.

## Supabase project

Estas sao as envs publicas do projeto Supabase. A anon key fica em:
Supabase > Project Settings > API > Project API keys > anon public.

```env
NEXT_PUBLIC_SUPABASE_URL=https://ecghpnltsptaeoqnhrxm.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=cole-a-anon-key-aqui
```

## Admin do painel

```env
ADMIN_PASSWORD=troque-por-uma-senha-forte
JWT_SECRET=gere-um-segredo-longo-com-32-ou-mais-caracteres
```

## Banco usado pelo backend

O app atual grava campanhas, cliques, bloqueios e postbacks pelo backend Node.
Para isso, ele ainda precisa de uma conexao Postgres server-side:

```env
DATABASE_URL=cole-a-connection-string-do-supabase-aqui
```

Notas:

- `NEXT_PUBLIC_SUPABASE_ANON_KEY` pode ficar no frontend; ela nao substitui a conexao server-side do banco.
- Nao coloque senha direta do banco em variaveis `NEXT_PUBLIC_*`.
- `.env` deve ficar apenas local. No deploy, use o painel do Vercel.
