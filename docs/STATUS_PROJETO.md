# Status do projeto

Estado curto para um chat novo. Confira o git antes de confiar neste arquivo (`git status -sb` e `git log -1 --oneline`).

## Onde paramos

- **Git:** `master` alinhada com `origin/master` após o push desta rodada. Confira com `git log -1 --oneline`.
- **Deploy em produção:** **pendente** — rodar `./deploy.sh` no servidor (migrações de soft delete e ajuste de datas de fim de semana).
- **Versão no painel (após deploy):** `v1.0.10` (`HomeController::systemInfo()`).

## O que já está no código

- Soft delete de produto: exclui mesmo com pedidos; histórico mantém `product_id`; nome libera com `[excluído #id]`.
- Sábado e domingo não são dias de entrega (cálculo, agendamento e editor).
- Migration one-shot empurra datas de fim de semana em pedidos abertos.
- Baixa / fatias / Detalhes do pedido (v1.0.9) permanecem.

## Próximo passo sugerido

1. No servidor: `./deploy.sh`.
2. Excluir o TIJOLAO METADE pela lixeira na lista de produtos e conferir o nome no pedido.
3. Validar planejamento: sexta → próxima fatia na segunda; alerta ao marcar sábado.

## Pendências

- Deploy em produção.
