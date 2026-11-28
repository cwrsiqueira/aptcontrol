# Design — APTControl

Padrões gerais: [padroes/06-UI-UX.md](padroes/06-UI-UX.md).

## Público e aparelho

- Uso principal: **escritório no computador**.
- Celular: possível, sem garantia de layout polido em todas as telas.

## Linguagem visual

- Bootstrap 4 + CSS do projeto (`public/css/`).
- Textos da interface em **português do Brasil**.
- Painel/changelog: linguagem de quem usa o sistema (`HomeController::systemInfo()`), sem jargão.

## Padrões de tela

- Lista de produtos: paginação, busca, ícones de ação (estoque, entregas, editar, excluir).
- Confirmação antes de excluir (confirm do navegador).
- Entregas: badges de carga e de fatia (Já baixada / Parcial).
- PDF: romaneio e carga com “não cadastrado” quando faltar endereço.

## Não fazer

- Não redesenhar o sistema inteiro sem pedido.
- Não trocar Bootstrap/Vue sem aprovação.
- Não colocar dado sensível em tooltip, HTML comentado ou PDF público.
