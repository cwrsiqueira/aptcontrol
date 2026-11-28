# Testes

O objetivo é um só: **mudança depois não quebra o que já funcionava**, e ninguém precisa lembrar de testar à mão.

## Regras gerais

1. **Toda regra de negócio tem teste.** Função pura em `lib/`/`app/Domain`/`src/lib` com teste unitário ao lado.
2. **Todo bug corrigido ganha um teste que falhava antes.** Primeiro o teste vermelho, depois a correção.
3. **Toda rota/ação sensível tem teste de abuso**, não só o caminho feliz (catálogo abaixo).
4. **Teste não depende de segredo nem de serviço real.** Pagamento, e-mail, WhatsApp e IA ficam atrás de interface com implementação falsa (`FakePaymentProvider`).
5. **Dado de teste é sintético** (Faker `pt_BR`). Nunca dado real de cliente.
6. **O CI roda lint + tipos + testes em todo push**, e deploy só acontece com tudo verde.
7. **Cobertura se mede por invariante, não por porcentagem.** A lista de invariantes do projeto está no `docs/TESTES.md` dele, cada um com o teste que o cobre.
8. A IA **não** altera nem apaga teste para fazer a suíte passar. Teste quebrado por mudança de regra é atualizado só quando a nova regra está escrita no `docs/DOMINIO.md`.

## Pirâmide

| Nível | O quê | Ferramenta | Quando roda |
| --- | --- | --- | --- |
| Unitário | funções puras: cálculo, validação, formatação, regra | Vitest, `bun test`, PHPUnit/Pest, `flutter test`, pytest | sempre, em segundos |
| Integração/feature | rota ou action com banco de teste: auth, policy, validação, efeito no banco | Laravel feature tests, Vitest com banco/mocks, Supabase local | sempre no CI |
| E2E | 3–5 fluxos que dão dinheiro ou quebram o negócio | Playwright, `integration_test`/Maestro | antes de publicar e sob demanda |
| Manual | só o que é visual ou depende de aparelho | checklist do `pode-subir` | na publicação |

## Por tipo de sistema

### SaaS / ERP / CRM web (Laravel, Next.js)

- Unitário: preço, plano, encargos, juros, datas de vencimento, máscaras, validação de CPF/CNPJ, geração de slug/código.
- Feature por rota: sem login ⇒ 401/redirect; outro usuário ⇒ 403/404; outra empresa ⇒ 404; payload inválido ⇒ 422 com campo; payload com campo proibido (`role`, `empresa_id`, `preco`) ⇒ ignorado; caminho feliz ⇒ efeito correto no banco.
- Isolamento multi-tenant: um teste por módulo que cria dados em duas empresas e prova que uma não vê a outra.
- Webhook: assinatura ausente/errada ⇒ 400; evento duplicado ⇒ processado uma vez; evento desconhecido ⇒ 200 sem efeito.
- E2E: login, fluxo principal (criar → publicar/fechar), pagamento em modo de teste.

### Supabase / Lovable

- Funções de `src/lib/` com teste unitário (`bun test`/Vitest).
- **Testes de RLS**: com dois usuários reais no Supabase local (ou pgTAP), provar que A não lê, não altera e não apaga o que é de B, e que anônimo não lê nada privado.
- Teste estático que falha se o código do cliente mencionar `service_role`, `sb_secret_` ou importar o client admin.
- Edge Functions: teste do handler com request simulado (sem JWT ⇒ 401, corpo inválido ⇒ 400).

### Site institucional / landing / estático

- Checagem automática de links quebrados e imagens sem `alt` (ex.: `lychee`, `pa11y` ou Lighthouse CI).
- Lighthouse nas páginas principais: acessibilidade ≥ 90, performance sem regressão.
- Formulário: envio válido, envio com honeypot preenchido (finge sucesso, não envia), rate limit.
- Conferência de headers de segurança em produção (`curl -I`).

### Mobile (Flutter, Expo)

- Unitário de regra e de services (com fakes).
- Widget/component test das telas críticas (login, paywall, fluxo principal).
- Teste que falha se houver chave secreta em `assets/`, `app.config`, `EXPO_PUBLIC_*`.
- Backend do app (Worker/API) com os mesmos testes de abuso do web: sem token de integridade, entitlement falso, cota estourada.

### IA no produto

