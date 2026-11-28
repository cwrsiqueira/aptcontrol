# Segurança — baseline comum

> Ao escrever, revisar ou arquitetar código, atue como engenheiro de segurança de aplicação. Segurança e integridade dos dados vêm antes de velocidade. A lista não esgota o assunto: se identificar um vetor novo, proteja o código e avise o responsável.

Este arquivo vale para todo projeto. O `docs/SEGURANCA.md` de cada projeto diz **como** ele cumpre cada item, o que ainda falta e o que é específico dele. Regras por stack em [03-SEGURANCA-POR-STACK.md](03-SEGURANCA-POR-STACK.md). IA no produto em [05-IA-NO-PRODUTO.md](05-IA-NO-PRODUTO.md).

**Leia só a seção do assunto.** Índice:

| § | Assunto | Leia quando |
| --- | --- | --- |
| 0 | Confiança zero no cliente | primeira vez no projeto |
| 1 | Quatro perguntas antes de cada feature | feature nova |
| 2 | Autorização e acesso | permissão, dono do registro, multiempresa |
| 3 | Entrada, validação e injeção | formulário, busca, filtro, SQL |
| 4 | Autenticação e sessão | login, senha, sessão, 2FA |
| 5 | Segredos e o que vai para o cliente | chave, `.env`, variável pública |
| 6 | Dinheiro e fluxos sensíveis | preço, pagamento, saldo, cupom |
| 7 | Arquivos e uploads | upload, download |
| 8 | Configuração e headers | servidor, CORS, CSP, debug |
| 9 | Cadeia de suprimentos | dependência nova |
| 10 | Integridade de dados e software | webhook, importação, deploy |
| 11 | Exceções e falhas | tratamento de erro |
| 12 | Logs e alertas | log, monitoramento |
| 13 | Abuso e consumo de recursos | rate limit, envio de e-mail/WhatsApp, IA |
| 14 | Chamadas para fora (SSRF) | buscar URL, API de terceiros |
| 15 | Inventário | rotas, versões de API |
| 16 | Navegador | HTML, cookies, armazenamento local |
| 17 | Mobile | app |
| 18 | Dados pessoais (LGPD) | CPF, contato, exclusão, retenção |
| 19 | Backup e ponto de volta | migração, mudança no banco, publicação grande |
| 20 | Testes de segurança obrigatórios | todo teste de mudança sensível |

## 0. Regra de ouro: confiança zero no cliente

Nada que vem de fora é confiável: corpo, query, URL, rota, header, cookie não assinado, ID, valor monetário, quantidade, papel do usuário, nome de arquivo, MIME declarado, webhook, CSV, OCR, resposta de API externa, saída de LLM.

| Situação | Errado | Certo |
| --- | --- | --- |
| Quem é o usuário | `user_id` no corpo | identidade da sessão assinada |
| Quanto custa | `price` no corpo | preço do catálogo no servidor, pelo ID do produto |
| Qual papel | `role: "admin"` no payload | papel lido do banco pela sessão |
| Pode editar? | botão escondido na tela | policy no servidor checando dono/tenant |
| Pagou? | redirecionamento para `/sucesso` | webhook assinado ou consulta ao provedor |
| É imagem? | extensão `.jpg` | magic bytes + reprocessamento |
| É assinante? | app diz "sou pro" | servidor consulta o provedor de assinatura |
| Cota usada | contador no app | contador no servidor, atômico |

Se a única forma de resolver algo é no frontend, **pare e avise**. Validação no cliente é só conforto de UX.

## 1. Antes de cada feature: quatro perguntas

1. **Quem** pode fazer isso? (anônimo, logado, dono, papel, tenant)
2. **Em qual** recurso? (este registro é dele / da empresa ativa?)
3. **O que** um atacante tentaria aqui? (trocar ID, repetir requisição, mandar valor negativo, mandar 10 mil vezes, mandar arquivo malicioso, injetar texto no prompt)
4. **O que vaza** se der errado? (dado pessoal, dinheiro, segredo, disponibilidade)

As respostas viram validação, policy, rate limit e **teste de abuso** no mesmo PR.

## 2. Autorização e acesso (OWASP A01:2025 · API1, API3, API5)

