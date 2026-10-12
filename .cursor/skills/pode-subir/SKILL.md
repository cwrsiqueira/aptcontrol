---
name: pode-subir
description: >-
  Atualiza a documentação que a rodada mudou, roda php artisan test e, se
  passar, faz commit e push. Não executa o deploy local: o script fica no
  servidor. Use quando o usuário disser "Pode subir", "sobe isso", "pode
  publicar" ou "manda pro git".
---

# Pode subir

Fecha uma rodada neste repositório. Siga a ordem. Não pule os testes.

```
Progresso:
- [ ] 1. Documentação, só se a rodada mudou regra, segurança, deploy ou o texto do painel
- [ ] 2. php artisan test
- [ ] 3. Commit e push, só com os testes verdes
- [ ] 4. Resumo e o que rodar no servidor
```

## 1. Documentação

Não crie arquivo novo de contexto. Atualize só o que a rodada alterou:

- Regra que o código passa a decidir: `docs/REGRAS-DE-NEGOCIO.md`
- O que a tela não pode mostrar ou o que o servidor precisa validar: `docs/SEGURANCA.md`
- Forma de publicar: `docs/DEPLOY.md`
- Texto que a pessoa vê no painel: `HomeController::systemInfo()`, em português, sem jargão. Suba a versão só quando esse texto mudar.

Se nada disso mudou, não edite documentação.

## 2. Testes

```bash
php artisan test
```

Se algum teste falhar, pare. Mostre a falha, não faça commit e não faça push.

## 3. Commit e push

Só depois dos testes verdes. Veja `git status` e `git diff` antes de adicionar arquivos.

Não inclua `.env`, `database/database.sqlite`, journal, backup (`*.backup`, `*.backup.*`), `storage/framework/sessions`, `storage/framework/views`, `storage/logs` nem arquivo de pré-visualização. Se o status mostrar algum desses, deixe de fora e avise.

Mensagem curta, em português, sobre o porquê. Nesta máquina o shell é PowerShell; use `git commit -m "mensagem"`. Não use heredoc de bash.

```bash
git add <arquivos da rodada>
git commit -m "mensagem"
git push
```

Se a branch ainda não rastreia o remoto e `origin` existe, use `git push -u origin HEAD`. Se não houver remoto, avise e pare antes do push.

Não altere `git config`. Não use `--no-verify`, `--force` nem push forçado. Não faça commit vazio. Não rode `deploy.sh`.

## 4. Resumo

Em tópicos curtos: o que mudou para quem usa o sistema, o hash do commit e se o push foi feito.

O deploy não roda aqui. No servidor de produção, depois do push:

```bash
./deploy.sh
```

Esse script faz backup do SQLite, entra em manutenção, puxa o código, instala dependências, migra e recria o cache de rotas. A primeira vez no servidor precisa de `chmod +x deploy.sh`.
