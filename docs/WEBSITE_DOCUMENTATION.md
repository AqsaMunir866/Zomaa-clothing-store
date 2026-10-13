# Website Documentation — Laravel Vue E‑Commerce ("Maison Blanc")

A full‑stack clothing e‑commerce application with **three parts**:

1. **Storefront** (public website) — Laravel Blade + Alpine.js + Tailwind.
2. **Admin Panel** — a separate Vue 3 SPA (Vuex + Vue Router).
3. **Backend API** — Laravel 11 REST API (Sanctum tokens) powering the admin panel and checkout.

Currency is **PKR** (`Rs`). Payments are manual (COD / Bank Transfer / JazzCash / EasyPaisa) with proof upload; a legacy Stripe path also exists.

---

## 1. Tech Stack

| Layer | Technology |
|---|---|
| Backend framework | Laravel 11 (`^11.0.7`), PHP 8.2 |
| Auth | Laravel Breeze (web) + Laravel Sanctum (API tokens) |
| Public frontend | Blade templates, Alpine.js 3, Tailwind CSS 3, Vite 5 |
| Admin frontend | Vue 3, Vuex 4, Vue Router 4, CKEditor 5, Chart.js, Tailwind, Vite 5 |
| Database | MySQL 8 (default `laravel_vue_ecommerce`) |
| Media storage | Local `public` disk (`storage/app/public`, symlinked to `public/storage`) |
| Slugs | `spatie/laravel-sluggable` |
| Payments | Manual (COD / bank / JazzCash / EasyPaisa) + legacy Stripe (`stripe/stripe-php`) |
| Dev tooling | Docker Compose (php-fpm, nginx, mysql, 2× node), Telescope, Debugbar |

---

## 2. Repository Structure

```
laravel-vue-ecommerce/
├── app/
│   ├── Enums/                     # OrderStatus, PaymentStatus, AddressType, CustomerStatus
│   ├── Helpers/Cart.php           # Guest (cookie) + authenticated (DB) cart logic
│   ├── Http/
│   │   ├── Controllers/           # Storefront controllers
│   │   │   ├── ProductController.php   # home, byCategory, product view
│   │   │   ├── CartController.php      # cart add/remove/update
│   │   │   ├── CheckoutController.php  # checkout, place order, Stripe
│   │   │   ├── OrderController.php     # my orders, order view, pay, proof upload
│   │   │   ├── ProfileController.php   # profile + addresses
│   │   │   ├── ReportController.php
│   │   │   ├── Api/                    # Admin REST API controllers
│   │   │   │   ├── AuthController.php          # login/logout/getUser
│   │   │   │   ├── ProductController.php       # products CRUD
│   │   │   │   ├── UserController.php          # admin users CRUD
│   │   │   │   ├── CustomerController.php      # customers CRUD + countries
│   │   │   │   ├── CategoryController.php      # categories CRUD + tree
│   │   │   │   ├── BannerController.php        # banners CRUD
│   │   │   │   ├── PaymentMethodController.php # payment methods CRUD
│   │   │   │   ├── SettingController.php       # business settings
│   │   │   │   ├── OrderController.php         # orders + status changes
│   │   │   │   └── DashboardController.php     # dashboard widgets
│   │   │   └── Auth/                   # Breeze auth controllers
│   │   ├── Middleware/
│   │   │   ├── Admin.php               # requires is_admin == 1 (else 403 JSON)
│   │   │   └── GuestOrVerified.php     # guests OR verified users (blocks unverified)
│   │   ├── Requests/                   # Form request validation
│   │   └── Resources/                  # API JSON resources
│   ├── Mail/                           # NewOrderEmail, OrderUpdateEmail
│   ├── Models/                         # Eloquent models (see §5)
│   ├── Support/Money.php               # Rs formatting
│   └── Support/Settings.php            # cached business settings
├── backend/                            # Vue 3 admin SPA (separate Vite app)
│   ├── src/
│   │   ├── components/                 # AppLayout, Sidebar, Navbar, core UI, charts
│   │   ├── views/                      # Dashboard, Products, Categories, Banners,
│   │   │   │                           # PaymentMethods, Settings, Orders, Users,
│   │   │   │                           # Customers, Reports, auth screens
│   │   ├── store/                      # Vuex: state, mutations, actions
│   │   ├── router/index.js             # routes + auth guard
│   │   ├── axios.js                    # API client (Bearer token)
│   │   └── main.js
│   ├── .env.example                    # VITE_API_BASE_URL
│   └── vite.config.js
├── bootstrap/app.php                   # Laravel 11 middleware + routing config
├── config/                             # standard Laravel config
├── database/
│   ├── migrations/                     # schema
│   └── seeders/                        # AdminUser, Country, ClothingStore,
│                                       # BannerAndSettings, Product
├── docker-compose/                     # nginx + php ini overrides
├── public/                             # web root (index.php, build assets, storage link)
├── resources/
│   ├── css/app.css, js/app.js          # storefront Vite inputs
│   └── views/                          # Blade templates
├── routes/
│   ├── web.php                         # storefront + auth routes
│   ├── api.php                         # admin API routes
│   └── auth.php                        # Breeze auth routes
├── tests/                              # PHPUnit test suite (Unit + Feature)
├── Dockerfile                          # php-fpm image
├── docker-compose.yml                  # local dev stack
└── vite.config.js                      # storefront Vite config
```

