# CLAUDE.md

As regras do projeto estão no AGENTS.md (importado abaixo), que também é lido por Cursor e outras ferramentas. Aqui fica o mapa para navegar no código.

@AGENTS.md

## Mapa do código

- Rotas: `routes/web.php` (quase tudo atrás de `auth`)
- Models: `app/` (sem pasta `app/Models`) — `Product`, `Order`, `Order_product`, `OrderProductDeliveryPlan`, `Client`, `Seller`, `Truck`, `Load`, `LoadItem`, `Zone`, `PurchaseOrder`, etc.
- Controllers: `app/Http/Controllers/`
- Fatias abertas / relatório: `app/Reports/PendingDeliveryRows.php`
- Helper: `app/Helpers/Helper.php` (`get_permissions`, `day_delivery_*`, `isNonDeliveryDay`, `nextBusinessDay`, `saveLog`, `cargaTotalPaletes`)
- Views: `resources/views/`
- Permissões: slugs em `permission_items`; checagem no controller
- Skills: `.cursor/skills/` (espelho em `.claude/skills/`)
- Contexto resumido: `.cursor/rules/aptcontrol-context.mdc` e `docs/CONTEXTO.md`

## Padrão de uma ação/rota nova

1. Autenticar (`auth`) e obter o usuário da sessão.
2. Validar entrada (`Validator` / `$request->only()`).
3. Autorizar com slug (`Helper::get_permissions()` ou `is_admin`).
4. Aplicar regra de negócio no backend.
5. Responder / redirecionar sem vazar detalhe interno.
6. Teste feature: caminho feliz + sem permissão + entrada inválida, quando couber.

## Comandos extras

```bash
php artisan test
php artisan test --filter=ProductSoftDeleteTest
php artisan migrate
php artisan route:clear
```

## Cuidados

- Não executar `deploy.sh` na cópia local.
- Soft delete em `Product`: listagens usam escopo padrão; histórico usa `withTrashed()`.
- Sábado e domingo não são dias de entrega (`Helper::isNonDeliveryDay`).
