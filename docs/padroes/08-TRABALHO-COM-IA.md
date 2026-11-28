# Como a IA trabalha nos projetos

Vale para Claude Code, Cursor, Codex e qualquer agente com acesso ao repositório.

## Antes de começar

1. Ler o `AGENTS.md` do projeto e o doc indicado para o tipo de tarefa.
2. Conferir o estado real: `git status -sb` e `git log -1 --oneline`. O git manda; se o `STATUS_PROJETO.md` divergir, confie no git e avise.
3. Verificar versões instaladas (`composer.json`, `package.json`, `pubspec.yaml`) antes de assumir API de framework.
4. Se a tarefa for ambígua, perguntar **antes** de escrever código. Uma pergunta objetiva por vez.

## Conversa

- Quando a pessoa sugerir uma solução, compare com pelo menos uma alternativa e discorde se houver opção melhor. Concordar por padrão é falha, não gentileza.
- Resposta que pode ser lida de dois jeitos (sim ou não): confirme antes de agir.
- Conversa longa ou com muitos assuntos: sugira **Encerre a sessão** e um chat novo com **Me atualize**. Os docs guardam onde vocês pararam.
- Pedido grande (várias telas, mudança no banco, mais de uma sessão): divida em etapas pequenas, mostre a lista e peça "sim" antes de cada uma. Cada etapa termina funcionando e testável.

## Durante

- **Mudança pequena e focada.** Só o que o pedido exige. Refatoração fora do escopo vira sugestão no relatório.
- **Seguir o padrão do projeto**, mesmo que você faria diferente. Divergência vira proposta, não fato consumado.
- **Dependência nova:** justificar, confirmar no registro que o pacote existe e é o oficial (nome inventado por IA é vetor de ataque), e pedir aprovação.
- **Teste junto com o código**, incluindo abuso quando a mudança toca auth, dado pessoal, dinheiro, arquivo ou entrada pública.
- **Doc junto com o código:** comportamento mudou, doc muda no mesmo commit.
- Não ler `.env` para exibir. Conferir só nomes de variáveis.
- Não mexer em arquivo gerado (`routeTree.gen.ts`, `types.ts` do Supabase, migrations do Lovable, bloco gerado do Next no `AGENTS.md`).
- Não alterar nem apagar teste para fazer a suíte passar.

## Segredos e comandos

- **Segredo nunca aparece em comando** que a IA escreve ou sugere (`CHAVE=valor`, `export`, `setenv`, argumento de script, `curl -H "Authorization: Bearer ..."` com valor real). O shell grava o histórico.
- Na VPS, o usuário grava valores com um script que pergunta sem eco (o kit traz `scripts/definir-segredo.sh`). Para conferir, `cut -d= -f1 .env`.
- Exemplo de código e doc usam placeholder: `SUA_CHAVE_AQUI`, `${STRIPE_SECRET_KEY}`.
- Comando de servidor entregue ao usuário começa com `cd` para a pasta do sistema; um bloco por pasta.

## Git

- **Sem commit nem push sem autorização explícita.** A frase "Pode subir" é a autorização.
- Sem `--force`, sem `--no-verify`, sem reescrever histórico publicado, sem squash em repositório sincronizado com Lovable.
- Commit só no repositório que mudou, com mensagem que diz o porquê.
- Antes de commitar: conferir que nenhum `.env`, dump, backup, `node_modules`, `vendor`, dado real ou arquivo de runtime entrou.
- Repositório compartilhado com Lovable: branch própria + PR + merge commit; nunca commit direto na branch que o Lovable escreve.

## Ações que exigem confirmação do dono

Produção (VPS, deploy manual, DNS), banco de produção, migration destrutiva, modo live de pagamento, envio de mensagem/e-mail a cliente real, exclusão de dados ou arquivos, mudança de permissão de repositório ou de chave. A IA prepara e mostra o que vai fazer; executa só depois do "ok".

## Rituais (frases padrão)

| Frase | Skill | O que faz |
| --- | --- | --- |
| "Me atualize" / "Se atualize no contexto" | `se-atualize-contexto` | lê git + `STATUS_PROJETO.md` e resume onde estamos e o próximo passo |
| "Pode subir" | `pode-subir` | atualiza docs, roda lint/tipos/testes, commita e faz push se tudo passar; mostra o deploy |
| "Encerre a sessão" | `encerrar-sessao` | atualiza STATUS e docs tocados, confere segredos e entrega relatório |
| "Audite o contexto" | `auditar-contexto` | confere arquivos obrigatórios, links, citações, atualidade e segredos nos docs |
| "Revise a segurança" | `revisar-seguranca` | passa o diff (ou um módulo) pelo baseline e lista achados por gravidade |

## Ao terminar

Relatório de até 5 linhas (o texto que a IA escreve custa mais que o que ela lê), nesta ordem:

1. O que foi feito, citando arquivos.
2. Testes, lint e build: o que rodou e o resultado com números.
3. Estado do git: com ou sem commit/push; se falta "Pode subir".
4. Pendências e decisões que dependem do dono, rotuladas (fato, hipótese, recomendação, decisão pendente).
5. Se mudou algo visível: como testar na tela, em 2 a 4 passos.

Termine sempre com o próximo passo e a frase que a pessoa deve usar. Se há mudanças não publicadas: "Para salvar e publicar, diga **Pode subir**".

Quando o dono trabalha com um chat de estratégia separado, fechar com um bloco de código curto que ele possa colar lá.

## Várias IAs no mesmo projeto

- Cada uma tem fronteira escrita no `AGENTS.md` (ex.: Lovable cuida da UI e da infraestrutura; Claude Code cuida de `src/lib/`, testes e regras que custam dinheiro).
- Pedidos entre elas passam por arquivo (`docs/handoff/para-externo.md`, `docs/handoff/para-lovable.md`), um item por pedido (H-001, H-002…), status aberto/feito/bloqueado.
- Mais de duas rodadas no mesmo assunto ou conflito com o domínio: bloqueia e a decisão volta ao dono.

## Como escrever docs de contexto

- `AGENTS.md` e `CLAUDE.md` com menos de ~200 linhas cada. Detalhe vai para `docs/` e é lido sob demanda.
- Regra concreta e verificável: "rode `npm test` antes do commit", não "teste bem".
- Uma regra em um lugar. Regra comum fica em `docs/padroes/`; regra do projeto, no `AGENTS.md` dele. Duas versões da mesma regra = a IA escolhe uma ao acaso.
- Tabela "quando ler o quê" em vez de mandar ler tudo.
- Nada de valor de segredo, credencial de seed, IP privado de produção ou dado pessoal nos docs.
- Para algo que **não pode** acontecer de jeito nenhum, doc não basta: use hook (Claude Code `PreToolUse`), pre-commit, CI ou permissão no provedor.
- Doc de estado (`STATUS_PROJETO.md`) é curto e datado; histórico longo vai para `HISTORICO_SESSOES.md` ou `DECISOES.md`, que a IA só lê quando precisa.
