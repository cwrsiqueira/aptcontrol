# APTControl — instruções para agentes

Sistema interno de pedidos, produtos, clientes, vendedores, entregas, cargas e compras. A interface e os textos novos ficam em português.

Antes de alterar o sistema, leia:

1. [docs/CONTEXTO.md](docs/CONTEXTO.md) — stack, módulos e permissões
2. [docs/REGRAS-DE-NEGOCIO.md](docs/REGRAS-DE-NEGOCIO.md) — o que o código já decide
3. [docs/SEGURANCA.md](docs/SEGURANCA.md) — o que não pode ir para a tela nem ser aceito sem validação
4. [docs/DEPLOY.md](docs/DEPLOY.md) — como a produção é atualizada

## Ao implementar

- Confira a permissão no controller. Esconder o botão não autoriza a ação.
- Valide no servidor tudo o que chega da URL, do formulário, do JSON ou do arquivo.
- Mantenha a regra de negócio no backend, mesmo quando a tela já impede o clique.
- Não grave `.env`, `database/database.sqlite`, sessão, cache de view nem backup do banco.
- Não rode `deploy.sh` nesta máquina. O script é só no servidor de produção.
- Não coloque senha, chave, caminho de servidor ou dado de cliente em documentação nova, chat ou resposta de erro.
- O texto do painel vem de `HomeController::systemInfo()` e é escrito para quem usa o sistema, sem jargão técnico.