Falha de controle de acesso é o risco nº 1 da OWASP. Aqui ela aparece como IDOR/BOLA: trocar `/contratos/41` por `/contratos/42`.

- **Toda** rota autenticada passa por middleware de auth. Rota nova nasce protegida; pública é exceção explícita e listada no `SEGURANCA.md` do projeto.
- O ID na rota **localiza**; quem decide se pode ver é a policy (dono, papel, tenant). Consulta sempre filtrada pelo dono ou tenant da sessão: `where id = ? and empresa_id = ?`.
- **Multi-tenant:** toda tabela de negócio tem `empresa_id`; escopo global obrigatório em todo model; sem empresa ativa, `where 1 = 0`; teste automático de vazamento entre empresas em cada módulo novo. Sem esse teste, o módulo não está pronto.
- **Por função (BFLA):** ação administrativa checa permissão no servidor, não só no menu. Admin por allowlist no servidor; rota admin responde 404 para quem não é admin.
- **Por propriedade (mass assignment):** gravação só com lista explícita de campos permitidos (`$fillable`, schema Zod, DTO). `role`, `is_admin`, `empresa_id`, `status`, `preco`, `user_id` nunca vêm do payload.
- **Resposta mínima:** a API devolve só os campos que a tela usa, com lista explícita (Resource, `select`). Nunca o model inteiro. Senha, hash, token, campo interno e dado de outro usuário não saem.
- **IDs não sequenciais** em URL pública (`uuid`, `cuid`, `ulid`). Não é proteção sozinho, a policy continua obrigatória.
- **Recurso público** só aparece se marcado como publicado; checar o gate em todas as rotas que expõem o recurso (página, PDF, QR, VCF, imagem).
- **Mudar de empresa / papel** é ação explícita, revalidada no servidor e auditada.

## 3. Entrada, validação e injeção (A05:2025)

- **Validar no servidor com schema:** tipo, formato, tamanho mínimo e máximo, faixa numérica, lista fechada (enum) para status e tipo. Entrada nasce `unknown` e só vira tipo depois do `parse`.
- Normalize antes de validar (trim, Unicode NFC, só dígitos em CPF/CNPJ/telefone).
- Rejeite o que não conhece. Allowlist, nunca blocklist.
- **SQL:** só ORM ou query parametrizada. Proibido concatenar string em SQL, inclusive em `orderBy`/`sort`: coluna de ordenação vem de allowlist.
- **XSS:** confie no escape do framework (React, Blade `{{ }}`, Flutter Text). Proibido `dangerouslySetInnerHTML`, `{!! !!}`, `v-html`, `innerHTML` com dado de usuário. Se inevitável, sanitize com allowlist (DOMPurify) e documente.
- **Links:** `href` e `src` só com `https:` (ou caminho relativo do próprio site). Bloqueia `javascript:` e `data:`.
- **Redirect:** destino de `?next=` só de allowlist ou caminho relativo que começa com `/` e não com `//`.
- **Comando de sistema:** não montar comando com dado do usuário. Se inevitável, argumentos em array, sem shell.
- **Caminho de arquivo:** nome gerado no servidor; nunca `../` vindo do cliente; regex de nome seguro ao servir.
- **Planilha (CSV injection):** ao exportar, célula que começa com `=`, `+`, `-`, `@`, tab ou CR ganha apóstrofo. Ao importar, recuse ou neutralize.
- **E-mail e header:** sem quebra de linha em assunto e cabeçalhos montados com dado do usuário.
- **Template e regex:** sem template montado com entrada; regex com limite de tamanho da entrada (ReDoS).

## 4. Autenticação e sessão (A07:2025 · API2)

