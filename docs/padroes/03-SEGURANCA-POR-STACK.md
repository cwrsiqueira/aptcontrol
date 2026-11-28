# Segurança por stack

Como aplicar o [baseline](02-SEGURANCA.md) nas stacks que usamos. Leia só a seção da stack do projeto.

## Laravel (+ Inertia/React ou Blade)

- **Validação:** sempre `FormRequest` (`rules()` + `prepareForValidation()` para normalizar). Nada de `$request->all()` direto em `create`/`update`: use `$request->validated()`.
- **Mass assignment:** `$fillable` explícito em todo model. Proibido `$guarded = []`.
- **Autorização:** Policy por model + `authorize()`/`can` no controller ou `->can()` na rota. Permissões com constantes (spatie/laravel-permission), nunca string solta. Rota nova entra no grupo `auth`.
- **Multi-tenant:** trait com Global Scope (`PertenceAEmpresa`) em todo model de domínio; middleware que define a empresa ativa roda **antes** de `SubstituteBindings` (senão o route model binding resolve sem escopo). `semEscopo()` só em seed, comando e admin de tenants.
- **Resposta:** `JsonResource` com lista explícita de campos. Em Inertia, só os props que a página usa: tudo que vai em `Inertia::render` e em `HandleInertiaRequests::share` é público no HTML.
- **Consultas:** Eloquent/Query Builder com bindings. `DB::raw`, `whereRaw`, `orderByRaw` só com valor de allowlist.
- **Blade:** `{{ }}` sempre; `{!! !!}` proibido com dado de usuário.
- **CSRF:** middleware padrão ligado; exceção só para webhook, que valida assinatura.
- **Login:** `RateLimiter` por e-mail + IP; `Hash::make` (bcrypt/argon2id); `Auth::logoutOtherDevices` ao trocar senha; 2FA (Fortify) para admin.
- **Arquivos:** disco `local`/privado, `store()` com nome gerado, `mimes` + checagem do conteúdo, download por rota com policy (`Storage::download`).
- **Config de produção:** `APP_DEBUG=false`, `APP_ENV=production`, `APP_KEY` única por ambiente, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true` se houver dado sensível na sessão, `php artisan config:cache`. Document root em `public/`.
- **Headers:** middleware próprio (`SegurancaHttp`) com CSP com nonce (`Vite::useCspNonce()`), HSTS, nosniff, frame-ancestors, Referrer-Policy.
- **Logs:** canal `security` separado; nunca logar `$request->all()`.
- **Filas e jobs:** job recebe ID e recarrega do banco (o estado pode ter mudado); job é idempotente.
- **Testes:** `RefreshDatabase`, SQLite em memória, factories com Faker `pt_BR`. Teste de feature para cada rota com: sem login → 302/401, outro usuário → 403/404, outra empresa → 404, payload com campo proibido → ignorado.
- **Ferramentas:** Pint, Larastan (nível ≥ 5), `composer audit`.

## Next.js (App Router)

- **Server Actions e Route Handlers são endpoints públicos.** Toda action: (1) `obterSessao()`; (2) `schema.safeParse(entrada)` com `entrada: unknown`; (3) rate limit quando houver abuso possível; (4) consulta filtrada pelo `userId` da sessão; (5) retorno `{ ok, erro }` com mensagem amigável.
- Esconder a action na UI não protege: qualquer um chama pelo ID dela.
- `NEXT_PUBLIC_*` vai para o navegador. Segredo em variável sem prefixo, lida só em código de servidor. Marque módulos com `import "server-only"`.
- Componente cliente (`"use client"`) não importa nada que leia segredo ou banco.
- **Middleware não é a única barreira:** confira a sessão de novo na action/rota (houve bypass de middleware em versões antigas; mantenha o Next atualizado).
- CSP com nonce no `middleware.ts` + `strict-dynamic`; `poweredByHeader: false`; headers em `next.config`.
- Imagens remotas (`next/image`) com `remotePatterns` restrito.
- Prisma: só a API do client; `$queryRaw` com template tag (parametrizado), nunca `$queryRawUnsafe` com entrada.
- Serverless: rate limit e cache em store compartilhado; Prisma com pooler.
- Cookie de sessão via `cookies()` com `httpOnly`, `secure`, `sameSite`.

## Supabase / Lovable (React + Vite, TanStack Start, Edge Functions)

- **A chave publicável (`sb_publishable_` / `anon`) é pública.** Quem protege o dado é a **RLS**. Toda tabela no schema exposto tem RLS ligada e policies explícitas por operação (`SELECT`, `INSERT`, `UPDATE`, `DELETE`) e por papel.
- Policy padrão de multi-tenant: `USING (empresa_id = public.empresa_atual())` e `WITH CHECK` igual. Nunca policy `USING (true)` em tabela com dado de usuário.
- `UPDATE` precisa de policy de `SELECT` também; teste com dois usuários de verdade.
- Coluna sensível (papel, plano, status de pagamento) não é atualizável pelo cliente: policy ou trigger impede, ou a escrita vai só por Edge Function.
- **Chave secreta / `service_role` ignora RLS.** Só em Edge Function e servidor. Import dinâmico dentro do handler para não vazar para o bundle. Nunca em `VITE_*`.
- Funções `SECURITY DEFINER` com `SET search_path = public` (ou vazio) e checagem explícita do usuário dentro.
- Storage: bucket privado por padrão + policies por pasta (`auth.uid()` no caminho); URL assinada para leitura.
- Edge Function: validar JWT do usuário (`auth.getUser()`), validar corpo com Zod, CORS com origem explícita, segredo em `supabase secrets`.
- Webhook em Edge Function: validar assinatura antes de qualquer coisa; idempotência por tabela `processed_*` com `unique`.
- Rodar o **Security Advisor** do Supabase antes de cada publicação.
- Chaves legadas `anon`/`service_role` estão sendo descontinuadas até o fim de 2026: migre para publishable/secret e desligue as antigas.
- **Fronteira com o Lovable:** o Lovable é dono da infraestrutura (migrations, rotas geradas, arquivos marcados como gerados). A IA externa não cria migration nem rota nova: descreve em `docs/schema-pendente.md` e para. Sem force push, sem squash, sem trocar gerenciador de pacotes.

## PHP puro / site estático em hospedagem compartilhada (Hostinger/hPanel)

- Raiz pública só com o que é público. Config e credenciais fora de `public_html` ou bloqueadas no `.htaccess`.
- `.htaccess` com headers de segurança, `Options -Indexes`, bloqueio de `.env`, `.git`, `*.sql`, `*.bak`, `composer.*`.
- PDO com prepared statements e `ERRMODE_EXCEPTION`; `display_errors=Off`.
- Formulário de contato: validação no servidor, honeypot, rate limit simples (por IP em arquivo ou banco), sem montar header de e-mail com entrada.
- Site 100% estático (HTML/JS) não tem segredo nenhum: tudo nele é público. Integração que precisa de chave passa por um endpoint de servidor.
- Upload pelo gerenciador do hPanel: nunca subir `.env`, `node_modules`, backup ou dump.

## Flutter (app e web)

- `--dart-define`, `assets/` e `environment.json` vão para dentro do binário/bundle: são públicos. Segredo só no backend.
- `flutter_secure_storage` para token e PIN (PIN com hash e sal). Nada sensível em `SharedPreferences`.
- Release: `--obfuscate --split-debug-info`, minify/shrink no Android, sem `print` de dado sensível (`kReleaseMode`).
- HTTP só HTTPS; sem `badCertificateCallback` que aceita tudo.
- WebView/player: bloquear navegação externa; app infantil com gate parental.
- Flutter web: nada de segredo em `build/web`; variáveis injetadas no build vêm do provedor (Vercel) e só as públicas.

## Expo / React Native + backend em Worker (Cloudflare)

- `EXPO_PUBLIC_*` e `app.config` são públicos. Chaves de IA, HMAC e secret do RevenueCat só no Worker (`wrangler secret put`); em dev, `.dev.vars` fora do Git.
- Worker: Zod em todo body, limite de tamanho de body, rate limit (KV/Durable Object), integridade do app (App Attest/Play Integrity) quando houver custo por requisição.
- Entitlement consultado no servidor (REST do RevenueCat) com cache curto; o app nunca afirma "sou pro".
- Token assinado (HMAC) para qualquer dado que vai ao cliente e volta e não pode ser alterado (prompt de imagem, lista de exclusões, cupom).
- `expo-secure-store` para token local. Remover EXIF antes de enviar foto; o servidor não confia nisso e descarta de novo.

## Python (agentes de campo, scripts, ETL)

- Configuração por variável de ambiente ou arquivo fora do Git; nada de IP, token ou senha no código versionado.
- `requests`/`httpx` sempre com `timeout=` e HTTPS com verificação de certificado.
- Dados locais (SQLite de buffer) com permissão restrita; sem dado pessoal desnecessário.
- `subprocess` com lista de argumentos, nunca `shell=True` com entrada.
- Dependências fixadas (`requirements.txt` com versões ou `uv.lock`), `pip-audit` no CI.
- Serviço em produção roda com usuário sem privilégio, iniciado pelo systemd, com log rotacionado.

## Integrações comuns

| Integração | Regra |
| --- | --- |
| Stripe | Preço por `price_id` do servidor validado contra o catálogo; webhook com `constructEvent` e segredo; dedupe por `event.id`; modo live só com confirmação explícita do dono |
| Mercado Pago | Validar `x-signature` (HMAC com `x-request-id` e `data.id`); reconsultar o pagamento na API antes de liberar |
| Pix (PSP/banco) | Webhook com mTLS quando o PSP oferecer; sem mTLS, IP do PSP + segredo na URL + reconsulta da cobrança (`txid`) antes da baixa |
| WhatsApp (Cloud API, 360Dialog) | Verificar assinatura do webhook (`X-Hub-Signature-256` na Cloud API); responder 200 rápido e processar em fila; idempotência por ID da mensagem; canal atrás de interface |
| E-mail (Resend etc.) | Chave só no servidor; sem chave em produção a função lança (não imprime o corpo); opt-out por token |
| Google Maps / YouTube / APIs com chave de cliente | Restringir chave por domínio/app e por API no console; cota e alerta |
| ViaCEP / ReceitaWS / APIs públicas | Validar resposta com schema; timeout; não confiar no dado para decisão crítica sem revisão |
