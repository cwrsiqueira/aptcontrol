# Texto para as Instructions do Lovable

O Lovable já lê o `AGENTS.md` da raiz do repositório. Este texto reforça as regras principais e manda ele consultar os docs. Cole em **Project settings → Instructions** (ou em Workspace settings → Instructions, para valer em todos os projetos do workspace), ajustando o que for do projeto. Se já houver texto lá, cole depois dele, sem apagar.

**Só cole depois que os docs do kit estiverem no GitHub** (commit e push feitos e a pasta `docs/` visível no código do Lovable). Antes disso, o Lovable não acha os arquivos citados.

```text
CONTEXTO DO PROJETO
- Antes de qualquer mudança, leia o AGENTS.md da raiz. Ele descreve o projeto, as regras invioláveis e qual documento de docs/ ler para cada tipo de tarefa.
- As regras comuns estão em docs/padroes/ (segurança, UI/UX, testes). O estado atual está em docs/STATUS_PROJETO.md e as regras de negócio em docs/DOMINIO.md.
- Quando uma regra de negócio não estiver em docs/DOMINIO.md, pergunte em vez de inventar.

REGRAS DE SEGURANÇA (não negociáveis)
- Nunca confie no frontend. Toda regra, validação, preço, permissão e cálculo fica no backend (Edge Function ou RLS). O frontend só coleta dados e mostra o erro que o servidor devolveu.
- Toda tabela com RLS ligada e policies explícitas por operação (SELECT, INSERT, UPDATE, DELETE). Nunca USING (true) em tabela com dado de usuário. Multiempresa: empresa_id em toda tabela e policy por empresa.
- A chave publicável é pública. A chave secreta / service_role só em Edge Function, nunca em VITE_* nem em código do cliente.
- Colunas de papel, plano, status de pagamento e preço não são alteráveis pelo cliente.
- Edge Function: valida o JWT do usuário, valida o corpo com Zod, CORS com origem explícita, erro genérico.
- Webhook: valida assinatura antes de tudo e deduplica pelo ID do evento.
- Upload: bucket privado, policy por usuário, tipos permitidos, tamanho máximo.
- Nada de dangerouslySetInnerHTML. Links só https.
- Rode o Security Advisor antes de publicar.

SIMPLICIDADE E UI
- Uma tarefa por tela, poucos campos, linguagem do usuário em português do Brasil.
- shadcn/ui + Tailwind, tema claro, uma cor de destaque, texto ≥ 16 px, contraste AA, alvo de toque ≥ 44 px, celular primeiro.
- Toda tela com estados de carregando, vazio e erro. Erro de formulário ao lado do campo.
- Nada de número, selo ou depoimento inventado.
```
