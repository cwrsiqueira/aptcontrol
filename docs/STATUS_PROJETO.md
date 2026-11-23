# Status do projeto

Estado curto para um chat novo. Confira o git antes de confiar neste arquivo (`git status -sb` e `git log -1 --oneline`).

## Onde paramos

- **Git:** `master` = `origin/master` em `342a8ac` (working tree limpa após este fechamento de docs). **Já no GitHub.**
- **Deploy em produção:** **feito** (confirmado pelo usuário em 23/11/2026). Painel deve mostrar `v1.0.9`.
- **Último código de produto:** `ca3737f` — fatias já baixadas/parciais no editor e resumo no Detalhes do pedido.
- **Tag de retorno (pré-UX):** `pre-ux-fatias-baixadas-e6b049b` → `e6b049b`.
- **Backup local (esta máquina):** `database/database.sqlite.backup.local_20261121_115725` (não vai pro Git). Em produção, o `./deploy.sh` gerou o `.backup.*` no servidor.
- **Versão no painel:** `v1.0.9` (`HomeController::systemInfo()`).

## O que já está no código (e no ar)

- Baixa consome fatias a partir da primeira (`PendingDeliveryRows::openPlans` / `planStatuses`).
- Editor de entregas: badge **Já baixada** (somente leitura) e **Parcial** com o que falta; planos continuam no banco.
- Detalhes do pedido: contadores de baixadas / abertas; lista só fatias abertas.
- Entregas por produto / relatório usam a mesma conta; data antiga com saldo = atraso, não baixa.
- Skill: `.cursor/skills/pode-subir`.

## Próximo passo sugerido

1. Validar no ar (pedido grande fracionado, ex. #5392 / TIJOLÃO): editar item → badges Já baixada / Parcial; Detalhes → “N já baixadas” / “N abertas”; lista `cc_product` sem datas 100% cobertas.
2. Se a cliente ainda achar “entrega antiga” com saldo pendente, alinhar expectativa: isso é atraso documentado em `REGRAS-DE-NEGOCIO.md`, não regressão do filtro.

## Pendências

- Nenhuma pendência de código ou deploy aberta.
