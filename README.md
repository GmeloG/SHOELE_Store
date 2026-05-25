# 👟 Shoele Store

Sistema de gestão de stocks para ponto de venda especializado em sapatilhas.
Projeto académico — ISEP MEEC

---

## Tecnologias

| Camada        | Tecnologia              |
| ------------- | ----------------------- |
| Frontend      | HTML5, CSS3, JavaScript |
| Backend       | PHP 8.1                 |
| Base de dados | MySQL 8.0               |
| Ambiente      | Docker + Docker Compose |
| DB Admin      | phpMyAdmin              |

---

## Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e a correr

---

## Instalação e Execução

### 1. Iniciar os contentores

Na pasta raiz do projeto (`sneakerstock/`):

```bash
docker compose up -d --build
```

### 2. Aguardar a inicialização (~20 segundos)

```bash
docker compose logs -f db   # Ver quando a DB estiver pronta
```

### 3. Aceder à aplicação

| Serviço         | URL                   |
| --------------- | --------------------- |
| Loja / Catálogo | http://localhost:8080 |
| phpMyAdmin      | http://localhost:8081 |

**Credenciais phpMyAdmin:**

- Utilizador: `sneaker_user` / Password: `sneaker_pass`

---

## Utilizadores de Teste

Os utilizadores são criados automaticamente na primeira execução.

| Role          | Email                     | Password   |
| ------------- | ------------------------- | ---------- |
| Administrador | admin@shoele_store.test   | admin123   |
| Gestor        | gestor@shoele_store.test  | gestor123  |
| Cliente       | cliente@shoele_store.test | cliente123 |

---

## Comandos Úteis

```bash
# Iniciar (em background)
docker compose up -d --build

# Parar
docker compose down

# Reset completo (apaga dados)
docker compose down -v

# Ver logs em tempo real
docker compose logs -f

# Atualizar BD existente (adicionar tabela users + user_id)
docker compose exec db mysql -u sneaker_user -psneaker_pass sneakerstock < sql/migration_auth.sql

# Aceder ao MySQL
docker compose exec db mysql -u sneaker_user -psneaker_pass sneakerstock
```

---

## Estrutura do Projeto

