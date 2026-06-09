# Shoele Store

Sistema de gestão de stocks e loja online para ponto de venda especializado em sapatilhas.
Projeto académico — ISEP MEEC (Mestrado em Engenharia Eletrotécnica e de Computadores)

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![License](https://img.shields.io/badge/licença-Académica-lightgrey)

---

## Visão Geral

A **Shoele Store** é uma aplicação web full-stack que combina loja online, painel de administração e ponto de venda (POS) físico. Desenvolvida como projeto académico, demonstra boas práticas de desenvolvimento web com PHP, MySQL e Docker.

### Funcionalidades principais

- Loja online com catálogo, carrinho e checkout
- Painel de administração com dashboard, gestão de produtos e relatórios
- Ponto de Venda (POS) para vendas em loja física
- Controlo de acesso por roles (Visitante, Cliente, Gestor, Administrador)
- Rastreio de stock com auditoria completa de movimentos
- Suporte a variantes de produto (cor + tamanho)

---

## Tecnologias

| Camada        | Tecnologia              |
| ------------- | ----------------------- |
| Frontend      | HTML5, CSS3, JavaScript |
| Backend       | PHP 8.3                 |
| Base de dados | MySQL 8.0               |
| Ambiente      | Docker + Docker Compose |
| DB Admin      | phpMyAdmin              |

---

## Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e a correr

---

## Instalação e Execução

### 1. Clonar o repositório

```bash
git clone <url-do-repositório>
cd SHOELE_Store
```

### 2. Iniciar os contentores

```bash
docker compose up -d --build
```

### 3. Aguardar a inicialização (~20 segundos)

```bash
docker compose logs -f db   # Aguardar "ready for connections"
```

### 4. Aceder à aplicação

| Serviço         | URL                   |
| --------------- | --------------------- |
| Loja / Catálogo | http://localhost:8080 |
| phpMyAdmin      | http://localhost:8081 |

**Credenciais phpMyAdmin:**

- Utilizador: `sneaker_user` / Password: `sneaker_pass`

---

## Utilizadores de Teste

Os utilizadores são criados automaticamente na primeira execução.

| Role          | Email                    | Password   |
| ------------- | ------------------------ | ---------- |
| Administrador | admin@shoelestore.test   | admin123   |
| Gestor        | gestor@shoelestore.test  | gestor123  |
| Cliente       | cliente@shoelestore.test | cliente123 |

---

## Comandos Úteis

```bash
# Iniciar (em background)
docker compose up -d --build

# Parar
docker compose down

# Reset completo (apaga todos os dados)
docker compose down -v

# Ver logs em tempo real
docker compose logs -f

# Aceder ao MySQL diretamente
docker compose exec db mysql -u sneaker_user -psneaker_pass sneakerstock
```

---

## Estrutura do Projeto

```
SHOELE_Store/
│
├── config/
│   └── db.php                    # Ligação PDO (singleton, lê variáveis de ambiente)
│
├── includes/
│   ├── header.php                # Cabeçalho + navbar dinâmica por role
│   ├── footer.php                # Rodapé
│   └── functions.php             # Auth, carrinho, produtos, encomendas, auto-seed
│
├── assets/
│   ├── css/style.css             # Estilos da Shoele Store
│   ├── js/main.js                # Carrinho AJAX, POS, interações de produto
│   └── images/                   # Placeholder, favicon
│
├── uploads/produtos/             # Imagens carregadas pelo admin
│
├── admin/
│   ├── includes/admin_nav.php    # Sidebar (Utilizadores apenas para admin)
│   ├── dashboard.php             # Dashboard com estatísticas e alertas
│   ├── produtos.php              # Lista de produtos
│   ├── adicionar_produto.php     # Adicionar produto
│   ├── editar_produto.php        # Editar produto
│   ├── encomendas.php            # Gestão de encomendas
│   ├── relatorios.php            # Relatórios por período
│   └── utilizadores.php          # Gestão de utilizadores (só admin)
│
├── pos/
│   └── index.php                 # Ponto de venda (admin + gestor)
│
├── cliente/
│   └── encomendas.php            # Minhas encomendas (cliente autenticado)
│
├── api/
│   ├── cart.php                  # API carrinho (AJAX)
│   ├── search.php                # Pesquisa de produtos (AJAX)
│   ├── variant_stock.php         # Stock por variante (AJAX)
│   ├── confirmar_encomenda.php   # Confirmação de encomenda (AJAX)
│   └── pos_sale.php              # Registo venda POS (AJAX)
│
├── sql/
│   └── database.sql              # Schema completo + dados de exemplo
│
├── entrada.php                   # Página de entrada com seleção de role
├── login.php                     # Login (todos os roles)
├── registo.php                   # Registo de cliente
├── logout.php                    # Encerra sessão
├── index.php                     # Homepage (carrossel + mais vendidos + marcas)
├── catalogo.php                  # Catálogo com filtro por marca
├── produto.php                   # Detalhe do produto (cor/tamanho + stock)
├── carrinho.php                  # Carrinho de compras
├── checkout.php                  # Finalizar encomenda (requer login)
├── encomenda_sucesso.php          # Confirmação de encomenda
│
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## Sistema de Permissões

| Role          | Catálogo | Carrinho | Checkout | Admin | POS | Utilizadores |
| ------------- | -------- | -------- | -------- | ----- | --- | ------------ |
| Visitante     | ✅       | ✅       | ❌       | ❌    | ❌  | ❌           |
| Cliente       | ✅       | ✅       | ✅       | ❌    | ❌  | ❌           |
| Gestor        | ✅       | —        | —        | ✅    | ✅  | ❌           |
| Administrador | ✅       | —        | —        | ✅    | ✅  | ✅           |

### Navbar dinâmica por role

| Estado        | Itens visíveis                                                |
| ------------- | ------------------------------------------------------------- |
| Sem login     | Catálogo · Login · Registar                                   |
| Cliente       | Catálogo · Minhas Encomendas · Carrinho · Logout              |
| Gestor        | Dashboard · Produtos · Encomendas · Relatórios · POS · Logout |
| Administrador | + Utilizadores                                                |

---

## Funcionalidades

### Loja Online

- Homepage com carrossel de destaques, mais vendidos e marcas
- Catálogo com filtro por marca e pesquisa em tempo real
- Página de produto com seleção de cor/tamanho e verificação de stock via AJAX
- Carrinho (disponível mesmo sem login, baseado em sessão)
- Checkout requer autenticação — redireciona com mensagem flash se não autenticado
- "Minhas Encomendas" para clientes autenticados

### Painel de Administração (`/admin/`)

- Dashboard com estatísticas, alertas de stock crítico e encomendas pendentes
- CRUD de produtos com upload de múltiplas imagens (JPEG, PNG, WebP) e gestão de variantes
- Encomendas: lista, detalhe, alteração de estado (cancelamento devolve stock automaticamente)
- Relatórios por período: receita, produtos mais vendidos, stock crítico
- Gestão de utilizadores (só Administrador): criar, editar, eliminar, definir role

### Ponto de Venda (`/pos/`)

- Interface rápida para vendas em loja física
- Modal de seleção de variante com stock em tempo real
- Registo de venda e atualização automática de stock

### Base de Dados

| Tabela             | Descrição                                          |
| ------------------ | -------------------------------------------------- |
| `products`         | Catálogo de sapatilhas                             |
| `product_variants` | Stock por combinação produto + cor + tamanho       |
| `product_images`   | Múltiplas imagens por produto mapeadas por cor     |
| `users`            | Utilizadores registados (admin / gestor / cliente) |
| `customers`        | Dados de entrega das encomendas                    |
| `orders`           | Encomendas online                                  |
| `order_items`      | Itens de cada encomenda                            |
| `sales`            | Vendas realizadas no POS                           |
| `sale_items`       | Itens de cada venda POS                            |
| `stock_movements`  | Auditoria completa de todos os movimentos de stock |

---

## Segurança

- PDO com prepared statements (proteção contra SQL Injection)
- `htmlspecialchars` em todo o output (proteção contra XSS)
- `password_hash` / `password_verify` com bcrypt (custo 10)
- `session_regenerate_id(true)` após login (previne session fixation)
- Validação de MIME type em uploads de imagem
- Transações MySQL para operações críticas de stock
- `requireRole()` em todas as páginas protegidas

---

## Notas

- Não são processados pagamentos reais (projeto académico)
- Os utilizadores de teste são criados automaticamente na primeira execução via auto-seed
- A base de dados é inicializada automaticamente a partir de `sql/database.sql`
