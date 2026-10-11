# Contexto

O APTControl controla pedidos, produtos, estoque, clientes, vendedores, entregas, cargas e compras. Quem usa o sistema vê a interface em português.

## Stack

- Laravel 12, PHP 8.2, SQLite
- Bootstrap 4, jQuery e Vue 2
- PDF com Dompdf

O banco local é o arquivo `database/database.sqlite`. Ele não entra no Git.

## Onde está o código

- Models em `app/` (`Product`, `Order`, `Order_product`, `Client`, `Seller`, `Truck`, `Load`, `LoadItem`, `Zone`, `PurchaseOrder` e os demais). Não há pasta `app/Models`.
- Controllers em `app/Http/Controllers`.
- Telas em `resources/views`.
- Rotas em `routes/web.php`. Quase todas exigem login (`auth`).
- Ajuda comum em `App\Helpers\Helper`: `get_permissions`, `day_delivery_calc`, `day_delivery_recalc`, `saveLog`, `cargaTotalPaletes`.

O pedido usa `order_number` (texto) como chave lógica. Em `order_products`, a coluna `order_id` guarda esse número, não o id numérico da tabela `orders`.

## Módulos

- **Produtos** — cadastro, lista paginada, estoque e entregas do produto (`cc_product`).
- **Estoque** — lançamentos em `product_stocks`. O saldo atual do produto acompanha esses lançamentos.
- **Pedidos** — capa do pedido, itens, entregas parciais e romaneio.
- **Entregas por produto** — o que ainda falta sair, com data, zona, bairro e montagem de carga.
- **Logística** — caminhões, zonas, bairros e cargas.
- **Clientes, categorias e vendedores.**
- **Compras** — pedido de compra, orçamentos e anexo privado.
- **Relatórios** — entregas e produção pendente, na tela e em PDF.
- **Permissões e log** — o que cada usuário pode abrir e o registro das ações.

Operações pontuais de tela passam por `AjaxController`, também atrás de login.

## Permissões

O slug fica em `permission_items`. O vínculo do usuário fica em `permission_links`.

Há dois caminhos, e os dois precisam ser respeitados:

- `Helper::get_permissions()` devolve os slugs do usuário logado. O controller compara com `in_array` antes de gravar, excluir ou abrir a ação.
- `Auth::user()->is_admin` é verdadeiro quando `confirmed_user === 1`. O administrador passa nas gates e nas checagens dos controllers.

A gate (`menu-produtos` e as outras em `AuthServiceProvider`) libera o menu. Ela não substitui a checagem da ação (`products.update`, `order_products.delivery` e o restante). Rota com `auth` só garante que existe um usuário logado.

## Versão no painel

O número e o texto do Dashboard saem de `HomeController::systemInfo()`. Esse texto é para quem usa o sistema. Detalhe técnico fica no Git e nesta pasta, não nesse changelog.
