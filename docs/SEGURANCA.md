# Segurança

Este guia é regra para código novo e para alteração. Não é uma auditoria do que já está no ar. A lista conhecida como WOSP é o **OWASP Top 10** (edição 2021).

## Frontend não é cofre

HTML, JavaScript, PDF e mensagem de erro não levam senha, `APP_KEY`, token, conteúdo de `.env`, caminho do banco, caminho do servidor nem rastreio de exceção.

Telefone, endereço e documento de cliente são dado operacional. Eles aparecem só na tela ou no PDF de quem já passou na permissão daquela função. Não vão para página pública, para o repositório nem para texto de ajuda.

Esconder botão, desabilitar campo ou calcular na tela não protege a ação. Quem alterar o HTML ainda chega na rota.

## Nada que vem de fora é confiável

URL, query, formulário, JSON, arquivo, cookie e cabeçalho são entrada. O controller valida e autoriza de novo.

- Pegue só os campos previstos, com `$request->only()`, e passe no `Validator`.
- O autor da ação é `Auth::user()`. Não use um id de usuário enviado pelo formulário para decidir quem fez a ação ou quem é administrador.
- Quantidade, saldo, cancelamento, capacidade do caminhão e etapa da compra são conferidos no servidor, mesmo quando a tela já bloqueia o clique.
- Id de produto, pedido, carga, orçamento ou arquivo na URL não prova que o usuário pode ver aquele registro. A permissão da ação continua obrigatória.

## OWASP Top 10 neste sistema

### 1. Controle de acesso quebrado

Login (`auth`) só prova que há um usuário. Cada ação confere o slug com `Helper::get_permissions()` ou `is_admin`. A gate do menu não substitui essa checagem.

Cuidado com referência direta: o id na URL de produto, pedido, carga e orçamento precisa continuar atrás da permissão. Um usuário sem o slug não baixa anexo, não muda status e não exclui só porque sabe o número.

`is_admin` é `confirmed_user === 1`. Formulário de usuário comum não pode gravar esse campo. Só um administrador altera permissão e perfil de acesso.

### 2. Falhas criptográficas

Senha só entra pelo hash do Laravel. Não grave senha em claro, no log, na view ou em documentação.

Sessão e cookie ficam com o mecanismo do framework. Em produção, `APP_DEBUG=false`. Erro de produção não mostra SQL, caminho de arquivo nem variável de ambiente.

### 3. Injeção

Busca com `whereRaw` usa `?` e o valor separado. Texto digitado não é concatenado no SQL, no nome de coluna nem no nome de tabela.

O mesmo vale para ordenação e filtro vindo da URL: aceite só valores conhecidos (por exemplo a situação do pedido ou o tipo de retirada), não um pedaço de SQL montado pelo cliente.

### 4. Desenho inseguro

A tela pode orientar. Quem decide é o servidor: saldo, pedido cancelado, palete, divisão da carga e a passagem do pedido de compra de orçamento para aprovação e finalização.

Pedido cancelado (`complete_order = 2`) não volta a aberto por efeito colateral de abrir uma tela ou salvar uma linha. A reabertura é a ação explícita, registrada no log.

### 5. Configuração

O site público aponta para `public/`. Fora dessa pasta ficam o SQLite, o backup, o `.env` e o anexo de orçamento.

Não crie rota que leia `database/database.sqlite`, `.env` ou `storage` direto. Arquivo privado sai por controller, depois da permissão, como o anexo do orçamento.

Dependência e comando de produção não rodam a partir da cópia local. O deploy está em [DEPLOY.md](DEPLOY.md).

### 6. Componentes vulneráveis

Biblioteca nova entra pelo Composer ou pelo que o frontend já usa. Não acrescentar pacote abandonado para um detalhe que o Laravel já faz. Atualização de dependência é consciente, com o teste do fluxo que ela toca.

### 7. Falhas de autenticação

Não documente senha real, nem de exemplo copiado da produção. Não reenvie senha para a tela. Recuperação de senha continua no fluxo do Laravel.

Tentativa de login não revela se o e-mail existe. Mensagem genérica basta.

### 8. Integridade de software e de dado

O anexo de orçamento segue `PurchaseQuoteController`: disco `local`, PDF, JPG ou PNG, até 10 MB, conferência de extensão e de tipo, e entrega só depois de `canViewQuotes()`. O nome original do arquivo não vira caminho no disco.

Não aceite que o cliente escolha o diretório, o disco ou o nome final do arquivo. Não execute arquivo enviado.

### 9. Log e monitoramento

`Helper::saveLog` registra quem fez a ação relevante (exclusão, reabertura e as demais já previstas). O log não leva senha, token, conteúdo de `.env` nem o arquivo inteiro.

Falha de permissão responde sem detalhe interno. Não silencie erro de autorização como se a ação tivesse ocorrido.

### 10. SSRF

Não faça o servidor buscar uma URL montada pelo usuário. Se no futuro houver consulta externa, o destino é fixo no código, não um endereço colado no formulário.

## Além do Top 10

- **CSRF.** Todo POST, PUT e DELETE leva `@csrf`. O middleware `VerifyCsrfToken` não tem exceção. Ajax manda o token. Não abra exceção para "facilitar" uma chamada.
- **XSS.** Dado de usuário sai com `{{ }}` no Blade, que escapa o HTML. A forma que imprime HTML sem escapar não recebe nome, observação, endereço ou conteúdo de arquivo. PDF segue a mesma ideia: texto escapado.
- **Mass assignment.** Model tem `$fillable` curto. Não use `$request->all()` para criar ou atualizar. Não coloque `confirmed_user`, `is_admin` ou caminho de arquivo no preenchimento em massa.
- **Redirecionamento.** `redirect()->route(...)` ou caminho interno conhecido. Não redirecione para um endereço recebido na query.
- **Cabeçalhos no servidor.** Produção usa HTTPS. Cookie de sessão fica seguro e HTTP somente. Isso é configuração do servidor e do `.env`, não um comentário na view.
- **Backup.** A cópia do SQLite tem pedido, cliente, telefone e endereço. Ela permanece no servidor, fora do Git e fora de qualquer resposta da aplicação.
- **Dado em trânsito.** Não copie banco, `.env` ou backup para chat, issue ou documento.

## O que não entra neste guia

Senha, usuário de produção, host e caminho de pasta do servidor não são escritos aqui. Se uma alteração precisar disso, o valor fica no `.env` do servidor, não no repositório.
