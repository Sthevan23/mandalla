# Mandalla — E-commerce de moda feminina (PHP)

Loja virtual premium em **PHP puro (MVC)** com SQLite, catálogo, carrinho, checkout, WhatsApp, área da cliente e painel administrativo.

Sem Node.js / Next.js — pronto para Hostinger.

## Requisitos

- PHP 8.2+ com extensões: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`
- Apache com `mod_rewrite` (Hostinger já tem)

## Local

```bash
php -S localhost:8080 -t public public/router.php
```

Na primeira execução o banco `database/mandalla.db` é criado com produtos de demonstração.

## Acessos de demonstração

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Admin | admin@mandalla.com | admin123 |
| Cliente | cliente@exemplo.com | cliente123 |

**Altere essas senhas antes de publicar.**

## Cupons de exemplo

- `MANDALLA10` — 10% (mín. R$ 150)
- `BEMVINDA` — R$ 30 (mín. R$ 200)

## Estrutura

```text
app/          Controllers, Models, Views, Core
config/       App e banco
database/     schema.sql, seed.php, mandalla.db
public/       Front controller, assets, uploads  ← document root
storage/      Logs
```

## Hostinger

Guia completo: **[HOSTINGER.md](HOSTINGER.md)**

Resumo:

1. Envie o projeto inteiro para `public_html`
2. Aponte o document root para `public_html/public`
3. Crie `.env` a partir de `.env.example` (`APP_URL`, `WHATSAPP`, `APP_DEBUG=false`)
4. Permissão de escrita em `database/` e `public/uploads/`
5. PHP 8.2+ com `pdo_sqlite`

## Funcionalidades

- Loja: home, catálogo com filtros, produto, favoritos, carrinho, cupom, checkout
- Pedido no banco + mensagem pronta para WhatsApp
- Pagamentos configuráveis (PIX / link / na entrega)
- Admin: dashboard, produtos, pedidos, estoque, financeiro (CSV), clientes, configurações
- SEO básico: meta tags, Open Graph, sitemap, robots, Schema.org
