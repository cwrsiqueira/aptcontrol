# Status do projeto

Estado curto para um chat novo. Confira o git antes de confiar neste arquivo (`git status -sb` e `git log -1 --oneline`).

## Onde paramos

- **Git:** `master` = `origin/master` no código de produto (`0a661ad` — soft delete + sábado). **Instalação do kit de contexto** ainda local (sem commit desta etapa).
- **Deploy em produção:** `v1.0.10` (soft delete + bloqueio de sábado) — confirmar se `./deploy.sh` já rodou no servidor.
- **Kit:** `docs/padroes/` versão **1.5.0**; skills `encerrar-sessao`, `se-atualize-contexto`, etc.

## O que já está no código

- Soft delete de produto; sábado/domingo sem entrega; fatias/baixa (v1.0.9+).
- Docs de IA alinhados ao kit (`AGENTS.md`, `CLAUDE.md`, `DOMINIO.md`, …).

## Próximo passo sugerido

1. Salvar a instalação do kit no GitHub (etapa 6 / “Pode subir” desta instalação).
2. Se ainda não rodou: `./deploy.sh` no servidor para `v1.0.10`.
3. Excluir TIJOLAO METADE na lista e validar sábado bloqueado.

## Pendências

- Commit/push dos docs do kit.
- Confirmar deploy `v1.0.10` em produção.
- Decisão pendente: prioridade de layout no celular (`docs/DOMINIO.md`).