- **Senha** (NIST SP 800-63B rev. 4, ago/2025): mínimo 15 caracteres quando a senha é o único fator (8 com MFA), aceitar até 64+, qualquer caractere imprimível e espaço, **sem** regra de composição, **sem** troca periódica forçada, checar contra lista de senhas vazadas/comuns, permitir colar (gerenciador de senhas).
- **Hash** do framework: Argon2id ou bcrypt (custo ≥ 12). Atenção: bcrypt trunca em 72 bytes; limite o tamanho ou use Argon2id.
- **Sem senha fixa**: senha inicial aleatória com troca obrigatória, ou definida pelo próprio usuário por link. Credencial de seed vem do ambiente, nunca do código nem dos docs.
- **Mesma resposta e mesmo tempo** para "conta não existe" e "senha errada". Recuperação de senha: "se houver conta, enviamos".
- **Rate limit** no login por conta + IP e por IP; também na emissão e na verificação de código.
- **Código OTP / magic link:** 6 dígitos ou token aleatório; guardado como HMAC/hash; comparação em tempo constante; uso único; expiração curta (10–15 min); teto de 5 tentativas; vínculo ao navegador que pediu.
- **Token de reset/convite:** aleatório (≥ 128 bits), hash no banco, uso único, expira.
- **MFA** obrigatório para admin e para quem mexe em dinheiro ou dado pessoal de terceiros. Prefira passkey (WebAuthn) ou TOTP; SMS só como último recurso. Login não pode depender de quebra-cabeça cognitivo sem alternativa (WCAG 3.3.8).
- **Sessão:** cookie `HttpOnly`, `Secure`, `SameSite=Lax` (ou `Strict` no admin), prefixo `__Host-` quando possível. Regenerar ID no login. Expiração por inatividade e absoluta. Logout invalida no servidor. Revogação de todas as sessões (ex.: `token_version`) ao trocar senha.
- **Nunca** token, flag de privilégio ou dado pessoal em `localStorage`/`sessionStorage`.
- **JWT:** algoritmo fixo no servidor (rejeitar `none` e troca de algoritmo), segredo forte (≥ 32 bytes aleatórios), `exp` curto, validar `iss`/`aud`. Para app web próprio, prefira sessão de cookie a JWT no cliente.
- **Usuário inativo** não entra, mesmo com sessão antiga.

## 5. Segredos e o que vai para o cliente (A04:2025)

- Segredo só em variável de ambiente ou cofre do provedor (Vercel, Supabase secrets, Cloudflare `wrangler secret`, GitHub Actions secrets). `.env` fora do Git; `.env.example` com nomes e placeholders.
- **Tudo com prefixo público vai para o navegador**: `NEXT_PUBLIC_*`, `VITE_*`, `EXPO_PUBLIC_*`, `PUBLIC_*`, `--dart-define` de app, `assets/` de Flutter, HTML do site. Só URL pública e chave publicável entram neles.
- Chave publicável (Supabase publishable/anon, Stripe publishable, RevenueCat público, Google Maps) é pública por definição: a proteção é RLS, restrição no console do provedor (domínio, app, API) e limite de gasto.
- Chave secreta (Supabase secret/`service_role`, Stripe `sk_`, chave de IA, token de webhook) **nunca** em código de cliente, nem importada no topo de arquivo que o bundler possa levar ao cliente.
- **Segredo nunca aparece em comando** (`CHAVE=valor`, `export`, argumento de script): o shell grava o histórico. Use um script que pergunta sem eco ou edite o arquivo. Para conferir, liste só os nomes (`cut -d= -f1 .env`).
- Docs, testes, logs, issues e respostas citam o **nome** da variável, nunca o valor.
- Uma chave por ambiente e por componente. Vazou: rotacione primeiro, investigue depois. Rotação documentada no `DEPLOY.md`.
- Limite de gasto e alerta configurados no dia 1 em todo provedor pago (IA, cloud, mapas, SMS, e-mail).
- **Criptografia:** TLS 1.2+ em tudo; sem algoritmo próprio; aleatoriedade só de fonte criptográfica (`random_bytes`, `crypto.randomUUID`, `crypto.getRandomValues`, `secrets`). Dado muito sensível (documento, CPF em massa) com criptografia em repouso no nível de campo quando a lei ou o risco pedirem.
- **Varredura de segredo** no repositório: ligar secret scanning/push protection do GitHub quando o plano permitir e rodar `gitleaks` no CI ou no pre-commit.

## 6. Dinheiro, pagamentos e fluxos sensíveis (A06:2025 · API6)

