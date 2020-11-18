# aptcontrol
Controle de Vendas Amapá Telhas

BETA TESTE 1.07 //
18/112020 - 19:23

- Alterações nos formulários de Efetuar e Editar Pedidos
- Alterações nas inclusões e deleções das linhas de produtos de pedidos
- Reformulação do código para geração de relatórios e alterações visuais
- outros

BETA TESTE 1.06 //
27/10/2020 - 12:18

- Alteração dos relatórios de CC Produtos, CC Clientes, Relatório
- Correções de erros nos relatórios CC Produtos, CC Clientes, Relatório
- Alterações no formulário de criar pedidos
- Alteração na lógica geral do sistema para demonstração nos relatórios
- Alteração na lógica do banco de dados para apresentar o saldo real dos produtos
- Efetuar a seguinte alteração no banco de dados:
    - UPDATE order_products SET delivery_date = '1970-01-01' WHERE quant < 0;
    - para que o sistema ajuste a posição das entregas em relação aos pedidos

BETA TESTE 1.05 //
29/07/2020 - 23:54

- Opções de consulta na C/C Clientes
- Botão Excluir cliente
- Ajuste do sistema de consultas no menu pedido
- Bug que não aparecia os pedidos cancelados (resolvido)
- Incluído nos relatórios data, contato e atrasados em vermelho
- Voltar da pesquisa pra tela anterior sem perder a pesquisa

BETA TESTE 1.04 //
26/07/2020 - 15:46

- Menu Produtos: - inclusão botão excluir;
- Menu Pedidos: - Alteração do botão Concluir: - Inclusão das opções Entregar Pedido e Cancelar Pedido
- Alteração da visualização do pedido após entregue ou cancelado

BETA TESTE 1.03 //
22/07/2020 - 02:44

- Adiciona botão excluir usuário

BETA TESTE 1.02 //
21/07/2020 - 22:44

- Ajustes de bugs nas permissões

BETA TESTE 1.01 // 
17/07/2020 - 16:24

- Correção de erros do deploy

VERSÃO BETA TESTE 1.0 //
17/07/2020 - 04:22

- Lançamento da versão
- Ajustes no sistema de Login e permissões
- Inclusão botão Editar Pedido
- Alteração botão Visualizar Pedido
- outros