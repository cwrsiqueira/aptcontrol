# APIs e conexões

Simples, previsíveis e fechadas por padrão.

## Forma

- REST com JSON, recursos no plural, verbos HTTP com o sentido certo: `GET` não altera nada.
- Uma convenção por projeto. Exemplo: `GET /api/v1/contratos`, `GET /api/v1/contratos/{id}`, `POST /api/v1/contratos`, `PATCH /api/v1/contratos/{id}`.
- Versão no caminho (`/v1`) quando houver cliente fora do seu controle (app de loja, integração). Versão antiga tem data para morrer.
- Datas em ISO 8601 com fuso; dinheiro em centavos inteiros com o nome dizendo (`valor_cents`); enums como string legível.
- Paginação sempre, com teto: `?page=1&per_page=20` (máx. 100). Resposta traz o total ou o cursor.
- Filtro e ordenação só por campos de allowlist.

## Respostas

Formato único de erro em todo o projeto:

```json
{ "erro": { "codigo": "VALIDACAO", "mensagem": "Confira os campos destacados.", "campos": { "cpf": "CPF inválido." } } }
```

| Status | Quando |
| --- | --- |
| 200 / 201 / 204 | sucesso (201 ao criar, 204 sem corpo) |
| 400 | corpo malformado |
| 401 | sem sessão ou token inválido |
| 403 | autenticado, mas sem permissão |
| 404 | não existe **ou** não é seu (prefira 404 a 403 quando revelar a existência já é vazamento) |
| 409 | conflito de estado (já pago, já estornado, versão desatualizada) |
| 422 | validação de campo |
| 429 | rate limit, com `Retry-After` |
| 500 | erro interno, mensagem genérica + ID de correlação |

- Mensagem para pessoas, sem stack, SQL, caminho ou nome de classe.
- Resposta com lista explícita de campos (Resource/DTO). Nunca serializar o model inteiro.

## Segurança da API

- Autenticação em toda rota, exceto as públicas listadas no `SEGURANCA.md`.
- Front próprio no mesmo domínio: sessão por cookie (`HttpOnly`) + CSRF. Front em outro domínio ou app: token de acesso curto + refresh rotativo guardado em storage seguro.
- Autorização por recurso em cada handler (ver [02-SEGURANCA.md](02-SEGURANCA.md), §2).
- Validação do corpo, da query e dos parâmetros de rota com schema.
- Rate limit por rota sensível; limite de tamanho de corpo.
- CORS com lista explícita de origens.
- Operação que cobra, envia ou cria algo externo aceita `Idempotency-Key` e devolve a mesma resposta ao repetir.
- Atualização concorrente: `updated_at` ou `version` no `PATCH`; diferente do banco ⇒ 409.

## Sistema chamando sistema

- Token de serviço só no ambiente, com escopo mínimo e prazo; um por integração.
- Requisição idempotente, timeout curto, retry com espera, auditoria nos dois lados.
- Cada sistema tem seu banco. Integração é por API ou fila, nunca por banco compartilhado.
- Contrato escrito (OpenAPI ou um `docs/INTEGRACAO.md` com exemplos de request/response).
- A fonte da verdade de cada dado é uma só e está escrita.

## Webhooks recebidos

1. Validar assinatura/origem **antes** de ler o corpo como confiável. Inválida ⇒ 400/401.
2. Gravar o ID do evento com `unique`; já processado ⇒ 200 sem refazer.
3. Responder 2xx rápido; processamento pesado em fila.
4. Reconsultar o estado no provedor quando o evento não trouxer tudo.
5. Evento de tipo desconhecido ⇒ 200 sem efeito, com log.

## Documentação para a IA

Cada API tem, no repositório:

- a lista de rotas com método, auth exigida e permissão (pode ser gerada: `php artisan route:list`, OpenAPI);
- exemplos de payload válido e de erro;
- o que é fonte da verdade e o que é cópia.

Quando uma regra muda, API e todos os clientes mudam no mesmo pedido. Regra, validação e permissão ficam na API; o cliente coleta dados e mostra o erro que o servidor devolveu.
