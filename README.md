# Bazaar

Bazaar is a complete online shop written in plain PHP 8.3 without a framework. The
storefront has a faceted catalog, product variants, a session cart, coupons, Stripe
Checkout, customer accounts, wishlists and verified-buyer reviews in English and Polish.
The back office covers catalog, orders, review moderation, coupons, users and sales
analytics, with role-based access. Routing, request handling, sessions, CSRF protection,
the database layer, migrations, views, configuration and translations are written from
scratch in about a dozen small classes under `src/Core`. The only runtime dependency is
the official Stripe SDK.

## Features

### Storefront

- **Home page** with the newest products and a category overview with product counts.
- **Catalog search** at `/products`: full-text match on name and description, category
  filter, price range, and attribute filters (for example size and colour). Values
  within one attribute are combined with OR, different attributes with AND.
- **Live facet counts**: every category and attribute value shows how many products
  would match. Each attribute's counts ignore that attribute's own selection, so the
  counts stay useful while you narrow the list. The price inputs show the min and max
  of the current result set.
- **Sorting** by newest, price ascending, price descending, name and rating, plus
  pagination that keeps all active filters.
- **Product page** with description, image, stock status ("only 3 left", "sold out"),
  average rating and approved reviews.
- **Product variants**: a product can have SKUs built from attribute values (Size:
  Large, Color: Sand). Each variant has its own price and stock. Sold-out variants are
  disabled in the picker, and catalog cards show "from" the lowest variant price.
- **Session cart** for guests and signed-in customers. Lines are keyed by product or
  variant. Quantities are capped at available stock, and inactive or deleted products
  are removed when the cart is read.
- **Coupons**: percentage or fixed-amount codes with a minimum subtotal, an optional
  redemption limit, an optional start and expiry date, and an active flag. The code is
  checked when it is applied and checked again on the cart page and at checkout.
- **Checkout with Stripe Checkout** (test mode). Placing an order creates a `pending`
  order with name, price and variant label copied onto each line, then redirects to
  Stripe. The order becomes `paid` when the customer returns or when the webhook
  arrives, whichever comes first. An order whose total is 0.00 (for example with a
  100 percent coupon) skips Stripe and is marked paid right away. If Stripe cannot
  create the session, the order is marked `failed` and the customer is sent back to
  checkout with an error, with the cart untouched.
- **Customer accounts**: registration with validation, sign in, sign out, order history
  and order detail pages, where an unpaid order can be cancelled.
- **Wishlist**: save or remove products from the catalog or product page and view them
  on `/account/wishlist`. The header shows the wishlist count.
- **Verified-buyer reviews**: only customers with a paid or fulfilled order containing
  the product can review it, once per product. New reviews wait for moderation before
  they appear.
- **English and Polish UI**: a switch in the shop header and in the admin sidebar, or
  `?lang=pl` on any URL. The choice is stored in the session. Every page of the shop and
  the back office, flash and validation messages, order mail and the coupon note on
  Stripe line items are translated. The order remembers the customer's language, so
  its mail and Stripe Checkout use it too.

### Back office (`/admin`)

- **Dashboard**: product count, orders per status, revenue, low-stock list and recent
  orders.
- **Analytics**: 14-day revenue and order chart, top five products by units sold,
  average order value, units sold, coupon usage and discount totals, and low stock.
- **Products**: create, edit and delete products with category, slug (generated from the
  name when left empty), price, stock, image URL and active flag.
- **Variants and attributes**: define attributes and their values, then build variants
  for a product from them, each with its own SKU (generated when left empty), price
  and stock.
- **Categories**: create, edit and delete categories.
- **Orders**: list and detail views. Paid orders can be marked as fulfilled. Pending and
  paid orders can be cancelled. Cancelling a paid order refunds it through Stripe when
  Stripe is configured, and the detail view shows the refund.
- **Review moderation**: pending, approved and rejected queues with approve and reject
  actions.
