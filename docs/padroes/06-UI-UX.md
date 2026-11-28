# UI/UX

Telas limpas, modernas e profissionais. O usuário resolve o que veio fazer sem pensar na ferramenta.

## Princípios

1. **Uma tarefa por tela.** Uma ação principal em destaque; no máximo duas ações visíveis por card. O resto vai para menu "⋮" ou tela de detalhe.
2. **Palavras do usuário.** "Interessados", não "leads"; "Parcelas em atraso", não "inadimplência D+30". Sem jargão técnico, sem sigla interna.
3. **Só o que é verdade.** Nada de métrica, selo, depoimento ou recurso inventado. Estado vazio é honesto: "Nenhum interessado ainda".
4. **Menos campos.** Pergunte só o necessário agora; o resto pode vir depois. Preencha o que já se sabe (CEP → endereço). Não peça a mesma informação duas vezes no mesmo fluxo (WCAG 3.3.7).
5. **O sistema explica o que aconteceu.** Toda ação tem retorno visível: salvo, enviado, erro e como corrigir.
6. **Consistência.** Mesmo componente, mesmo lugar, mesmo texto para a mesma coisa em todas as telas.

## Visual base

O ponto de partida é **Tailwind + shadcn/ui** (ou o design system do projeto, quando existir). O projeto define seus tokens em `docs/DESIGN.md`; sem ele, use:

| Elemento | Padrão |
| --- | --- |
| Fundo | neutro claro (`#f8fafb`); superfície branca |
| Texto | quase preto (`#111827`); secundário `#4b5563` |
| Cor da marca | uma só, usada em botão principal, link e item ativo |
| Estados | erro `#b91c1c`, alerta `#b45309`, sucesso na cor da marca ou verde escuro |
| Borda | 1 px cinza claro; cantos 8–16 px |
| Sombra | leve, só para separar camadas |
| Espaçamento | escala de 4 px (4, 8, 12, 16, 24, 32, 48) |
| Fonte | 1 família de texto (Inter ou system-ui) + no máximo 1 para títulos |
| Ícones | um conjunto só (lucide), sempre com rótulo quando a ação não é óbvia |

Evite: gradiente em tudo, sombra pesada, mais de uma cor de destaque, texto em caixa alta pequeno espaçado, animação que atrasa a tarefa, card dentro de card dentro de card.

## Tipografia e leitura

- Corpo ≥ 16 px (18 px para público 50+); rótulo ≥ 14 px; nada abaixo de 14 px.
- Linha de texto com 60–80 caracteres no máximo.
- Hierarquia por tamanho e peso, não por cor.
- Números financeiros com fonte tabular (`tabular-nums`), alinhados à direita em tabela, formato brasileiro (`R$ 1.234,56`, `04/11/2026`).

## Acessibilidade (WCAG 2.2 nível AA como mínimo)

- Contraste ≥ 4,5:1 para texto e ≥ 3:1 para ícone e borda de campo.
- Alvo de toque ≥ 24×24 px (WCAG 2.5.8); no celular use 44–48 px.
- Tudo funciona pelo teclado, com foco visível e não escondido por barra fixa (2.4.11).
- Arrastar sempre tem alternativa por clique (2.5.7).
- Ajuda no mesmo lugar em todas as telas (3.2.6).
- Login sem teste cognitivo: permitir colar senha e gerenciador de senhas; código por e-mail ou passkey como alternativa (3.3.8).
- `label` em todo campo; `alt` em imagem com conteúdo; `aria-live`/`role="alert"` para mensagem de erro.
- Cor nunca é a única forma de informar (ícone + texto).
- Respeitar `prefers-reduced-motion`.

## Formulários e erros

- Rótulo em cima do campo, sempre visível (placeholder não é rótulo).
- Máscara e teclado certos: `inputmode="numeric"` para CPF, CEP, telefone; `type="email"`; `autocomplete` correto.
- Validação no cliente só para conforto; o servidor valida de novo e a tela mostra o erro que ele devolveu.
- **O erro vai até a pessoa:** campo com borda vermelha + fundo claro, frase logo abaixo (`aria-describedby`, `aria-invalid`), rolagem e foco no primeiro campo com erro. Corrigiu, o vermelho some.
- Mensagem diz o que fazer: "Digite os 11 números do CPF", não "Campo inválido".
- Pergunta obrigatória (como o aceite dos termos) vem **antes** do botão.
- Botão desligado só com explicação visível do porquê.
- Ação destrutiva pede confirmação com o nome do que será apagado; a mais grave pede digitar a palavra (`EXCLUIR`). Prefira "desfazer" a "tem certeza?" quando der.
- Duplo clique não duplica: botão entra em "Salvando…" e o servidor é idempotente.

## Estados de tela

Toda tela tem os quatro: **carregando** (esqueleto, não spinner infinito), **vazio** (o que é e o primeiro passo), **erro** (o que houve e como tentar de novo) e **com dados**. Tela sem estado de erro está incompleta.

## Celular primeiro

- Layout pensado para 360 px de largura e depois expandido.
- Ação principal na zona do polegar; barra inferior com no máximo 4–5 itens.
- Tabela larga vira lista de cards no celular.
- Margem lateral de 16 px; nada de rolagem horizontal da página.

## Telas de sistema (painel, ERP, CRM)

- Lista com busca, filtro e ordenação só quando houver volume que justifique.
- Colunas que importam primeiro; o resto no detalhe.
- Ações em massa só quando o usuário realmente faz em massa.
- Data da última atualização visível quando o dado vem de importação (ex.: "Dados importados em 03/11, 14:20").
- Dashboard: 3–5 números que mudam decisão, cada um com o período e o que significa. Gráfico simples (linha, barra) com legenda direta.

## Desempenho percebido (Core Web Vitals)

Meta no percentil 75 dos acessos reais: **LCP < 2,5 s**, **INP < 200 ms**, **CLS < 0,1**.

- Imagens em WebP/AVIF, com `width`/`height` definidos e `loading="lazy"` abaixo da dobra.
- Fonte com `font-display: swap`, no máximo 2 famílias.
- JavaScript só o necessário na primeira tela; componentes pesados carregados sob demanda.
- Lista grande paginada no servidor.
- Sem layout que pula quando algo carrega (reserve o espaço).

## Segurança na interface

- Dado sensível mascarado por padrão (CPF `***.456.789-**`, telefone parcial) e revelado por ação explícita quando a pessoa tem permissão.
- Sessão expirada: mensagem clara e volta para a mesma tela depois do login, sem perder o que foi digitado quando possível.
- Nada de dado pessoal em URL, título da aba ou notificação na tela bloqueada.
- Link externo gerado por usuário mostra o domínio antes de abrir.

## Texto (microcopy)

- Português do Brasil, frases curtas, voz ativa, "você".
- Botão diz a ação: "Salvar contrato", "Enviar pelo WhatsApp". Nada de "OK", "Submeter".
- Número com unidade e período: "38 visitas em 7 dias".
- Data relativa só até 7 dias ("ontem", "há 3 dias"); depois, data completa.

## Antes de dar a tela por pronta

- [ ] Funciona em 360 px e em 1440 px.
- [ ] Funciona só com teclado.
- [ ] Contraste conferido.
- [ ] Estados carregando, vazio e erro feitos.
- [ ] Erro do servidor aparece no lugar certo.
- [ ] Texto revisado: sem jargão, sem número inventado.
- [ ] Lighthouse/PageSpeed sem regressão nas páginas públicas.
