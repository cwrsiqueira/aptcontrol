---
name: auditar-contexto
description: Confere se os docs de contexto seguem o padrão do kit e continuam verdadeiros — arquivos obrigatórios, links, arquivos e comandos citados, atualidade e valores com cara de segredo. Use quando o usuário pedir "Audite o contexto", "confira os docs" ou depois de reorganizar docs.
---

# Auditar contexto

Leitura e relatório. Corrige só se o usuário pedir.

## 1. Arquivos obrigatórios

`AGENTS.md`, `CLAUDE.md` (com `@AGENTS.md`), `.cursor/rules/base.mdc`, `docs/STATUS_PROJETO.md`, `docs/SEGURANCA.md`, `docs/DOMINIO.md`, `docs/DEPLOY.md`, `docs/TESTES.md`, `docs/padroes/` e as skills `pode-subir`, `encerrar-sessao`, `se-atualize-contexto`, `auditar-contexto`, `revisar-seguranca`.

Aponte também placeholders esquecidos (`<...>`) nos docs do projeto.

## 2. Links relativos

Para cada `.md` e `.mdc` fora de `node_modules`, `vendor`, `.next`, `dist`: extraia links markdown que não começam com `http`, `mailto:` ou `#`, resolva a partir da pasta do arquivo (sem âncora) e liste os que não existem.

```bash
rg -o --no-heading -n '\]\(([^)#\s]+)' -r '$1' -g '*.md' -g '*.mdc' -g '!node_modules' -g '!vendor' .
```

## 3. Citações

Nos `AGENTS.md`, `CLAUDE.md`, `SEGURANCA.md`, `TESTES.md` e `DEPLOY.md`: confira com `rg` que classes, middlewares, funções, pastas, scripts de `package.json`/`composer.json` e comandos citados existem.

## 4. Atualidade

- `git fetch` e `git status -sb`: atrás ou à frente do remoto.
- Data do `STATUS_PROJETO.md` mais antiga que o último commit de código ⇒ desatualizado.
- Stack do `AGENTS.md` confere com as versões em `package.json`/`composer.json`/`pubspec.yaml`.
- `AGENTS.md` + `CLAUDE.md` acima de ~200 linhas somadas ⇒ sugerir mover detalhe para `docs/`.
- Regras contraditórias entre `AGENTS.md`, `CLAUDE.md`, `.cursor/rules/` e `docs/`.

## 5. Segredos

```bash
rg -n -i -g '*.md' -g '*.mdc' -g '*.json' -g '!package*.json' -g '!node_modules' \
  '(password|senha|secret|token|api[_-]?key)\s*[:=]\s*\S{6,}|sk_live_|sk-ant-|whsec_|sb_secret_|AIza[0-9A-Za-z_-]{20,}|ghp_|APP_USR-|BEGIN [A-Z ]*PRIVATE KEY' .
```

Também: credencial de seed por extenso (e-mail com senha ao lado), IP de produção com porta de painel. Nome de variável sem valor é permitido.

Conferir ainda: `git ls-files | rg '(^|/)\.env($|\.)' | rg -v example` deve voltar vazio.

## Relatório

Uma lista por categoria (faltando, placeholder, link quebrado, citação sem código, desatualizado, contradição, possível segredo), com arquivo e linha. Diga quando uma categoria está limpa.
