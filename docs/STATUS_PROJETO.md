# Status do projeto

Estado curto para um chat novo. Confira o git antes de confiar neste arquivo (`git status -sb` e `git log -1 --oneline`).

## Onde paramos

- **Git:** após este fechamento, `master` deve estar alinhada com `origin/master`.
- **Tag de retorno:** `pre-ux-fatias-baixadas-e6b049b` aponta para `e6b049b` (estado antes desta publicação).
- **Último deploy de código (produto):** marcação de fatias já baixadas/parciais no editor e resumo no Detalhes do pedido; painel `v1.0.9`.
- **Backup produção (pré-deploy):** `database/database.sqlite.backup.pre_ux_fatias_20261121` no servidor.
- **Versão no painel:** `v1.0.9` (`HomeController::systemInfo()`).

## O que já está no código

- Quantidade entregue desconta a partir da primeira entrega planejada (`PendingDeliveryRows::openPlans` / `planStatuses`).
- Editor de entregas marca fatias cobertas (“Já baixada”) e parciais; Detalhes do pedido separa abertas e baixadas.
- Telas: Entregas por produto, Relatório de entregas e Detalhes do pedido usam a mesma conta.
- Skill do projeto: `.cursor/skills/pode-subir`.

## Próximo passo sugerido

1. Validar no ar: editar item com baixas (ex. pedido #5392) e conferir badges Já baixada / Parcial.
2. Confirmar na lista e no Detalhes do pedido o resumo de fatias abertas.

## Pendências

- Nenhuma pendência de código aberta nesta máquina.
