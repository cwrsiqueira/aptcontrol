---
name: pode-subir
description: Publica as mudanças do projeto. Atualiza os docs de contexto, roda lint, tipos e testes, faz commit e push só se tudo passar, e confere o deploy. Use somente quando o usuário disser "Pode subir" ou pedir explicitamente para publicar.
---

# Pode subir

A frase "Pode subir" é a autorização para commit e push. Não peça nova confirmação, exceto para os passos marcados como manuais.

## 1. O que vai subir

1. `git fetch` e `git status -sb`.
2. Se o repositório estiver **atrás** do remoto: `git pull --rebase` (repositório com Lovable: `git pull` com merge). Conflito ⇒ pare e mostre.
3. Liste os arquivos que vão no commit.

## 2. Conferências que barram a subida

- Nenhum `.env`, chave, dump, backup, `node_modules`, `vendor`, dado real ou arquivo de runtime no diff.
- Procure no diff valores com cara de segredo: `sk_live_`, `sk-ant-`, `whsec_`, `sb_secret_`, `AIza`, `ghp_`, `BEGIN PRIVATE KEY`, `password=` com valor. Achou ⇒ pare.
- Projeto com Lovable: nenhuma migration nova, nenhuma rota nova em `src/routes/`, nenhum arquivo gerado editado.
- Passe o diff pelo [checklist de revisão](../../../docs/padroes/checklists/REVISAO-DE-MUDANCA.md).

## 3. Docs

- `docs/STATUS_PROJETO.md`: data, o que mudou, próximo passo.
- `docs/DECISOES.md` se houve decisão; `docs/SEGURANCA.md` se uma lacuna foi resolvida ou descoberta; `docs/DEPLOY.md` se o jeito de publicar mudou.

## 4. Verificação

Rode os comandos de verificação do `AGENTS.md` (lint, tipos, testes; build se o deploy depender dele).

Se algo falhar, **não** faça commit. Antes de responder, descubra se a falha veio da mudança atual ou já existia: se os arquivos apontados não estão no diff (`git diff --name-only` e arquivos novos), o problema já existia. Não use `git stash` para isso.

- **Veio da mudança atual:** corrija, rode de novo e siga.
- **Já existia:** explique em linguagem simples (o que é a verificação, quais arquivos, que não foi a mudança de agora e se afeta ou não o funcionamento) e pergunte: "Posso corrigir esses problemas antigos agora, antes de publicar? Responda **sim** ou **não**."
  - **sim:** corrija com a menor mudança possível, sem alterar o comportamento, rode tudo de novo e siga para o commit (mencione a correção na mensagem).
  - **não:** não publique. Se a mudança atual for só de documentação, pergunte se a pessoa quer publicar mesmo assim, avisando que as verificações continuam falhando. Registre a pendência em `docs/STATUS_PROJETO.md`.

## 5. Backup e ponto de volta

Se a mudança mexe no banco com dados reais (migration, importação, correção em massa) ou é uma entrega grande:

- Confirme que há backup do banco feito agora, ou que o próprio deploy faz o backup antes da migration (veja `docs/DEPLOY.md`). Sem backup: pare, explique em uma frase e pergunte como a pessoa quer fazer.
- Entrega grande: crie a marcação de volta `git tag antes-<assunto>-<AAAA-MM-DD>` e envie com o push.
- Diga à pessoa, em uma linha, como voltar se der errado.

## 6. Commit e push

- Mensagem em português que diz o porquê.
- Sem `--force`, sem `--no-verify`.
- `git push` para a branch do projeto.

## 7. Deploy

- Deploy automático: acompanhe o CI até terminar e informe o resultado.
- Deploy manual (VPS, SSH, hPanel): entregue os comandos com `cd` para a pasta do sistema, sem nenhum valor de segredo, e peça o resultado.
- Rode a conferência pós-deploy de `docs/DEPLOY.md`.

## 8. Relatório (até 5 linhas)

- Commit enviado (hash curto), testes (números), deploy (resultado), pendências manuais (migration, cache, variável nova).
- **Teste isto na tela:** 2 a 4 passos para a pessoa conferir o que mudou. Funcionalidade nova: acrescente esse roteiro em "Como testar na tela" do `docs/TESTES.md`.
