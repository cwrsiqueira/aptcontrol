# Status do projeto

Estado curto para um chat novo. Confira o git antes de confiar neste arquivo (`git status -sb` e `git log -1 --oneline`).

## Onde paramos

- **Git:** `master` = `origin/master` em `ca3737f`. **Já no GitHub.**
- **Tag de retorno:** `pre-ux-fatias-baixadas-e6b049b` → commit `e6b049b` (estado anterior à publicação).
- **Backup local:** `database/database.sqlite.backup.local_20261121_115725` (não vai pro Git).
- **Deploy em produção:** código **já no GitHub**; o `./deploy.sh` **ainda precisa rodar no servidor** (SSH daqui não alcançou o host; Git Hostinger recusou diretório não vazio; cron novo não executou).
- **Versão no painel (após deploy):** `v1.0.9`.

## O que já está no código

- Fatias cobertas pela baixa marcadas no editor (“Já baixada” / parcial) e resumo no Detalhes do pedido.
- `PendingDeliveryRows::planStatuses` + `openPlans`.
- Skill: `.cursor/skills/pode-subir`.

## Próximo passo sugerido

No servidor, na pasta do site:

```bash
./deploy.sh
```

Isso faz backup do SQLite, `git pull` de `ca3737f`, composer, migrate e cache. Depois validar editor do pedido #5392 (badges Já baixada / Parcial).

## Pendências

- Executar `./deploy.sh` em produção (fora desta máquina, se o acesso SSH/hPanel for local).