```
sneakerstock/
│
├── config/
│   └── db.php                    # Ligação PDO (lê variáveis de ambiente Docker)
│
├── includes/
│   ├── header.php                # Cabeçalho + navbar dinâmica por role
│   ├── footer.php                # Rodapé
│   └── functions.php             # Auth, carrinho, produtos, encomendas, auto-seed
│
├── assets/
│   ├── css/style.css             # Design Shoele Store
│   ├── js/main.js                # Carrinho AJAX, POS, produto
│   └── images/                  # Placeholder, favicon
│
├── uploads/produtos/             # Imagens carregadas pelo admin
│
├── admin/
│   ├── includes/admin_nav.php    # Sidebar (mostra Utilizadores só para admin)
│   ├── dashboard.php             # Dashboard (admin + gestor)
│   ├── produtos.php              # Lista de produtos
│   ├── adicionar_produto.php     # Novo produto
│   ├── editar_produto.php        # Editar produto
│   ├── encomendas.php            # Gestão de encomendas
│   ├── relatorios.php            # Relatórios
│   └── utilizadores.php          # Gestão de utilizadores (só admin)
│
├── pos/
│   └── index.php                 # Ponto de venda (admin + gestor)
│
├── cliente/
│   └── encomendas.php            # Minhas encomendas (cliente)
│
├── api/
│   ├── cart.php                  # API carrinho (AJAX)
│   ├── variant_stock.php         # Stock por variante (AJAX)
│   └── pos_sale.php              # Registo venda POS (AJAX)
│
├── sql/
│   ├── database.sql              # Schema completo + dados exemplo
│   └── migration_auth.sql        # Migração para DBs existentes (users + user_id)
│
├── entrada.php                   # Página de entrada com seleção de role
├── login.php                     # Login (todos os roles)
├── registo.php                   # Registo de cliente
├── logout.php                    # Encerra sessão
├── index.php                     # Catálogo de produtos
├── produto.php                   # Detalhe do produto
├── carrinho.php                  # Carrinho de compras
├── checkout.php                  # Finalizar encomenda (requer login)
├── encomenda_sucesso.php          # Confirmação de encomenda
│
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## Sistema de Autenticação e Permissões

### Roles

| Role          | Catálogo | Carrinho | Checkout | Admin | POS | Utilizadores |
| ------------- | -------- | -------- | -------- | ----- | --- | ------------ |
| Visitante     | ✅       | ✅       | ❌       | ❌    | ❌  | ❌           |
| Cliente       | ✅       | ✅       | ✅       | ❌    | ❌  | ❌           |
| Gestor        | ✅       | —        | —        | ✅    | ✅  | ❌           |
| Administrador | ✅       | —        | —        | ✅    | ✅  | ✅           |

### Fluxo de autenticação

- Passwords guardadas com `password_hash()` (bcrypt, custo 10)
- Verificação com `password_verify()`
- Sessão armazenada em `$_SESSION['user']`
- `session_regenerate_id(true)` após login (previne session fixation)
- Logout destrói completamente a sessão
- Checkout sem login → redireciona para login com mensagem flash

### Navbar dinâmica

- **Sem login:** Catálogo, Login, Registar
- **Cliente:** Catálogo, Minhas Encomendas, Carrinho, Logout
- **Gestor:** Dashboard, Produtos, Encomendas, Relatórios, POS, Logout
- **Admin:** Dashboard, Produtos, Encomendas, Relatórios, POS, Utilizadores, Logout

---

## Funcionalidades

### Loja Online

- Catálogo com filtro por marca
- Página de produto com seleção de cor/tamanho e stock em tempo real
- Carrinho (disponível mesmo sem login)
- Checkout **requer login** — redireciona com mensagem se não autenticado
- Encomenda associada ao `user_id` do utilizador autenticado
- "Minhas Encomendas" para clientes autenticados

### Administração (`/admin/`)

- Dashboard com estatísticas e alertas de stock baixo
- CRUD de produtos com upload de imagem e variantes
- Encomendas: lista, detalhe, alteração de estado (cancelamento devolve stock)
- Relatórios por período: receita, mais vendidos, stock crítico
- **Gestão de utilizadores** (só Administrador): criar, editar, eliminar, definir role

### Ponto de Venda (`/pos/`)

- Interface rápida para vendas em loja
- Modal de seleção de variante com stock em tempo real
- Registo de venda + atualização automática de stock

### Base de Dados

| Tabela             | Descrição                                      |
| ------------------ | ---------------------------------------------- |
| `products`         | Catálogo de sapatilhas                         |
| `product_variants` | Stock por produto + cor + tamanho              |
| `users`            | Utilizadores registados (admin/gestor/cliente) |
| `customers`        | Dados de entrega das encomendas                |
| `orders`           | Encomendas online (com `user_id`)              |
| `order_items`      | Itens de cada encomenda                        |
| `sales`            | Vendas no POS                                  |
| `sale_items`       | Itens de cada venda POS                        |
| `stock_movements`  | Auditoria completa de movimentos de stock      |

---

## Segurança

- PDO + prepared statements (proteção SQL Injection)
- `htmlspecialchars` em todo o output (proteção XSS)
- `password_hash` / `password_verify` para passwords
- `session_regenerate_id` após login
- Validação de MIME type em uploads de imagem
- Transações MySQL para operações críticas de stock
- `requireRole()` em todas as páginas protegidas

---

## Notas

- Não são processados pagamentos reais (projeto académico)
- Os utilizadores de teste são criados automaticamente na primeira execução
- Para atualizar uma BD existente sem reiniciar: correr `migration_auth.sql`
