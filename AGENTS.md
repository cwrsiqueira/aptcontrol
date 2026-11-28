# APTControl — instruções para agentes

Sistema interno de pedidos, produtos, clientes, vendedores, entregas, cargas e compras. A interface e os textos novos ficam em português. Persona principal: equipe do escritório no computador (celular possível, não garantido). **Produção no dia a dia — alterações com cuidado.**

Estado atual e próximo passo: [docs/STATUS_PROJETO.md](docs/STATUS_PROJETO.md).

## Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Bootstrap 4, jQuery, Vue 2, Blade |
| Banco | SQLite (`database/database.sqlite`, fora do Git) |
| PDF | Dompdf |
| Testes | PHPUnit (`php artisan test`) |
| Hospedagem | Servidor próprio; publicação com `./deploy.sh` |
| Pacotes | Composer (e npm só se o frontend pedir) — não trocar sem aprovação |

## Comandos

```bash
composer install
php artisan serve          # ou XAMPP apontando para public/
php artisan test           # antes de qualquer commit ("Pode subir")
php artisan test --filter=NomeDoTeste
php artisan migrate
```

Antes de commit/push: `php artisan test` (skill `.cursor/skills/pode-subir`).

## Leia antes de alterar

| Antes de mexer em | Leia |
| --- | --- |
| qualquer coisa (chat novo) | [docs/STATUS_PROJETO.md](docs/STATUS_PROJETO.md), [docs/padroes/01-PRINCIPIOS.md](docs/padroes/01-PRINCIPIOS.md) |
| stack, módulos, permissões | [docs/CONTEXTO.md](docs/CONTEXTO.md) |
| regra de negócio, entrega, carga, compra | [docs/DOMINIO.md](docs/DOMINIO.md) e [docs/REGRAS-DE-NEGOCIO.md](docs/REGRAS-DE-NEGOCIO.md) |
| auth, permissão, dado pessoal, arquivo | [docs/SEGURANCA.md](docs/SEGURANCA.md) e [docs/padroes/02-SEGURANCA.md](docs/padroes/02-SEGURANCA.md) |
| detalhes Laravel | [docs/padroes/03-SEGURANCA-POR-STACK.md](docs/padroes/03-SEGURANCA-POR-STACK.md) |
| tela / texto da interface | [docs/DESIGN.md](docs/DESIGN.md) e [docs/padroes/06-UI-UX.md](docs/padroes/06-UI-UX.md) |
| testes | [docs/TESTES.md](docs/TESTES.md) e [docs/padroes/07-TESTES.md](docs/padroes/07-TESTES.md) |
| publicar | [docs/DEPLOY.md](docs/DEPLOY.md) |
| por que algo é assim | [docs/DECISOES.md](docs/DECISOES.md) |
| rituais com IA | [docs/padroes/08-TRABALHO-COM-IA.md](docs/padroes/08-TRABALHO-COM-IA.md) |

## Ao implementar

- Confira a permissão no controller. Esconder o botão não autoriza a ação.
- Valide no servidor tudo o que chega da URL, do formulário, do JSON ou do arquivo.
- Mantenha a regra de negócio no backend, mesmo quando a tela já impede o clique.
- Não grave `.env`, `database/database.sqlite`, sessão, cache de view nem backup do banco.
- Não rode `deploy.sh` nesta máquina. O script é só no servidor de produção.
- Não coloque senha, chave, caminho de servidor ou dado de cliente em documentação nova, chat ou resposta de erro.
- O texto do painel vem de `HomeController::systemInfo()` e é escrito para quem usa o sistema, sem jargão técnico.
- Pedido usa `order_number` (texto) como chave lógica; em `order_products.order_id` guarda esse número.

## Regras invioláveis

1. **O servidor decide.** Entrada validada no backend. Identidade e permissão vêm da sessão (`Auth` + `Helper::get_permissions()` / `is_admin`), nunca do payload.
2. **Quase toda rota exige login** (`auth`). Exceções só as de autenticação Laravel. Autorização por slug em cada ação sensível.
3. **Nenhum segredo no cliente, no Git, nos docs, nos logs ou em comandos.**
4. **Simples primeiro.** Sem dependência nova sem justificar e pedir aprovação.
5. **Regra de negócio e rota sensível com teste** quando a mudança as toca. Não alterar teste só para passar.
6. Produção no dia a dia: preferir mudança pequena, testada, com caminho de volta claro.

## Como conversar

- Discorde quando houver opção melhor; não concorde só para agradar.
- Resposta ambígua: confirme antes de agir.
- Conversa longa: sugira **Encerre a sessão** e um chat novo com **Me atualize**.

## Frases úteis

- **Me atualize** — skill `se-atualize-contexto`
- **Revise a segurança** — skill `revisar-seguranca`
- **Pode subir** — skill `pode-subir` (testes → commit → push; deploy no servidor)
- **Encerre a sessão** — skill `encerrar-sessao`

## Pare e pergunte quando

- a regra não está em `docs/DOMINIO.md` / `REGRAS-DE-NEGOCIO.md` ou é ambígua;
- a solução só existe no frontend;
- a ação é irreversível ou toca produção, banco de produção ou dado real de cliente.