- **Coupons**: create and delete coupons. Admin only.
- **Users and roles**: list users and change their role. Admin only, and admins cannot
  change their own role.

### Roles and permissions

There are three roles: `customer`, `manager` and `admin`.

| Capability | Manager | Admin |
|---|---|---|
| Catalog, variants, attributes, categories | yes | yes |
| Orders, fulfilment and cancellation | yes | yes |
| Review moderation | yes | yes |
| Dashboard and analytics | yes | yes |
| Coupons | no | yes |
| Users and roles | no | yes |

Capabilities are defined in `src/Support/Permissions.php` and exposed to views as
`$can('coupons.manage')`, which decides what the admin navigation shows. Routes are
protected by the `staff` and `admin` middleware.

### Order lifecycle and mail

```
pending --(Stripe payment confirmed, or total is 0.00)--> paid --(staff fulfils)--> fulfilled
   |  |                                                   |
   |  +--(Stripe session could not be created)--> failed  +--(staff cancels, refund)
   |                                                      |
   +--(customer or staff cancels)----------> cancelled <--+
```

- Marking an order paid is idempotent. Only a `pending` order changes state, so the
  success page and the webhook can both deliver the same payment safely.
- Payment re-checks stock for every line, then lowers product or variant stock and
  updates the order status in one transaction. If the order used a coupon, its
  redemption count goes up. A free order goes through exactly the same step, so stock,
  coupon redemption and the confirmation mail behave as for a card payment.
- A customer can cancel their own order while it is unpaid. Staff can cancel any order
  that has not been fulfilled. Cancelling an unpaid order first expires its open Stripe
  Checkout Session, so it can no longer be paid. Cancelling a paid order puts the
  product or variant stock back and lowers the coupon's redemption count, in the same
  transaction as the status change. With Stripe configured, the same transaction
  creates a Stripe refund for the order total; if Stripe rejects it, nothing changes
  and staff see an error. Without Stripe, the order is cancelled and staff are told
  to refund the payment manually.
- The customer gets an order confirmation (lines, variant labels, discount and total)
  when the order is paid, and a status update when it is fulfilled or cancelled. The
  cancellation mail states the refunded amount.
