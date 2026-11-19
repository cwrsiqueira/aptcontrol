# Status do projeto

Estado curto para um chat novo. Confira o git antes de confiar neste arquivo (`git status -sb` e `git log -1 --oneline`).

## Onde paramos

- **Git:** `master` = `origin/master`, working tree **limpa** (após este fechamento). **Já no GitHub.** **Não precisa Pode subir** se a tree continuar limpa.
- **Último deploy de código (produto):** `ad2744d` — entregas planejadas baixam na ordem; data prevista flexível (exceto domingo); relatório por fatia; botão Topo; painel `v1.0.8`.
- **Último push de docs/contexto:** `07505aa` — STATUS, CONTEXTO, AGENTS e regra Cursor para retomada.
- **Deploy em produção:** no servidor, `./deploy.sh` (puxa o que ainda não entrou). Este chat **não** confirmou a execução no VPS.
- **Versão no painel:** `v1.0.8` (`HomeController::systemInfo()`).

## O que já está no código

- Quantidade entregue desconta a partir da primeira entrega planejada (`app/Reports/PendingDeliveryRows.php`: `openPlans`, `describe`, `expand`).
- Telas: Entregas por produto, Relatório de entregas (tela/CSV/PDF) e Detalhes do pedido usam a mesma conta.
- Entregar produto mostra, antes de confirmar, o que encerra e o que fica pela metade.
- Data prevista editável, inclusive antes da previsão mínima; domingo bloqueado.
- Skill do projeto: `.cursor/skills/pode-subir`. **Não** há skill `se-atualize-contexto` neste repo (existe em outros projetos seus).

## Próximo passo sugerido

1. Confirmar no servidor se `./deploy.sh` já rodou após `ad2744d` (e puxar docs se ainda não).
2. Validar no ar: pedido com várias entregas → entrega parcial/múltipla → três telas + texto antes de confirmar.
3. Se quiser retomada automática no chat novo, criar `.cursor/skills/se-atualize-contexto` apontando para este arquivo.

## Pendências

- Nenhuma pendência de código aberta nesta máquina.
- Deploy em produção: confirmar fora deste chat.