---

## 3. Architecture & Request Flow

```
                         ┌─────────────────────────────┐
   Public visitor ─────► │  Laravel (Blade + Alpine)   │  Storefront
                         │  routes/web.php             │  (SSR HTML + Vite assets)
                         └──────────────┬──────────────┘
                                        │ Eloquent
                                        ▼
   Admin staff ─────► ┌─────────────┐  ┌───────────────┐
   (Vue 3 SPA)        │  Vue Admin  │─►│  Laravel API  │  JSON /api/*
                      │  backend/   │  │ routes/api.php│  (Sanctum Bearer token)
                      └─────────────┘  └───────┬───────┘
                                               │
                                               ▼
                                       ┌───────────────┐
                                       │    MySQL      │
                                       └───────────────┘
```

- **Storefront** is server‑rendered by Laravel (`routes/web.php`), enhanced with Alpine.js and Tailwind compiled by Vite into `public/build`.
- **Admin panel** is a decoupled SPA. It talks to the same Laravel app under `/api` using a **Sanctum personal access token** stored in `sessionStorage`. Requests that return **401** clear the token and redirect to login (`backend/src/axios.js`).
- **Auth split**:
  - Storefront users → session‑based (`web` guard), created at registration with a matching `Customer` profile.
  - Admin users → `is_admin = 1`, token‑based (`sanctum`), protected by the `admin` middleware.

---

## 4. Route Map

### 4.1 Storefront (web)

**Public (guests, or verified users — `guestOrVerified` middleware)**

| Method | URI | name | Purpose |
|---|---|---|---|
| GET | `/` | `home` | Home (hero banners, shop‑by‑category, featured products) |
| GET | `/category/{category:slug}` | `byCategory` | Category listing (includes children) |
| GET | `/product/{product:slug}` | `product.view` | Product detail |
| GET | `/cart` | `cart.index` | Cart page |
| POST | `/cart/add/{product:slug}` | `cart.add` | Add to cart |
| POST | `/cart/remove/{product:slug}` | `cart.remove` | Remove from cart |
| POST | `/cart/update-quantity/{product:slug}` | `cart.update-quantity` | Change quantity |

**Authenticated + email verified (`auth`, `verified`)**

| Method | URI | name | Purpose |
|---|---|---|---|
| GET | `/profile` | `profile` | View/edit profile + addresses |
| POST | `/profile` | `profile.update` | Save profile + addresses |
| POST | `/profile/password-update` | `profile_password.update` | Change password |
| GET | `/checkout` | `checkout` | Checkout (payment method picker) |
| POST | `/checkout/place` | `checkout.place` | Place order (manual methods) |
| GET | `/checkout/placed/{order}` | `checkout.placed` | Order confirmation |
| POST | `/checkout` | `cart.checkout` | Legacy Stripe checkout |
| POST | `/checkout/{order}` | `cart.checkout-order` | Legacy Stripe per‑order |
| GET | `/checkout/success` | `checkout.success` | Stripe success |
| GET | `/checkout/failure` | `checkout.failure` | Stripe failure |
| GET | `/orders` | `order.index` | My orders |
| GET | `/orders/{order}` | `order.view` | Order detail (owner only) |
| GET | `/orders/{order}/pay` | `order.pay` | Payment info / proof upload |
| POST | `/orders/{order}/pay` | `order.pay.submit` | Submit payment proof |

