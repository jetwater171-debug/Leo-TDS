# Vercel environment variables

Configure estas variaveis em Production, Preview e Development no Vercel.

```env
DATABASE_URL=postgresql://postgres:SENHA_URL_ENCODED@db.ecghpnltsptaeoqnhrxm.supabase.co:5432/postgres
ADMIN_PASSWORD=troque-por-uma-senha-forte
JWT_SECRET=gere-um-segredo-longo-com-32-ou-mais-caracteres
```

Para senha com caracteres especiais, use URL encoding dentro da `DATABASE_URL`.

Exemplo:

```txt
! vira %21
@ vira %40
```

Notas:

- Use apenas `DATABASE_URL` para a conexao Supabase/Postgres deste projeto.
- Nao coloque a senha real em arquivos commitados.
- `.env` deve ficar apenas local. No deploy, use o painel do Vercel.