- Mail goes through a small `Mailer` interface with a log driver (writes to
  `storage/logs/mail.log`) and a built-in SMTP client (STARTTLS, implicit TLS or plain,
  AUTH LOGIN). See [Mail](#mail).

## The micro-framework

The whole request path is in `src/Core`. Each class does one job and can be read in a
few minutes.

### Request lifecycle

```
public/index.php
  -> App::boot()   load .env, start the session, build repositories and services,
                   choose the locale, share view globals, register routes
  -> App::run()    match the route
                   -> 404 / 405 if nothing matches
                   -> check CSRF on every POST (except routes tagged "webhook")
                   -> run middleware: guest, auth, staff, admin
                   -> new Controller(Context) -> action(...route params)
                   -> Response::send()
```

### Router (`Router`, `RouteMatch`)

Routes are registered in `src/Routes.php` as `$router->get('/products/{slug}',
[CatalogController::class, 'show'], ['auth'])`. Each `{name}` placeholder becomes a
named regex group matching one path segment, and its value is passed to the controller
action as an argument. The router tells "path exists but method is wrong" (405) apart
from "no such path" (404). Middleware are plain string tags on the route that
`App::guard()` checks.

### Request and Response

`Request::capture()` turns PHP globals into a read-only value object: method, path
without trailing slash, query, body, headers and raw body (needed to verify the Stripe
webhook signature). `Request::integer()` reads numeric input safely. `Response` holds
status, headers and body, with `html()`, `json()` and `redirect()` constructors.
Controllers always return a `Response`. Nothing writes output directly.

### Context instead of a container

There is no service container and no global lookup. `App::boot()` builds every
repository and service once and puts them into a `Context` object with readonly
properties. Each controller receives that object in its constructor, so its
dependencies are explicit and typed.

### Sessions (`Session`)

Cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` when `APP_URL` is `https`. Strict
mode and cookie-only sessions are on. The session id is regenerated on sign in and sign
out. Flash messages are stored once and removed on the next read.

### CSRF (`Csrf`)

Each session gets a 256-bit random token. Every form includes it as a hidden
`csrfToken` field. The front controller checks it with `hash_equals` on every POST
before any controller runs, so a missing or wrong token returns 419. The Stripe webhook
is exempt because it is verified by its signature instead.

### Database (`Database`)

`Database` is a thin PDO wrapper. It turns on exception mode and associative fetch,
and turns off emulated prepares. SQLite connections also enable foreign keys. Its only
query methods are `select`, `selectOne`, `scalar`, `insert` and `statement`, and each
one calls `prepare` and `execute` with bound parameters. `transaction()` commits or
rolls back around a closure. `DB_DRIVER` selects a SQLite, MySQL or PostgreSQL
connection. Repositories in `src/Repositories` hold all SQL, one class
per aggregate. Values the user controls (sort order, filters) are never inserted into
SQL text: sort keys map to fixed `ORDER BY` clauses through `match`, and filters are
built as fragments with their own placeholders.

### Migrations (`Migrator`)

Plain `.sql` files in `database/migrations` run in filename order. Each applied file is
recorded in a `migrations` table, so `bin/migrate.php` only runs new files. The test
suite builds its in-memory database from the same files.

| File | Adds |
|---|---|
| `001_init.sql` | users, categories, products, orders, order items |
| `002_variants_coupons_reviews_wishlist_roles.sql` | attributes and values, variants, coupons, reviews, wishlist, user roles, order discount and variant columns |
| `003_order_locale_payments_refunds.sql` | order locale, Stripe payment intent, failed timestamp, Stripe refund id, refunded amount and time |

### Views (`View`)

Templates are plain PHP files in `views/`, addressed with dots (`admin.orders.show`).
A template is rendered into a layout (`layout` for the shop, `admin.layout` for the
back office), with shared values such as the current user, CSRF token, cart count,
locale, `$t()` and `$can()`. All dynamic output goes through `e()`
(`htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE`).

### Configuration and translations

`Config` reads `.env` (comments, quoted values) and fills in defaults for every key.
`Translator` loads `resources/lang/<locale>.php` arrays, looks up dotted keys,
replaces `:name` placeholders, and falls back first to English and then to the key
itself. Views call `$t('key')`, controllers `$this->t('key')`. Date formats are
translation keys too (`format.date`, `format.dateTime`). A test checks that both
language files have the same keys, that every key used in a view or class exists,
and that no view contains untranslated text. The Polish catalog is written without
diacritics.

## Requirements

- PHP 8.3 with `pdo` and `pdo_sqlite`. The tests need it too.
- `openssl` for SMTP over TLS.
- Composer.

## Quick start

```bash
composer install
cp .env.example .env
php bin/migrate.php      # create the schema
php bin/seed.php         # demo catalog, variants, coupons, accounts, one paid order
php -S 127.0.0.1:8000 -t public
```

Open http://127.0.0.1:8000. Composer also provides `composer migrate`, `composer seed`
and `composer test`.

Seeded accounts:

| Role | Email | Password |
|---|---|---|
| Admin | admin@bazaar.test | adminpass123 |
| Manager | manager@bazaar.test | managerpass123 |
| Customer | customer@bazaar.test | password123 |

Seeded coupons: `WELCOME10` (10 percent off, minimum 20.00) and `SHIP5` (5.00 off,
minimum 30.00). The linen apron has size variants and the wool throw has colour
variants. The sample customer has a paid order and an approved review for the soy
candle.

## Configuration

All settings are read from `.env`. Defaults live in `src/Core/Config.php`.

| Key | Default | Meaning |
|---|---|---|
| `APP_ENV`, `APP_DEBUG` | `production`, `false` | Environment flags |
| `APP_URL` | `http://127.0.0.1:8000` | Base URL for Stripe return links. `https` makes the session cookie `Secure` |
| `CURRENCY` | `usd` | Order and Stripe currency |
| `DB_DRIVER` | `sqlite` | `sqlite`, `mysql` or `pgsql` (see Limitations) |
| `DB_DATABASE` | `database/bazaar.sqlite` | SQLite file (relative to the project) or database name |
| `DB_HOST`, `DB_PORT` | `127.0.0.1`, `3306` | Server databases only |
| `DB_USERNAME`, `DB_PASSWORD` | `root`, empty | Server databases only |
| `STRIPE_SECRET_KEY` | placeholder | `sk_test_...` |
| `STRIPE_PUBLISHABLE_KEY` | placeholder | `pk_test_...` |
| `STRIPE_WEBHOOK_SECRET` | placeholder | `whsec_...` |
| `MAIL_DRIVER` | `log` | `log` or `smtp` |
| `MAIL_HOST`, `MAIL_PORT` | empty, `587` | SMTP server |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | empty | SMTP credentials. An empty username skips authentication |
| `MAIL_ENCRYPTION` | `tls` | `tls` (STARTTLS), `ssl` (implicit TLS) or `none` |
| `MAIL_FROM` | `orders@bazaar.test` | Sender address of order mail |

## Mail

`MailerFactory` picks the mailer from `MAIL_DRIVER` when the app boots:

- `log` (default) appends every message with its headers to `storage/logs/mail.log`.
  This is the right choice for development: place an order and read the log.
- `smtp` sends through `SmtpMailer`, a small SMTP client built on `fsockopen`. It
  handles multi-line server replies, STARTTLS (`MAIL_ENCRYPTION=tls`, usually port
  587), implicit TLS (`ssl`, usually port 465), `AUTH LOGIN`, CRLF line endings and
  dot-stuffing.

```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=postmaster@example.com
MAIL_PASSWORD=app-password
MAIL_ENCRYPTION=tls
MAIL_FROM=orders@example.com
```

An unknown driver or encryption value, or `smtp` without `MAIL_HOST`, stops the app at
boot with a clear error. A misconfigured mailer never fails silently.

## Stripe

Payments run in Stripe test mode. Put your test keys from
https://dashboard.stripe.com/test/apikeys into `.env`. While the placeholders are in
place, the checkout page shows a "payments disabled" notice and disables the pay
button, except for free orders, which need no keys. The rest of the shop works without
any keys.

1. `POST /checkout` creates a `pending` order and a Checkout Session, stores the session
   id on the order and redirects to Stripe. The session's line items come from the
   order lines (variant price, variant label, quantity). A coupon discount is spread
   across the lines in whole cents, splitting a line in two when its units cannot share
   the discount evenly, so the Stripe total always equals the order total. Orders with
   a 0.00 total never reach Stripe. If creating the session fails, the order is marked
   `failed` immediately instead of staying `pending`.
2. `/checkout/success?session_id=...` retrieves the session. If it is paid and belongs to
   the signed-in customer, the order is marked paid, the payment intent id is stored
   and the cart is cleared.
3. `POST /webhook/stripe` verifies the `Stripe-Signature` header and handles
   `checkout.session.completed` in the background. Because marking an order paid is
   idempotent, both paths can fire.
4. Staff cancelling a paid order calls the Refund API on the order's payment intent
   (read from the Checkout Session when it was not stored) for the full total, with the
   idempotency key `refund-<payment intent>`, so a retried request cannot refund twice.
   The refund id, amount and time are saved on the order.

To test the webhook locally, run `stripe listen --forward-to 127.0.0.1:8000/webhook/stripe`.

## Database schema

Column names are camelCase. Money is always stored as integer cents.

- `users`: email (unique), passwordHash (bcrypt), name, role, legacy isAdmin flag,
  createdAt.
- `categories`: name, slug (unique), description.
- `products`: categoryId (set null on delete), name, slug (unique), description,
  priceCents, stockQuantity, imageUrl, isActive, createdAt.
- `attributes`, `attributeValues`: named attributes and their ordered values.
- `productVariants`: productId (cascade), sku (unique), priceCents, stockQuantity,
  isActive. `variantOptions` links a variant to one value per attribute.
- `orders`: userId, status (`pending`, `paid`, `fulfilled`, `cancelled`, `failed`),
  totalCents, discountCents, couponCode, currency, locale, stripeSessionId,
  paymentIntentId, stripeRefundId, refundedCents, refundedAt, and a timestamp per
  status.
- `orderItems`: orderId (cascade), productId (set null), variantId, with productName,
  variantLabel and unitPriceCents copied at order time, so past orders stay correct
  after catalog edits.
- `coupons`: code (unique), type (`percent` or `fixed`), value, minSubtotalCents,
  maxRedemptions, redeemedCount, startsAt, expiresAt, isActive.
- `reviews`: productId, userId, rating 1-5, title, body, status (`pending`, `approved`,
  `rejected`), moderatedAt. One review per user and product.
- `wishlistItems`: userId, productId. Unique per pair.

## Tests

```bash
vendor/bin/phpunit
```

The PHPUnit suite has 118 tests: router matching and 405 handling, CSRF tokens, the
translator and translation coverage (same keys in both languages, every used key
present, no untranslated text in views), pages rendered in Polish, permissions and
roles, the session cart with variants and stock caps, coupon rules and discounts,
coupon redemption on payment, the order lifecycle with stock decrement and idempotent
payment, free orders that skip Stripe, failed Checkout Session creation, cancellation
with stock and coupon restore, Stripe refunds (request payload, idempotency key,
rejected refunds), Stripe line items matching the order total, order mail and the
configured sender, the mailer factory, review eligibility and moderation, wishlist
behaviour, and faceted search (filters, facet counts, sorting, pagination, price
range). Stripe calls go to a fake HTTP client (`tests/FakeStripeHttpClient.php`), so
no network or keys are needed. Database tests run against
an in-memory SQLite database built from the real migrations, so they need
`pdo_sqlite`. Without it, only the tests that do not touch the database will pass.

## Project layout

```
public/index.php          Front controller
public/assets/app.css     Stylesheet for the shop and the admin panel
src/Core/                 App, Router, RouteMatch, Request, Response, Context,
                          Session, Csrf, Database, Migrator, View, Config
src/Routes.php            Route table with middleware tags
src/Controllers/          Storefront controllers
src/Controllers/Admin/    Back-office controllers
src/Repositories/         SQL, one class per aggregate
src/Services/             Auth, Cart, OrderService, CouponService, ReviewService,
                          ProductSearch, Analytics, StripeGateway
src/Mail/                 Mailer interface, LogMailer, SmtpMailer, NullMailer,
                          MailerFactory, OrderMailer
src/Support/              Translator, Role, Permissions, helpers (e, formatMoney, slugify)
resources/lang/           en.php, pl.php
views/                    Shop and admin templates, partials, error pages
database/migrations/      Versioned SQL schema
bin/                      migrate.php, seed.php
tests/                    PHPUnit suite
```

## Limitations

- Orders from abandoned checkouts stay `pending` until the customer or staff cancels
  them. Nothing expires them automatically.
- Refunds are always for the full order total. Partial refunds have to be issued in the
  Stripe dashboard, and refunds issued there are not synced back to the order.
- Product images are referenced by URL. There is no upload.
- The price filter and price sort use the product's base price, not variant prices.
- Mail is sent during the request, with no queue or retry.
- The migrations use SQLite syntax (`AUTOINCREMENT`, `ALTER TABLE ... ADD COLUMN`).
  The connection layer can open MySQL and PostgreSQL connections, but the schema has
  to be ported before those drivers will work.
- Some admin records cannot be edited after creation. Coupons and variants can only be
  created or deleted. Attributes and their values can only be added.

## License

MIT. See [LICENSE](LICENSE).
