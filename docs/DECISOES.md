# Decisões

Registro curto do porquê de escolhas importantes.

## Endurecimento de acesso (2026-11)

- **Decisão:** desligar `/register`; tirar `confirmed_user` do `$fillable`; Dompdf ≥ 3.1.6.
- **Por quê:** revisão de segurança — sistema interno em produção não deve aceitar auto-cadastro nem promoção a admin via formulário.
- **Consequência:** novos usuários só pelo fluxo interno; testes em `SecurityHardeningTest`.

## Soft delete de produto (2026-11)

- **Decisão:** excluir produto marca `deleted_at` e libera o nome com sufixo; não apaga linhas de pedido.
- **Por quê:** cliente pediu excluir item com histórico; hard delete quebraria FKs ou apagaria pedidos.
- **Consequência:** histórico continua legível via `withTrashed()`; produto some da lista ativa.

## Sábado sem entrega (2026-11)

- **Decisão:** sábado tratado como domingo — não é dia de entrega.
- **Por quê:** pedido da cliente; empresa não entrega no sábado.
- **Consequência:** cálculo, editor e pedidos abertos com data no fim de semana são empurrados para o próximo dia útil.

## SQLite + deploy.sh

- **Decisão:** banco em arquivo no servidor; publicação só via `./deploy.sh` no servidor.
- **Por quê:** operação simples para o porte do sistema; backup no próprio script de deploy.