- **Preço, desconto, frete e total são calculados no servidor** a partir do catálogo. O cliente envia só o que escolheu (ID do produto, plano em allowlist, quantidade validada).
- **Webhook fail-closed:** assinatura obrigatória (Stripe `constructEvent`, Mercado Pago `x-signature` HMAC, Pix com mTLS ou IP + segredo na URL conforme o provedor). Sem segredo configurado ou assinatura inválida ⇒ 400/401. Nunca 200 silencioso.
- **Estado vem do provedor:** depois do webhook, reconsulte a API quando o evento não trouxer o estado completo. Corpo do webhook não é confiável só por chegar.
- **Idempotência:** webhook chega duplicado e fora de ordem. Deduplicar pelo ID do evento (tabela com `unique`). Criação de cobrança com chave de idempotência.
- **Corrida (race condition):** saldo, estoque, cota, vaga e cupom são decrementados de forma atômica (`UPDATE ... WHERE saldo >= ?`, `SELECT ... FOR UPDATE`, transação). Teste com requisições paralelas.
- **Sem cobrança dupla:** quem já tem o recurso ativo não abre outro checkout.
- **Fluxos de negócio abusáveis** (cadastro, cupom, convite, trial, envio de mensagem, geração de PDF/imagem): limite por usuário, por IP e global; custo alto só para quem pagou.
- **Valor monetário** sempre em centavos inteiros ou decimal; nunca float.
- Conciliação e baixa de pagamento: só por confirmação verificada; registro do valor efetivamente pago; trilha de auditoria.

## 7. Arquivos e uploads

- Upload só autenticado, com rate limit por usuário.
- Tipo pelo **conteúdo** (magic bytes), não pela extensão nem pelo MIME declarado. Allowlist curta (JPG, PNG, WEBP, PDF).
- **SVG proibido** em upload de usuário (executa script). HTML também.
- Limite de tamanho e de quantidade no servidor e no proxy (Nginx/Apache/Caddy).
- Nome gerado no servidor (UUID). Nada do nome original no caminho.
- Disco **privado** e download por rota autenticada que confere o dono; ou URL assinada de vida curta. Nunca pasta pública com listagem.
- Reprocessar imagem (redimensionar) remove carga escondida; remover EXIF (localização GPS).
- Apagar arquivo antigo ao trocar ou excluir, conferindo que nenhum outro registro ainda usa a URL.
- PDF e imagem gerados no servidor só leem do próprio storage (anti-SSRF).

## 8. Configuração e headers (A02:2025 · API8)

- `APP_DEBUG=false` / `NODE_ENV=production`. Sem stack trace, SQL, caminho interno ou versão em resposta.
- Erro para o cliente: mensagem genérica + código de correlação. Detalhe só no log.
- Headers em toda resposta dinâmica:
  - `Content-Security-Policy` própria (com nonce para script inline; `frame-ancestors 'none'`; `object-src 'none'`; `base-uri 'self'`). Comece em Report-Only, depois enforce.
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains` em produção com HTTPS.
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin` (ou mais restrita)
  - `Permissions-Policy` desligando o que não usa (câmera, microfone, geolocalização)
  - sem `X-Powered-By` nem versão do servidor (`ServerTokens Prod`, `expose_php = Off`, `poweredByHeader: false`)
- Página com dado pessoal: `Cache-Control: no-store`.
- **CORS:** lista explícita de origens. Nunca `*` com credenciais. API só para o próprio front pode nem ter CORS.
- Bloquear acesso a `.env`, `.git`, `composer.json`, `storage/`, backups e `phpinfo` no servidor web. Raiz pública aponta só para `public/`.
- Sem usuário e senha padrão em nada (banco, painel, Traccar, phpMyAdmin). Painéis administrativos atrás de VPN, IP allowlist ou autenticação forte.
- HTTPS em tudo, inclusive APIs internas e agentes de campo. HTTP puro só em teste local.
- Firewall ligado na VPS: só as portas necessárias. Banco nunca exposto à internet.

## 9. Cadeia de suprimentos (A03:2025)

Entrou em 3º lugar na OWASP 2025. Com IA escrevendo código, o risco cresce: o modelo pode inventar nome de pacote, e alguém registra esse nome com código malicioso (*slopsquatting*).

