---
name: revisar-seguranca
description: Revisa o diff atual, um módulo ou o projeto inteiro contra o baseline de segurança (OWASP Top 10:2025, API Top 10, LLM/Agentic Top 10, LGPD) e lista achados por gravidade com a correção sugerida. Use quando o usuário disser "Revise a segurança", "audite a segurança" ou antes de um lançamento.
---

# Revisar segurança

Leitura e relatório. Corrige só se o usuário pedir, um achado por vez, com teste.

## Escopo

- Sem indicação: o diff atual (`git diff` + arquivos novos).
- "do módulo X": a pasta do módulo e as rotas que apontam para ele.
- "do projeto": tudo, começando pelo inventário.

## 1. Inventário (projeto inteiro)

- Rotas: públicas, autenticadas, admin (`php artisan route:list`, pasta `app/`, `src/routes/`, Edge Functions, Server Actions).
- Tabelas com dado pessoal ou dinheiro.
- Integrações externas e webhooks.
- Onde ficam os segredos (só nomes) e o que tem prefixo público.

## 2. Passar pelo baseline

Use as seções de [docs/padroes/02-SEGURANCA.md](../../../docs/padroes/02-SEGURANCA.md) que o escopo pede (índice no topo; o projeto inteiro pede todas) e a seção da stack em [03-SEGURANCA-POR-STACK.md](../../../docs/padroes/03-SEGURANCA-POR-STACK.md). Para cada rota/ação/tela no escopo, responda:

1. Exige login? Autoriza o recurso (dono/empresa/permissão) no servidor?
2. Valida toda entrada com schema? Grava só campos de allowlist?
3. Algum valor de confiança vem do cliente (preço, papel, dono, status, cota)?
4. A resposta expõe campo além do necessário?
5. Tem rate limit se for abusável?
6. Erro vaza detalhe? Log grava dado sensível?
7. Busca URL, abre arquivo ou executa comando com entrada do usuário?
8. Tem corrida (saldo, cota, estoque)?
9. Webhook valida assinatura e deduplica?
10. Chama IA? Fato vem de tool, saída validada, texto externo tratado como dado?

Conferências automáticas que valem rodar:

- `git ls-files` sem `.env`; busca de segredo no código e no histórico recente (`git log -p -S 'sk_live_' --all` etc.).
- Código de cliente sem `service_role`/`sb_secret_`/import do client admin.
- `npm audit` / `composer audit` / `pip-audit`.
- `curl -I` em produção para headers; `curl` em `/.env` e `/.git/config`.

## 3. Relatório

Tabela por gravidade:

| Gravidade | Achado | Onde (arquivo:linha) | Risco em uma frase | Correção sugerida | Teste que prova |
| --- | --- | --- | --- | --- | --- |

- **Crítica:** segredo exposto, rota sem auth com dado pessoal/dinheiro, IDOR, preço do cliente, webhook sem assinatura, RLS desligada.
- **Alta:** sem rate limit em login, upload inseguro, mass assignment, debug em produção, SSRF.
- **Média:** headers ausentes, CORS largo, log com dado sensível, falta de auditoria.
- **Baixa:** melhorias.

Rotule cada achado: **fato confirmado** (viu no código) ou **hipótese a validar** (depende de config que não está no repositório). Termine com as 3 primeiras correções recomendadas. Registre os achados que não forem corrigidos agora em **Lacunas conhecidas** do `docs/SEGURANCA.md` (sem repetir os que já estão lá) e marque como "corrigida em <data>" as que deixaram de existir. A matriz "Como cumprimos o baseline" só muda se o usuário pedir.
