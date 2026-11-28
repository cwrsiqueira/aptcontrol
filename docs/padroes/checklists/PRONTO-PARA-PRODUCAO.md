# Pronto para produção

Use antes do primeiro lançamento público e revise a cada mudança grande. Item que não se aplica: marque "n/a" com o motivo no `docs/SEGURANCA.md`.

## Acesso e dados

- [ ] Toda rota fora da lista de públicas exige login; teste prova 401/redirect.
- [ ] Toda leitura/escrita filtra por dono ou empresa; teste de IDOR e de vazamento entre empresas passando.
- [ ] Papel, dono, empresa e preço nunca vêm do payload.
- [ ] Respostas com lista explícita de campos; nada de hash, token ou campo interno.
- [ ] Supabase: RLS ligada em todas as tabelas expostas; Security Advisor sem alerta crítico.

## Autenticação

- [ ] Rate limit no login e na recuperação/OTP.
- [ ] Mesma resposta para conta inexistente e senha errada.
- [ ] Sem senha padrão em lugar nenhum; seed lê do ambiente.
- [ ] Cookie de sessão `HttpOnly`, `Secure`, `SameSite`.
- [ ] MFA para admin.

## Segredos

- [ ] `.env` fora do Git; `git log` sem segredo histórico (se houver, foi rotacionado).
- [ ] Nada secreto em `NEXT_PUBLIC_*`, `VITE_*`, `EXPO_PUBLIC_*`, assets ou HTML.
- [ ] Chaves de produção diferentes das de dev, uma por componente.
- [ ] Limite de gasto e alerta nos provedores pagos.

## Dinheiro

- [ ] Preço e total calculados no servidor.
- [ ] Webhook com assinatura obrigatória e dedupe por ID de evento.
- [ ] Decremento de saldo/cota/vaga atômico.
- [ ] Modo live testado com valor real mínimo e estorno.

## Configuração

- [ ] Debug desligado; erro genérico para o usuário.
- [ ] HTTPS + HSTS; CSP em modo enforce; nosniff; frame-ancestors; Referrer-Policy; sem versão do servidor.
- [ ] `.env`, `.git`, backups e `storage/` inacessíveis pela web (testado com `curl`).
- [ ] Firewall na VPS; banco sem porta pública; painéis administrativos protegidos.
- [ ] CORS com origens explícitas.

## Arquivos

- [ ] Upload autenticado, tipo por conteúdo, SVG bloqueado, limite de tamanho, nome gerado, disco privado.

## Abuso

- [ ] Rate limit em login, formulários públicos, upload, IA, envio de mensagem.
- [ ] Rate limit em store compartilhado se houver mais de uma instância.
- [ ] Paginação com teto.

## Dependências e CI

- [ ] Lockfile versionado; `audit` sem alta/crítica.
- [ ] CI roda lint, tipos e testes; deploy bloqueado se falhar.
- [ ] Alerta de dependência vulnerável ligado.
- [ ] Varredura de segredos ativa.

## Operação

- [ ] Log de segurança (401/403/429/webhook) sem dado sensível.
- [ ] Monitor externo de disponibilidade com alerta.
- [ ] Backup automático, criptografado, fora do servidor, **restauração testada**.
- [ ] `docs/DEPLOY.md` com passo a passo de publicação, volta (rollback) e rotação de chaves.

## LGPD

- [ ] Política de privacidade e termos publicados; aceite registrado.
- [ ] Exclusão de conta apaga dados e arquivos; retenção definida e automática.
- [ ] Dado de dev e teste é sintético.
- [ ] Roteiro de incidente conhecido ([INCIDENTE.md](INCIDENTE.md)).

## UI/UX

- [ ] Fluxo principal testado no celular (360 px) e com teclado.
- [ ] Contraste AA; estados de carregando, vazio e erro.
- [ ] Core Web Vitals das páginas públicas dentro da meta.
