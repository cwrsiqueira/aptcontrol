# Regras de negócio

Estas regras já estão no código. Uma alteração de tela não as substitui.

## Situação do pedido

`orders.complete_order`:

- `0` — em aberto
- `1` — concluído
- `2` — cancelado

O saldo de um produto no pedido é a soma de `order_products.quant` daquele produto. Quantidade positiva é o que foi pedido. Quantidade negativa é entrega já registrada. Linha de planejamento não soma no saldo.

`OrderProductObserver` faz o seguinte com pedido em aberto ou concluído: se ainda existe saldo diferente de zero, o pedido fica em aberto; se todo saldo zerou, fica concluído. Pedido cancelado não muda por abrir a tela, salvar uma linha ou zerar saldo. A reabertura é só a ação explícita `OrderController::updateStatus`, que grava o log "Reabertura".

A tela de entregas do produto lista pedido em aberto com saldo ainda positivo. Data antiga continua visível enquanto o pedido estiver em aberto e houver saldo. A ordem é a data mostrada, depois zona, bairro e id.

## Entrega fracionada

No item do pedido, a quantidade por entrega define quantas saídas serão criadas. O sistema reparte o total e deixa o resto na última. Cada entrega tem quantidade, data prevista e paletes. A capacidade do palete é informada na entrega; a sobra ocupa mais um palete. `Helper::cargaTotalPaletes` soma os paletes e usa o menor valor entre tipo e quantidade para corrigir inversão de digitação.

## Carga

- O caminhão tem responsável pela manutenção, capacidade de paletes, modelo, placa e observação. Não tem motorista fixo.
- O motorista é informado na carga, ao adicionar. Ao incluir item numa carga existente, o motorista informado substitui o anterior.
- Se a quantidade passa da capacidade, o sistema abre outras cargas. A capacidade é respeitada.
- Zona tem nome e bairros (`Zone` e `ZoneBairro`).

A montagem fica em Produtos > Ver entregas (`/products/cc_products/{id}`). O badge mostra quantos paletes já estão em carga. O botão de adicionar fica desabilitado quando todos já entraram. Remover tira os `LoadItem` daquele item do pedido.

## Endereço

Entrega no cliente (CIF) mostra endereço, bairro e zona no romaneio e no PDF da carga. Retirada (FOB) não leva endereço de entrega. Campo vazio aparece como "não cadastrado". O romaneio é `orders/{order}/print`, título "Romaneio de Transporte".

## Produção pendente

O relatório lista produto em que `max(0, quantidade ainda a entregar - estoque)` é maior que zero, usando `Helper::day_delivery_calc`, da próxima entrega para a mais distante.

## Compras

O pedido de compra passa por orçamento, aprovação e finalização. Quem vê, orça, aprova ou conclui depende da permissão. O anexo do orçamento fica no disco `local`, aceita PDF, JPG ou PNG até 10 MB, e só é baixado por quem pode ver orçamentos.

## Lista de produtos

A lista pagina de 10 em 10, com `page` e, na busca, `q`. Cadastrar, estoque, entregas, editar, o nome do produto e excluir levam a origem em `from_page` e `from_q`, porque a tela de estoque já usa `page` e `q` para a própria lista. Voltar, cancelar, salvar e o redirect de excluir reabrem `products.index` com essa origem. A busca da própria lista, e o botão Limpar busca, continuam na primeira página.
