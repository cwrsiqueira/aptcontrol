# Adotar um projeto feito sem IA

Objetivo: a IA passa a trabalhar no projeto com segurança, sem quebrar o que está em produção. Uma etapa por vez; cada etapa termina com relatório e espera o "ok".

## Etapa 1 — Ler sem mudar nada

- Instalar o kit (pela IA com o `INSTALAR.md`, pelo script ou manual; nada é sobrescrito).
- Colar o prompt `prompts/ADOTAR-PROJETO-EXISTENTE.md` da pasta do kit (o prompt não é copiado para o projeto).
- A IA preenche, **a partir do código**: `AGENTS.md` (o que é, stack real, comandos que funcionam), `docs/STATUS_PROJETO.md`, `docs/DOMINIO.md` (regras que encontrou, marcadas como "a confirmar"), `docs/DEPLOY.md` (como está publicado hoje).
- Entrega o **inventário**: rotas (públicas, autenticadas, admin), tabelas com dado pessoal, integrações externas, onde estão os segredos (só nomes), o que tem e o que não tem teste.

## Etapa 2 — Confirmar o domínio com o dono

- Revisar `docs/DOMINIO.md` juntos. Regra confirmada vira "fato"; o resto fica como "decisão pendente".
- Nada de corrigir "bug" de regra de negócio antes desta etapa: pode ser o comportamento que o cliente espera.

## Etapa 3 — Rede de segurança

- Ligar CI com o que já existir (lint, testes, build), mesmo que poucos.
- Testes de caracterização dos 3–5 fluxos que dão dinheiro ou que, se quebrarem, param a operação.
- Testes de autorização nas rotas com dado pessoal e dinheiro. Os que falharem **não** são corrigidos ainda: viram itens no backlog.

## Etapa 4 — Diagnóstico de segurança

- Skill `revisar-seguranca` no projeto inteiro, contra [02-SEGURANCA.md](../02-SEGURANCA.md).
- Primeiro os vazamentos imediatos:
  1. Segredo no Git ou no frontend → **rotacionar** a chave (apagar do código não basta: o histórico guarda).
  2. Rota sem auth que expõe ou altera dado.
  3. IDOR em dado pessoal ou dinheiro.
  4. Debug ligado / stack trace em produção / `.env` acessível pela web.
  5. Webhook sem assinatura; preço vindo do cliente.
- `docs/SEGURANCA.md` do projeto com a matriz: item, situação (sim/parcial/não), onde está, prioridade.

## Etapa 5 — Corrigir em ordem

- Uma correção por mudança, com o teste que prova (vermelho antes, verde depois).
- P1 (vazamento ativo, dinheiro, dado pessoal) antes de P2 (headers, rate limit, logs) antes de P3 (melhorias).
- A cada correção: atualizar a matriz.

## Etapa 6 — Rotina normal

- A partir daqui, o projeto segue o fluxo comum: [REVISAO-DE-MUDANCA.md](REVISAO-DE-MUDANCA.md) em toda mudança e "Pode subir" para publicar.
- Melhorias de UI/UX e simplificação entram como tarefas normais, nunca misturadas com correção de segurança.

## O que não fazer

- Reescrever o projeto "do jeito certo" de uma vez.
- Atualizar todas as dependências de uma vez em produção.
- Trocar de framework ou de banco sem decisão escrita em `docs/DECISOES.md`.
- Apagar código que parece morto sem confirmar (rota chamada por app antigo, cron, integração).
