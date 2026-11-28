# Status do projeto

Estado curto para um chat novo. Confira o git antes de confiar neste arquivo (`git status -sb` e `git log -1 --oneline`).

## Onde paramos

- **Git:** `master` alinhada com `origin/master` após o push desta rodada. Confira com `git log -1 --oneline`.
- **Deploy em produção:** **pendente** — rodar `./deploy.sh` no servidor (v1.0.10 produto/sábado + v1.0.11 segurança + `composer install` pelo Dompdf).
- **Versão no painel (após deploy):** `v1.0.11`.

## O que já está no código

- Soft delete de produto; sábado/domingo sem entrega.
- Auto-cadastro público desligado; `confirmed_user` fora do fillable.
- `cc_product` 404 para produto soft-deleted; testes de abuso (`SecurityHardeningTest`).
- Dompdf ≥ 3.1.6; kit de contexto em `docs/padroes/`.

## Próximo passo sugerido

1. No servidor: `./deploy.sh`.
2. Conferir que `/register` não abre.
3. Excluir TIJOLAO METADE e validar bloqueio de sábado.

## Pendências

- Deploy em produção.
- Demais advisories Composer (fora Dompdf) em janela de manutenção.
