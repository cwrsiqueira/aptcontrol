# Domínio — regras de negócio

A IA não inventa regra. O que não está aqui como **fato confirmado** é perguntado ao dono antes de implementar. Detalhe operacional longo continua em [REGRAS-DE-NEGOCIO.md](REGRAS-DE-NEGOCIO.md).

Legenda: **[fato]** confirmado pelo dono · **[a confirmar]** encontrado no código · **[decisão pendente]** precisa de resposta.

## Glossário

| Termo | Significa | Não confundir com |
| --- | --- | --- |
| Pedido | Cabeçalho em `orders`, chave lógica `order_number` | Id numérico da tabela |
| Fatia / plano | Linha em `order_product_delivery_plans` | Linha de entrega já feita (`quant` negativa) |
| Saldo do item | Soma de `order_products.quant` do produto no pedido | Estoque do produto |
| Soft delete | Produto some da lista (`deleted_at`), histórico permanece | Apagar pedidos |

## Entidades e fonte da verdade

| Entidade | Fonte da verdade | Quem cria/altera |
| --- | --- | --- |
| Produto | `products` | Usuário com permissão; exclusão = soft delete |
| Pedido | `orders` + `order_products` | Usuário com permissão |
| Estoque atual | `products.current_stock` via lançamentos `product_stocks` | Tela de estoque |
| Carga | `loads` + `load_items` | Montagem em Entregas por produto |

## Regras

### Produto

1. **[fato]** Excluir produto é soft delete: some da lista e dos selects; pedidos/estoques mantêm `product_id`; nome ganha sufixo `[excluído #id]`.
2. **[fato]** Lista pagina de 10 em 10; origem `from_page`/`from_q` ao voltar da edição/estoque/exclusão.

### Pedido e saldo

3. **[fato]** `complete_order`: 0 aberto, 1 concluído, 2 cancelado. Cancelado só reabre por ação explícita com log.
4. **[fato]** Quantidade positiva = pedida; negativa = entrega feita. Plano de entrega não soma no saldo.
5. **[fato]** Observer: saldo ≠ 0 → aberto; saldo 0 → concluído (exceto cancelado).

### Entregas

6. **[fato]** Não há entrega no sábado nem no domingo; o sistema empurra para o próximo dia útil. Válido até segunda ordem.
7. **[fato]** Data prevista pode ser antes da previsão mínima (desde que dia útil).
8. **[fato]** Baixa consome fatias a partir da primeira; parcial fica na data planejada; “Já baixada” / parcial no editor.
9. **[fato]** Data antiga com saldo pendente = atraso visível, não baixa automática.

### Carga e compras

10. **[fato]** Motorista é por carga; capacidade do caminhão divide cargas automaticamente.
11. **[fato]** Compras: orçamento → aprovação → finalização; anexo privado PDF/JPG/PNG até 10 MB.

## Invariantes

- Permissão da ação é checada no servidor, não só no menu.
- Pedido cancelado não reabre por efeito colateral.
- Produto soft-deleted não volta à lista ativa sem restore explícito (não há tela de restore hoje).

## Fora de escopo

- App mobile dedicado (uso no celular, se houver, é o mesmo site).
- Multi-empresa / multi-tenant.
- Integração de pagamento online.

## Decisões pendentes

- **[decisão pendente]** Uso no celular: priorizar responsivo ou manter foco em desktop?
