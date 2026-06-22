# Vercel environment variables

Configure estas variaveis em Production, Preview e Development no Vercel.

```env
SUPABASE_URL=https://ecghpnltsptaeoqnhrxm.supabase.co
SUPABASE_SERVICE_ROLE_KEY=cole-a-service-role-key-aqui
ADMIN_PASSWORD=troque-por-uma-senha-forte
JWT_SECRET=gere-um-segredo-longo-com-32-ou-mais-caracteres
```

Notas:

- Use `SUPABASE_SERVICE_ROLE_KEY`, nao `NEXT_PUBLIC_SUPABASE_ANON_KEY`, para o backend administrativo.
- Nunca coloque `SUPABASE_SERVICE_ROLE_KEY` com prefixo `NEXT_PUBLIC_`.
- Rode `supabase.sql` no SQL Editor do Supabase antes do primeiro uso.
- `.env` deve ficar apenas local. No deploy, use o painel do Vercel.
