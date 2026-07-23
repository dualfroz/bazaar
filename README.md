# Bazaar

A small e-commerce shop built on a hand-rolled PHP microframework - no Laravel, no
Symfony, no external framework at all. Catalog, session cart, Stripe Checkout (test
mode), customer accounts and a full admin back office, all on top of a router, a PDO
layer that only ever runs prepared statements, SQL migrations, hardened sessions, CSRF
protection and output escaping written from scratch.

## Requirements

- PHP 8.3 with `pdo` and a PDO driver:
  - `pdo_sqlite` for the default dev/test setup, or
  - `pdo_mysql` / `pdo_pgsql` for a server profile.
- Composer.

## Quick start

```bash
composer install
cp .env.example .env
php bin/migrate.php      # create the schema
php bin/seed.php         # load demo catalog + accounts
php -S 127.0.0.1:8000 -t public
```

Open http://127.0.0.1:8000.

Seeded accounts:

| Role     | Email                 | Password       |
|----------|-----------------------|----------------|
| Admin    | admin@bazaar.test     | adminpass123   |
| Customer | customer@bazaar.test  | password123    |

The admin panel lives at `/admin` and is gated behind the admin role.

## Tests

```bash
vendor/bin/phpunit
```

Covers the router, the session cart, the order lifecycle (pending -> paid -> fulfilled
with stock decrement), authentication and CSRF. Order and auth tests run against an
in-memory SQLite database built from the real migrations.

## Architecture

Everything a request touches is hand-written under `src/`.

```
public/index.php        Front controller - the single entry point.
src/Core/
  App.php               Boots services, wires the Context, dispatches the request.
  Router.php            Method + path matching, {param} capture, per-route middleware.
  RouteMatch.php        Result of a match (handler, params, middleware, status).
  Request.php           Immutable wrapper over the incoming request.
  Response.php          Status, headers and body; html/json/redirect helpers.
  Context.php           Explicit container passed to every controller.
  Database.php          PDO wrapper - prepared statements only, transactions.
  Migrator.php          Runs .sql migrations, tracks them in a migrations table.
  Session.php           Hardened session (httponly, samesite, strict mode) + flash.
  Csrf.php              Per-session token, constant-time verification.
  View.php              Plain-PHP view engine with a layout and partials.
  Config.php            Reads .env, exposes typed config.
src/Routes.php          The route table.
src/Controllers/        HTTP controllers (storefront + Admin/*).
src/Repositories/       One class per table, all queries parameterized.
src/Services/           Auth, Cart, OrderService, StripeGateway, domain exceptions.
src/Support/helpers.php  e(), formatMoney(), slugify(), config(), basePath().
views/                  Layouts, partials and page templates (escaped output).
database/migrations/    Versioned SQL schema.
bin/                migrate.php, seed.php.
tests/                  PHPUnit suite.
```

Request lifecycle: `public/index.php` -> `App::boot()` builds the services and shares
view data -> `App::run()` matches the route, verifies CSRF on every mutating request
(except the Stripe webhook, which is verified by signature instead), runs the route
middleware (`guest` / `auth` / `admin`), instantiates the controller and returns a
`Response`.

### Security

- Every SQL statement goes through `PDO::prepare` + bound parameters. There is no string
  interpolation of user values into SQL anywhere.
- CSRF tokens are required on all POST forms and checked centrally in the front controller.
- Passwords are stored with `password_hash` (bcrypt) and checked with `password_verify`.
- Sessions are httponly, `SameSite=Lax`, use strict mode, and the id is regenerated on
  login and logout.
- All template output is escaped through `e()` (htmlspecialchars with `ENT_QUOTES`).

## Database schema

`users`
- `id`, `email` (unique), `passwordHash`, `name`, `isAdmin`, `createdAt`.

`categories`
- `id`, `name`, `slug` (unique), `description`.

`products`
- `id`, `categoryId` (FK -> categories, set null on delete), `name`, `slug` (unique),
  `description`, `priceCents`, `stockQuantity`, `imageUrl`, `isActive`, `createdAt`.

`orders`
- `id`, `userId` (FK -> users), `status` (`pending` | `paid` | `fulfilled` |
  `cancelled`), `totalCents`, `currency`, `stripeSessionId`, `createdAt`, `paidAt`,
  `fulfilledAt`, `cancelledAt`.

`orderItems`
- `id`, `orderId` (FK -> orders, cascade), `productId` (FK -> products, set null),
  `productName`, `unitPriceCents`, `quantity`. Name and price are snapshotted at order
  time so historical orders stay correct if the product later changes.

Money is stored as integer cents everywhere to avoid floating point rounding.

## Stripe keys

Payments run in Stripe test mode. Put your test keys in `.env`:

```
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Get them from https://dashboard.stripe.com/test/apikeys. While the placeholders are in
place the checkout page renders with a clear "payments disabled" notice and the pay
button is disabled, so the rest of the shop is fully usable without any keys.

Order flow with real test keys:

1. `POST /checkout` creates a `pending` order and a Stripe Checkout Session, then
   redirects the customer to Stripe.
2. On return, `/checkout/success` verifies the session and, if paid, marks the order
   `paid` and decrements stock.
3. `POST /webhook/stripe` handles `checkout.session.completed` out of band (verified by
   the webhook signature, so it is exempt from CSRF) and marks the order paid as well;
   `markPaid` is idempotent so the two paths never double-count.

Point a Stripe webhook (or `stripe listen --forward-to`) at `/webhook/stripe` to test the
async path.

## Configuration

`.env` keys: `APP_ENV`, `APP_DEBUG`, `APP_URL`, `CURRENCY`, the `DB_*` block
(`DB_DRIVER` = `sqlite` | `mysql` | `pgsql`), and the `STRIPE_*` block. Defaults live in
`src/Core/Config.php`.
