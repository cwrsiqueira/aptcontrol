# Princípios

Valem para todo projeto. Quando um doc específico contradiz este, o específico só vence se disser explicitamente por quê.

## 1. O servidor é a fronteira de confiança

Navegador, app, URL, header, cookie, arquivo, webhook, planilha importada, resposta de API de terceiro e saída de IA são **entrada não confiável**. Tudo é validado, tipado e autorizado no backend antes de qualquer uso.

A tela pode esconder um botão. A rota continua recusando. Detalhe em [02-SEGURANCA.md](02-SEGURANCA.md).

## 2. Menos é mais

Simples é o que resolve o problema com o menor número de peças que alguém precisa entender. Simples não é inseguro: segurança nunca é cortada para simplificar.

- **Comece pelo que já existe.** Recurso do framework antes de biblioteca; biblioteca consolidada antes de código próprio; código próprio antes de serviço novo.
- **Uma forma de fazer cada coisa.** Um jeito de validar, um de autorizar, um de chamar a API, um de tratar erro. Duas convenções no mesmo projeto é dívida.
- **Sem abstração antecipada.** Interface, fábrica, camada genérica e "para o futuro" só entram quando houver o segundo caso real. Exceção: fronteiras externas (pagamento, canal de mensagem, IA) ficam atrás de uma interface desde o início, porque trocar de provedor acontece.
- **Sem dependência nova sem motivo escrito.** Cada pacote é código de terceiro rodando com os seus privilégios.
- **Uma tarefa por tela.** Poucos campos, uma ação principal, linguagem do usuário.
- **Regra de negócio em função pura.** Cálculo, validação de domínio e decisão ficam em funções sem banco e sem rede, com teste. Controller, action e rota só orquestram.
- **Apagar é melhor que comentar.** Código morto, flag velha e rota sem uso saem.
- **Não construa o que o serviço já faz.** Use a ferramenta pronta até bater num limite concreto e documentado.

Teste do "menos é mais": se outra pessoa (ou outra IA) precisar de mais de 5 minutos para entender onde mexer, está complicado demais.

## 3. Fatos vêm de dados, contas vêm de código

- Preço, saldo, estoque, desconto, frete, prazo e status vêm do banco ou de fonte oficial. Nunca do cliente, nunca da IA.
- Matemática e decisão determinística ficam em código testado. A IA entende linguagem, extrai dados e redige; não calcula nem decide sozinha.
- Valor que não veio da fonte não é estimado. Mostre que falta, não invente.
- Pagamento só é reconhecido por confirmação verificada (webhook assinado ou consulta ao provedor). Promessa, redirecionamento e "tela de sucesso" não confirmam nada.

## 4. Dinheiro e datas

- Dinheiro em **centavos inteiros** (`_cents`, `bigint`) ou `DECIMAL(15,2)`. Nunca `float`/`double`.
- Arredondamento em um lugar só, com regra escrita.
- Fuso do negócio explícito (`America/Sao_Paulo`, `America/Belem`). Vencimento é `date`; evento é `timestamptz`/UTC.
- Valor efetivamente cobrado é gravado no registro do pagamento, para auditoria.

## 5. Falhar seguro

- Sem empresa ativa, a consulta retorna vazio. Sem permissão, 403. Sem segredo configurado, a rota recusa.
- Tela vazia é melhor que vazamento. Erro claro é melhor que dado errado.
- Exceção não tratada não vira 200, nem mensagem com stack para o usuário.

## 6. Pare e pergunte

A IA para e descreve, em vez de executar, quando:

- a regra de negócio não está escrita em `docs/DOMINIO.md` ou é ambígua;
- a tarefa exige mudar schema em projeto com dono externo de infraestrutura (Lovable), criar rota em arquivo gerado, ou mexer em produção;
- a única solução possível fica no frontend (segurança no cliente não existe);
- a ação é irreversível: apagar dado, migration destrutiva, force push, cobrança real, envio de mensagem a cliente.

Regra de negócio ambígua não se resolve por escolha do modelo. O custo de errar aparece no extrato do cliente, não no build.

## 7. Diga o que é o quê

Ao propor algo, rotule: **fato confirmado** (está no código ou no doc), **hipótese a validar**, **recomendação técnica** ou **decisão pendente**. Nunca use métrica, depoimento ou resultado inventado em código, tela, doc ou material comercial.

## 8. Docs curtos e verdadeiros

- Doc desatualizado é pior que doc nenhum. Ao mudar comportamento, mude o doc no mesmo commit.
- O git manda: antes de afirmar o estado do projeto, confira `git status` e `git log`.
- Escreva para quem chega sem contexto: o que é, onde está, como verificar.
- Português do Brasil em tudo que é para pessoas (docs, telas, mensagens, commits). Nada de *ficheiro*, *ecrã*, *telemóvel*.