- Suíte de conversas simuladas (ver [05-IA-NO-PRODUTO.md](05-IA-NO-PRODUTO.md#testes)): fatos batem com o banco, contas batem com o código, injeção é ignorada, saída respeita o schema.
- Rodar com o provedor real só em job manual (custa dinheiro); no CI, com respostas gravadas.

### Financeiro, contábil, importação

- Invariantes como teste: débito = crédito; período fechado não aceita escrita; dinheiro sem float; sequencial sem buraco; arredondamento.
- Importação: mesmo arquivo duas vezes ⇒ sem duplicata e com alerta; linha inválida ⇒ registrada com motivo; conta que sumiu do arquivo ⇒ alerta, nunca baixa.
- Arquivos de exemplo sintéticos em `tests/fixtures/` cobrindo os formatos reais.

### Scripts e agentes de campo (Python)

- `python -m py_compile` no mínimo; pytest nas funções de cálculo (ETA, distância, parsing de NMEA).
- Teste de "sem rede": fila enche offline e esvazia quando volta.

## Testes de abuso

Escolha os que se aplicam à mudança. Cada um vira um teste nomeado.

| Ataque | O teste prova que |
| --- | --- |
| IDOR/BOLA | usuário A recebe 403/404 no recurso de B (ler, editar, apagar, baixar arquivo) |
| Vazamento entre empresas | consulta e rota de uma empresa não retornam nada da outra |
| Elevação por payload | `role`, `is_admin`, `empresa_id`, `user_id`, `status`, `preco` no corpo são ignorados |
| Função restrita | usuário comum recebe 403/404 em rota admin |
| Sem sessão / sessão inválida | cookie ausente, forjado ou expirado ⇒ não autenticado |
| Valor adulterado | preço, desconto, quantidade negativa ou plano fora da allowlist ⇒ recusado ou recalculado |
| Pagamento duplicado | quem já é assinante não abre novo checkout; webhook repetido processa uma vez |
| Webhook falso | sem assinatura, assinatura errada ou segredo ausente ⇒ 400 |
| Corrida | N requisições paralelas não gastam o saldo/cota/vaga mais de uma vez |
| Upload malicioso | SVG, HTML, MIME forjado, arquivo grande, sem login ⇒ recusado |
| Injeção | `' OR 1=1`, `<script>`, `javascript:` em link, `=cmd` em planilha ⇒ neutralizado |
| SSRF | URL interna (`127.0.0.1`, `169.254.169.254`) ⇒ não buscada |
| Rate limit | exceder o limite de login/formulário/IA ⇒ 429 |
| Enumeração | e-mail inexistente e senha errada têm a mesma resposta |
| Logs | OTP, senha e token não aparecem no log |
| Exposição pública | recurso não publicado ⇒ 404 em todas as rotas que o expõem |
| Resposta | o JSON não contém hash, token nem campo interno |

## Projeto antigo sem testes

Não tente cobrir tudo. Ordem:

1. **Testes de caracterização** dos fluxos que dão dinheiro: gravam o comportamento atual (mesmo o errado) para que a refatoração não mude nada sem querer.
2. Testes de abuso de autorização nas rotas com dado pessoal ou dinheiro. Os que falharem viram itens do backlog de segurança.
3. A partir daí, regra 2: cada bug e cada mudança chega com teste.

## CI mínimo

O projeto tem um workflow que roda em todo push e PR. Exemplos (fixe as actions por SHA ao copiar):

```yaml
# Laravel
- run: composer install --no-interaction --prefer-dist
- run: vendor/bin/pint --test
- run: php artisan test
- run: composer audit

# Node (Next, Vite, Lovable)
- run: npm ci            # ou: bun install --frozen-lockfile
- run: npm run lint
- run: npx tsc --noEmit
- run: npm test
- run: npm audit --audit-level=high

# Flutter
- run: flutter pub get
- run: flutter analyze
- run: flutter test
```

Mais um passo de varredura de segredos (`gitleaks`) e `permissions: contents: read` no topo do workflow.

## O que o `docs/TESTES.md` do projeto precisa ter

- Comandos exatos para rodar tudo, um arquivo e um teste.
- Lista de invariantes com o teste que cobre cada um.
- Os fluxos E2E e quando rodam.
- O que o CI roda e o que bloqueia o deploy.