**Auth pages (Breeze):** `/register`, `/login`, `/forgot-password`, `/reset-password/{token}`, `/verify-email`, `/confirm-password`.
**Webhook:** `POST /webhook/stripe` (legacy).

### 4.2 Admin API (`/api`, middleware `auth:sanctum` + `admin`)

| Method | URI | Purpose |
|---|---|---|
| POST | `/api/login` | Admin login (public) → returns `{user, token}` |
| GET | `/api/user` | Current admin |
| POST | `/api/logout` | Revoke current token |
| — | `/api/products` | Products CRUD |
| — | `/api/users` | Admin users CRUD |
| — | `/api/customers` | Customers CRUD |
| — | `/api/categories` (+ `/api/categories/tree`) | Categories CRUD + tree |
| — | `/api/banners` | Banners CRUD |
| — | `/api/payment-methods` | Payment methods CRUD |
| GET/PUT | `/api/settings` | Business settings |
| GET | `/api/countries` | Country list |
| GET | `/api/orders`, `/api/orders/{order}` | Orders list / detail |
| GET | `/api/orders/statuses` | Available order statuses |
| POST | `/api/orders/change-status/{order}/{status}` | Change order status |
| POST | `/api/orders/change-payment-status/{order}/{status}` | Change payment status |
| GET | `/api/dashboard/*` | Counts, income, orders‑by‑country, latest |
| GET | `/api/report/{orders,customers}` | Reports |

---

## 5. Data Model

```
users (id, name, email, password, is_admin, email_verified_at)
  │ 1:1
  └── customers (PK user_id, first_name, last_name, phone, status)
        │ 1:N
        └── customer_addresses (type[shipping|billing], address1.., country_code → countries.code)

users 1:N orders
orders (id, total_price, status, created_by, updated_by)
  ├─ 1:N order_items (order_id, product_id, quantity, unit_price)
  └─ 1:1 payments (order_id, amount, status, type, session_id, payment_proof)

categories (id, name, slug, active, parent_id[self], image)
  └─ N:M products  (pivot: product_categories)

products (id, title, slug, description, price, quantity, published)
  └─ 1:N product_images (product_id, path, url, mime, size, position)

cart_items (user_id, product_id, quantity)     # authenticated cart
banners  (title, kicker, text, image_url, link_label, link_url, position[hero|split], sort_order, active)
settings (key, value)                           # business name, tagline, logo, contact, announcement
payment_methods (name, code, description, details, is_active, sort_order)
countries (code PK, name, states json)
personal_access_tokens                          # Sanctum
```

**Enums**
- `OrderStatus`: `unpaid, processing, paid, cancelled, shipped, completed`
- `PaymentStatus`: `pending, paid, failed`
- `AddressType`: `shipping, billing`
- `CustomerStatus`: `active, disabled`

> Note: `order_details` table exists from an early migration but order lines use `order_items`.

---

## 6. Storefront Flows

### 6.1 Browse
`ProductController@index` → published products, paginated (5/page), search (`?search=`) and sort (`?sort=-updated_at`), plus active **hero** and **split** banners. `byCategory` filters by a category **and all its children** (pivot `product_categories`).

### 6.2 Cart (guest → user)
- **Guest:** stored in a `cart_items` **cookie** (30 days).
- **Authenticated:** stored in the `cart_items` table.
- On login/registration, `Cart::moveCartItemsIntoDb()` merges cookie items into the DB.
- Stock is respected: adding/updating beyond `product.quantity` returns **422** with a message.

### 6.3 Registration & Email Verification
`RegisteredUserController` creates a `User` **and** a linked `Customer` (status active), then logs in. `GuestOrVerified` lets guests browse but requires verified users to continue. Disabled customers are blocked at login (`LoginRequest`).

### 6.4 Profile
`ProfileController` edits name/email/phone and **shipping + billing addresses** (stored in `customer_addresses`). Checkout requires both addresses, else redirects to profile.