- **Toda dependência sugerida por IA é conferida no registro** antes de instalar: existe, é a oficial, tem histórico, mantenedor conhecido, downloads compatíveis. Pacote criado há poucos dias é suspeito.
- Lockfile versionado (`package-lock.json`, `bun.lock`, `composer.lock`, `pubspec.lock`) e um gerenciador por projeto.
- `npm audit` / `composer audit` / `pip-audit` / `flutter pub outdated` no CI; alerta de dependência vulnerável ligado no GitHub.
- Atualização de segurança sobe com o teste do projeto afetado.
- GitHub Actions com permissões mínimas (`permissions:` explícito), action de terceiros fixada por SHA, segredo só no job que precisa.
- Script de terceiro no navegador só de CDN confiável e com `integrity` (SRI), ou servido do próprio domínio.
- Menos dependências é menos superfície: prefira o que o framework já entrega.

## 10. Integridade de dados e software (A08:2025)

- Webhooks e integrações entre sistemas: assinatura, token de serviço de vida limitada e escopo mínimo, idempotência e auditoria dos dois lados. Nenhum banco compartilhado entre sistemas.
- Nunca desserializar dado não confiável com formato que executa código (`unserialize` do PHP, `pickle`, YAML inseguro). Use JSON + schema.
- Import de dados (CSV, OFX, OCR, imagem) nunca vira registro definitivo direto: staging, validação, revisão humana, depois efetiva. Import é idempotente e nenhuma linha some em silêncio.
- Deploy só a partir do CI com testes verdes. Nada de editar arquivo direto em produção.

## 11. Exceções e falhas (A10:2025)

Categoria nova da OWASP 2025: o sistema precisa falhar de forma previsível.

- Toda chamada externa tem **timeout** e no máximo 1–2 retries com espera. Sem retry em operação não idempotente.
- Erro de terceiro não derruba o fluxo principal quando não é essencial (aviso por e-mail falhou ≠ venda falhou).
- Operação de várias escritas fica em **transação**. Falhou no meio, desfaz tudo.
- Exceção não vira sucesso: nada de `catch {}` vazio, nada de 200 com corpo de erro.
- Falta de configuração (segredo, URL) em produção impede o boot ou a rota, em vez de seguir com padrão inseguro.
- Limites de recurso tratados: arquivo grande, lista enorme, campo gigante, recursão.

## 12. Logs e alertas (A09:2025)

- Log de segurança para 401, 403, 429, falha de assinatura de webhook, login falho, troca de senha, mudança de papel e escrita sensível: método, rota, status, ID do usuário, ID da requisição, horário.
- **Nunca** no log: senha, token, OTP, cookie, header `Authorization`, corpo da requisição, query string com dado pessoal, número de cartão, CPF completo.
- Auditoria de negócio: quem mudou o quê e quando, com valor sensível mascarado.
- Monitor externo de disponibilidade em todo sistema em produção, com alerta que chega a alguém.
- Alerta para pico de 401/403/429, falha de webhook e erro 5xx.

## 13. Abuso e consumo de recursos (API4)

- Rate limit em toda entrada pública e em toda ação cara: login, cadastro, formulário público, upload, busca, geração de PDF/imagem, chamada de IA, envio de e-mail/WhatsApp.
- Em serverless ou mais de uma instância, rate limit em store compartilhado (Redis/KV/banco). Limitador em memória só serve para instância única.
- IP do cliente: confie em `X-Forwarded-For` só depois do proxy conhecido; documente qual posição da cadeia é usada.
- Paginação obrigatória com teto (`per_page ≤ 100`). Limite de tamanho de corpo e de campos.
- Timeout de requisição e de consulta.
- Formulário público: honeypot, rate limit e, se preciso, desafio acessível. Resposta igual para duplicado, sem revelar.
- Teto diário de gasto para recurso pago (IA, SMS): ao atingir, pausa o grátis e mantém o pagante.

## 14. Chamadas para fora: SSRF e APIs de terceiros (API7, API10)

SSRF agora faz parte de A01:2025.

- O servidor nunca busca URL escolhida pelo usuário. Se precisar, allowlist de host, só HTTPS, bloqueio de IP interno (127.0.0.0/8, 10/8, 172.16/12, 192.168/16, 169.254/16, `::1`), sem seguir redirect, timeout e teto de tamanho.
- Resposta de API de terceiro (CEP, CNPJ, provedor de pagamento, IA) é validada com schema como qualquer entrada.
- Credenciais de terceiro com escopo mínimo.

