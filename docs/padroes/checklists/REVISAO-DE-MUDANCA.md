# Revisão de mudança (PR ou "Pode subir")

Dois minutos por mudança. Se algum item falhar, a mudança não sobe.

## Sempre

- [ ] A mudança faz só o que foi pedido.
- [ ] Lint, tipos e testes passando (números no relatório).
- [ ] Nenhum `.env`, segredo, dump, dado real ou arquivo de runtime no diff.
- [ ] Nenhuma dependência nova sem justificativa e conferência no registro.
- [ ] Docs tocados atualizados (`STATUS_PROJETO.md` no mínimo).

## Se tocou rota, action, endpoint ou tela com dado

- [ ] Auth exigida (ou rota listada como pública no `SEGURANCA.md`).
- [ ] Autorização por recurso no servidor; teste de IDOR.
- [ ] Entrada validada com schema; campos de gravação em allowlist.
- [ ] Resposta com campos explícitos.
- [ ] Erro genérico para o usuário, detalhe só no log, log sem dado sensível.

## Se tocou dinheiro, plano, cota ou estoque

- [ ] Valor calculado no servidor; teste com valor adulterado.
- [ ] Idempotência e atomicidade; teste de repetição/paralelo.

## Se tocou login, sessão ou permissão

- [ ] Rate limit; mensagens que não revelam conta; teste de sessão inválida e de papel no payload.

## Se tocou arquivo, URL externa ou importação

- [ ] Tipo por conteúdo, limite, nome gerado, disco privado.
- [ ] Nenhum fetch para URL do usuário sem allowlist.
- [ ] Import passa por staging e é idempotente.

## Se tocou IA

- [ ] Fato vem de tool, conta vem de código, saída validada por schema.
- [ ] Texto externo tratado como dado; suíte de conversas passando.

## Se tocou banco

- [ ] Migration reversível ou com plano de volta; backup antes se for destrutiva.
- [ ] Projeto Lovable: nenhuma migration criada fora do Lovable.
- [ ] Tabela nova: `empresa_id` + escopo/RLS + teste de isolamento.

## Se tocou UI

- [ ] Celular, teclado, contraste, estados de carregando/vazio/erro.