### 6.5 Checkout & Order Placement
1. `GET /checkout` — lists **active payment methods**; blocks if addresses missing.
2. `POST /checkout/place` — validates the chosen method, checks stock, decrements stock, creates `Order` (`status = processing`), `order_items`, and a `Payment` (`status = pending`, `type = method code`), clears the cart, redirects to the confirmation page.
3. `GET /checkout/placed/{order}` — shows confirmation + method details.

### 6.6 Payment (manual)
`GET /orders/{order}/pay` (owner only):
- **COD** → shows "no advance payment" message.
- **Bank / JazzCash / EasyPaisa** → shows the method's `details` and an **upload proof** form.
`POST /orders/{order}/pay` stores the proof (JPG/PNG/WEBP/PDF, ≤ 8 MB) on the `public` disk and records `payments.payment_proof`. Admin then verifies and marks the order paid.

### 6.7 Orders
`/orders` lists the user's orders; `/orders/{order}` is owner‑only (403 otherwise). `Order::isPaid()` checks `status === paid`.

---

## 7. Admin Panel Flows (Vue SPA)

Login (`/login`) → token saved in `sessionStorage` → routed SPA under `/app`.

| Section | Route | Capabilities |
|---|---|---|
| Dashboard | `app.dashboard` | counts, income, orders by country, latest orders/customers, charts |
| Products | `app.products` | list, create (`ProductForm`), edit, delete, categories, image upload + reorder |
| Categories | `app.categories` | tree list, create/edit (parent, image), delete |
| Banners | `app.banners` | hero/split banners, image upload, order, active flag |
| Payment Methods | `app.payment-methods` | CRUD; controls what appears at checkout |
| Settings | `app.settings` | business name, tagline, contact, address, announcement, logo |
| Orders | `app.orders` / `app.orders.view` | search, view items, change **order status** & **payment status**, view uploaded proof |
| Customers | `app.customers` / `app.customers.view` | CRUD customer + addresses |
| Users | `app.users` | admin users CRUD |
| Reports | `reports.orders`, `reports.customers` | date‑filtered exports |

**Order lifecycle**

```
processing ──(change status: paid)──► paid ──► shipped ──► completed
     │                                   ▲
     └──(cancelled: stock restored)──► cancelled
Payment: pending ──► paid | failed
```

---

## 8. Configuration & Environment

**Root `.env` (key values)**
```
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=db              # local docker service name
DB_DATABASE=laravel_vue_ecommerce
DB_USERNAME=root
DB_PASSWORD=root
FILESYSTEM_DISK=local
MAIL_MAILER=log
# STRIPE_SECRET_KEY / WEBHOOK_SECRET_KEY (legacy, optional)
```

**`backend/.env`**
```
VITE_API_BASE_URL=http://localhost:8000
```

**Media:** uploads go to `storage/app/public`; run `php artisan storage:link` to expose them at `/storage`.

---

## 9. Seeding

`DatabaseSeeder` runs `AdminUserSeeder` + `CountrySeeder`.
Extra seeders: `ClothingStoreSeeder` (24 products + categories + images), `BannerAndSettingsSeeder` (hero/split banners + business settings), `ProductSeeder` (30 fake products).

Default admin: **admin@example.com / admin123**

---

## 10. Local Development (Docker)

```bash
docker compose up -d            # app(php-fpm) :9000, nginx :8000, mysql :3309, node, admin node :3000
docker exec laravel_app composer install
docker exec laravel_app php artisan key:generate
docker exec laravel_app php artisan migrate --seed
docker exec laravel_app php artisan storage:link
# storefront build (host or node container)
npm install && npm run dev      # Vite storefront :3001
# admin SPA
cd backend && npm install && npm run dev   # :3000
```

Storefront: http://localhost:8000 · Admin: http://localhost:3000

---

## 11. Testing

PHPUnit suite under `tests/`:
- `tests/Unit` — pure logic (Money, enums).
- `tests/Feature` — auth, admin API, storefront, checkout & payment.
- Test config in `phpunit.xml` points at a **separate test database** (`laravel_vue_ecommerce_test`) so tests never touch live data.

Run: `docker exec laravel_app php artisan test`
