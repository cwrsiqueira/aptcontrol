# Testes — APTControl

Estratégia comum em [padroes/07-TESTES.md](padroes/07-TESTES.md).

## Comandos

```bash
php artisan test
php artisan test --filter=OrderProductDeliveryPlanningTest
php artisan test --filter=ProductSoftDeleteTest
```

## O que o "Pode subir" exige

Neste projeto: `php artisan test`. Falhou → sem commit/push.

Não há pipeline CI separado documentado; a trava é a skill e a disciplina local.

## Invariantes cobertos (exemplos)

| Invariante | Teste |
| --- | --- |
| Soft delete de produto com pedido mantém vínculo e some da lista | `ProductSoftDeleteTest` |
| Rejeita entrega no domingo e no sábado | `OrderProductDeliveryPlanningTest` |
| Sexta → próxima fatia na segunda | `test_skips_weekend_when_the_next_delivery_is_calculated` |
| Baixa consome fatias da primeira; parcial no editor | `test_large_multi_slice_partial_delivery_...` |
| Fluxo de compras / anexo | `PurchaseOrderWorkflowTest` |
| Relatório de entregas | `ReportDeliveryTest` |

## Como testar na tela

| Funcionalidade | Passos | Resultado esperado |
| --- | --- | --- |
| Excluir produto com histórico | Lista → lixeira → confirmar | Some da lista; pedido ainda mostra o nome |
| Bloqueio de sábado | Item do pedido → data no sábado | Alerta / erro de validação |
| Deploy | No servidor: `./deploy.sh` | Painel em v1.0.10; migrations ok |

## Convenções

- Feature tests com `RefreshDatabase`.
- Admin de teste: `confirmed_user = 1`.
- Não alterar teste para “fazer passar” sem ajustar a regra documentada.