## 15. Inventário (API9)

- Lista de rotas públicas, autenticadas e administrativas no `SEGURANCA.md` do projeto.
- Rota de teste, debug, seed ou versão antiga da API não existe em produção.
- Ambiente de homologação sem dado real e sem a mesma chave de produção.

## 16. Navegador

- **CSRF:** formulário com sessão de cookie usa token CSRF do framework; `SameSite=Lax` no cookie. API com token Bearer não usa cookie.
- **Clickjacking:** `frame-ancestors 'none'` (ou `X-Frame-Options: DENY`).
- `target="_blank"` com `rel="noopener noreferrer"`.
- `postMessage` confere `origin`.
- Nada de lógica de autorização ou regra de preço no JavaScript do cliente; o bundle é público e editável.

## 17. Mobile (OWASP MASVS)

- Segredo nunca no app: a chave da IA, do pagamento e do HMAC fica no backend. O app chama o seu backend.
- Dado sensível local em storage seguro (Keychain/Keystore, `flutter_secure_storage`, `expo-secure-store`), nunca em SharedPreferences/AsyncStorage puro. PIN e segredo local com hash.
- Build de release com minificação/ofuscação, logs de debug desligados.
- Integridade do app (App Attest, Play Integrity) validada no servidor quando há custo ou fraude em jogo.
- Assinatura e entitlement conferidos no servidor junto ao provedor (RevenueCat, loja), nunca pelo que o app diz.
- Deep link valida destino; criança não sai do app sem gate parental.

## 18. Dados pessoais (LGPD)

- **Minimização:** colete só o necessário para a finalidade. CPF, documento, endereço completo e dado de menor só com finalidade clara.
- Base legal e finalidade escritas; política de privacidade em linguagem simples; aceite registrado com data.
- Direitos do titular: ver, corrigir, exportar, excluir (dados **e** arquivos). Retenção definida por tipo de dado e apagada automaticamente.
- Backups com dado pessoal: criptografados, acesso restrito, retenção definida. Exclusão some dos backups no máximo quando eles vencem; documente o prazo.
- Dado de desenvolvimento é sintético (Faker `pt_BR`). Arquivo real de cliente nunca entra no repositório, em fixture, seed ou print.
- **Incidente:** a Resolução CD/ANPD 15/2024 dá **3 dias úteis** para comunicar a ANPD (em dobro para agente de pequeno porte) quando houver risco ou dano relevante aos titulares, e pede comunicação aos titulares sem demora injustificada. Todo incidente é registrado internamente, mesmo sem comunicação. Roteiro em [checklists/INCIDENTE.md](checklists/INCIDENTE.md).

## 19. Backup e ponto de volta

- Backup automático do banco e dos arquivos, fora do servidor de produção, criptografado.
- Restauração testada de tempos em tempos (um backup nunca restaurado não é backup).
- Antes de publicar uma mudança que mexe no banco com dados reais (migration, importação, correção em massa): backup feito agora e caminho de volta escrito. Sem backup, não publica.
- Antes de uma entrega grande: marcação no Git (`git tag antes-<assunto>-<AAAA-MM-DD>`) para voltar o código, e migration que só acrescenta (coluna nova em vez de apagar ou renomear).
- A pessoa sempre sabe como voltar: onde está o backup, o comando de restaurar e a marcação. Isso fica no `docs/DEPLOY.md`.

## 20. Testes de segurança obrigatórios

Não basta o caminho feliz. Cada mudança sensível leva **casos de abuso**: IDOR (usuário A no recurso de B), papel no payload, valor adulterado, webhook sem assinatura, sem sessão, sessão expirada, entrada malformada, arquivo forjado, rate limit estourado, vazamento entre empresas, requisições paralelas. Catálogo em [07-TESTES.md](07-TESTES.md#testes-de-abuso).

O mapa das referências (OWASP, API, LLM, MASVS, NIST e LGPD) por seção fica no `FONTES.md` do kit. Para uma verificação mais funda (auditoria, cliente grande), use o OWASP ASVS 5.0 como checklist completo.
