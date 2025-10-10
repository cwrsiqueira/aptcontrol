# PSDControl — Controle de Entregas (Código-Fonte)

Projeto em **Laravel 7** para gestão de **pedidos, produtos, clientes, vendedores** e **controle de entregas** (parciais e totais), com relatórios de entrega prontos.

> Este README foi preparado para publicação do projeto em plataformas de e-commerce (Hotmart, Mercado Livre, etc.).  
> Ele descreve requisitos, instalação e execução local/produção, além das versões das principais dependências.

---

## 🧰 Tecnologias & Versões

**Backend**  
- PHP: `>=7.2` (recomendado PHP 7.4.x)  
- Laravel Framework: `^7.x`  
- Laravel UI (Auth scaffolding)  
- Doctrine DBAL (altera/renomeia colunas)  
- Guzzle HTTP  
- fruitcake/laravel-cors  
- fideloper/proxy  

**Frontend / Build**  
- Laravel Mix  
- Vue 2  
- Bootstrap 4  
- Axios  
- jQuery  
- Sass, sass-loader, resolve-url-loader  
- Popper.js  

**Banco de dados**  
- **SQLite** (arquivo `.sqlite` local — não precisa de servidor MySQL)

---

## 📦 O que vem pronto

- Autenticação com **login** e **logout** (Laravel UI).  
- Modelos principais: **Product**, **Client**, **Order**, **OrderProduct**, **Seller**.  
- Permissões básicas por **slug** para menus e ações (tabelas `permission_*`).  
- Relatórios:
  - `Relatório de Entregas` em **/report/delivery**.  
- Seeds de base:
  - **Usuário admin:** `admin@email.com` / **secret123** *(altere após o primeiro login!)*  
  - **Permissões** e **Categorias de Clientes**.

---

## ✅ Requisitos

- **PHP 7.4** ou superior  
- **Composer** 2.x  
- **Node.js** 14 ou 16 (compatível com Laravel Mix 5) e **npm**

> Dica: em produção Linux, garanta escrita em `storage/` e `bootstrap/cache/` pelo usuário do servidor web.

---

## 🚀 Instalação (Local)

### 1️⃣ Baixar o projeto
Baixe o arquivo `.zip` na plataforma de e-commerce (Hotmart, Mercado Livre etc.)  
e extraia o conteúdo em uma pasta, por exemplo:

```bash
C:\Projetos\psdcontrol\
```

### 2️⃣ Entrar na pasta
```bash
cd psdcontrol
```

### 3️⃣ Instalar dependências PHP
```bash
composer install
```

### 4️⃣ Copiar o arquivo `.env` e gerar a chave
```bash
cp .env.example .env
php artisan key:generate
```

### 5️⃣ Configurar o SQLite no `.env`
Abra o arquivo `.env` e edite as linhas de banco de dados:
```
DB_CONNECTION=sqlite
DB_DATABASE=/caminho/absoluto/para/database/database.sqlite
```

Crie o arquivo do banco:
```bash
mkdir -p database && touch database/database.sqlite
```

### 6️⃣ Executar migrations + seeds
```bash
php artisan migrate --seed
```

### 7️⃣ Criar link de storage público
```bash
php artisan storage:link
```

### 8️⃣ Instalar dependências do frontend e compilar
```bash
npm install
npm run dev   # ou: npm run prod
```

### 9️⃣ Iniciar o servidor local
```bash
php artisan serve
```
Acesse: **http://localhost:8000**

---

## 💡 Observações Importantes

- 💰 **O preço é referente somente ao código-fonte.**  
- 🔧 **Oferecemos serviço de instalação cobrado à parte**, com duas opções:  
  1. **Instalação no servidor do cliente** — preço único.  
  2. **Instalação no nosso servidor** — com **mensalidade** de hospedagem e suporte.  

Para solicitar instalação ou suporte técnico, entre em contato por e-mail:  
📩 **suporte@carlosdev.com.br**

---

## 🔐 Login de teste (criado pelo seeder)

- **E-mail:** `admin@email.com`  
- **Senha:** `secret123`

> Altere a senha após o primeiro acesso.

---

## 🗺️ Rotas principais

- Página inicial: `/`  
- Dashboard: `/home`  
- Produtos: `/products`  
- Clientes: `/clients`  
- Vendedores: `/sellers`  
- Pedidos: `/orders`  
- Itens do Pedido: `/order_products`  
- **Relatório de Entrega:** `/report/delivery`  
- Logout: `/logout` (GET)

---

## 📚 Scripts úteis

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
```

---

## ❓ Suporte

📧 **suporte@carlosdev.com.br**  
Atendimento para dúvidas de instalação e configuração básica.  
Reembolso não disponível (o código é entregue completo e testável em ambiente demo).

---

## 📝 Licença

Projeto fornecido **“como está” (as is)**.  
Revise os termos da plataforma onde foi adquirido e ajuste esta seção conforme sua estratégia comercial.
