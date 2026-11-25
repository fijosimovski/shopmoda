# ShopModa — Briefing: SEO, Language, AJAX Cart, Hosting & Launch

_Written 24.11.2026, based on the current code in `C:\ShopModa`._

---

## 0. Where the project stands (snapshot)

| Area | State |
|---|---|
| Stack | Laravel 12, PHP 8.2+, MySQL, Bootstrap 5.3 + Bootstrap Icons from CDN, plain JS in `public/js`, one stylesheet `public/css/shopmoda.css`. No Vite build is used by the layout, so **no npm step is needed on the server**. |
| Shop | Home, product list with filters (`?category=&brand=&model=&gender=&sale=&sort=&q=`), product page with sizes (EU), promotions page with countdown, how-to-order page, session cart (guests too), checkout with coupons, cash on delivery, order confirmation by emailed 6‑digit code + signed link, reminder after 24h, expiry. |
| Accounts | Register / login / forgot password (code by mail), my orders, profile, contact form (logged-in only). |
| Admin | Dashboard, analytics, visits log, products + images, brands, models, categories, promotions, coupons, announcements, orders (+ Excel export), users & roles, user logs, messages, settings. |
| Scheduler | `routes/console.php`: expire orders 03:00, reminders hourly, daily Excel report 00:00, clear reset codes, visit aggregation, pruning. **Needs a server cron every minute.** |
| Mail | Sent synchronously (no `ShouldQueue`), so **no queue worker is needed** on the server. Currently goes through **Gmail SMTP with your personal address** — must change before launch (see §5). |
| Language | Everything is **hard-coded English** (`<html lang="en">`, `APP_LOCALE=en`, ~140+ UI phrases in Blade plus messages in controllers/requests/mails). Only 8 `__()` calls exist. |
| Not a git repo | Strongly recommended to `git init` + private GitHub repo before deploying. |

---

## 1. SEO — what is there, what is missing, what to do

### Current state (honest audit)

Good already:
- Clean product URLs with slugs: `/products/{slug}`, `/promo/{slug}`.
- Per-page `<title>` and `<meta name="description">` via `@yield` (product page uses its description).
- One `<h1>` per product page, breadcrumbs on the product page, mobile viewport, Bootstrap = responsive.
- Paginated list keeps filters (`withQueryString()`).

Missing / wrong:
1. `<html lang="en">` — wrong for a Serbian shop.
2. Titles are `ShopModa - Home`, `ShopModa - Sneakers` — brand first, generic words. Google shows ~60 chars; the keyword should come first.
3. **No canonical tags.** `/products?sort=price_asc&page=2&brand=nike` etc. create hundreds of duplicate URLs of the same content.
4. **No `sitemap.xml`.**
5. `robots.txt` allows everything (cart, checkout, account, admin, login, search results).
6. **Category / brand / gender pages are only query strings** (`/products?category=running`). Google can index them, but they have no own title, H1, description or text → they won't rank for searches like *"nike patike muške"*, *"patike za trčanje"*.
7. **No structured data (JSON-LD).** No Product (price, RSD, in stock), no BreadcrumbList, no Organization → no price/stock in Google results.
8. **No Open Graph / social tags** → links shared on Instagram/Facebook/Viber/WhatsApp show no picture.
9. Images: product card and thumbnails have `alt=""`; no `width`/`height` (layout shift), no `loading="lazy"`, uploads are not resized/compressed or converted to WebP.
10. Performance: 3 Google font families + Bootstrap + Icons from CDNs, render-blocking. OK, but can be faster (self-host fonts, `preload`, cache headers).
11. Contact is login-only; there is no public "About / Contact / Company info" page — bad for trust and also legally required (§4).
12. No custom 404/500 pages, no 301 redirect when a product slug changes.

### SEO to-do list (in priority order)

**A. Must-do before launch (≈1–2 days of work)**
1. `lang="sr"` (or `sr-Latn`), `APP_LOCALE=sr`, Serbian texts (see §2).
2. Title pattern `@yield('pageTitle') | ShopModa`, write real titles:
   - Home: *"Patike za trčanje, lifestyle i košarku | ShopModa"*
   - Product: *"Nike Air Max 90 muške patike – 12.999 RSD | ShopModa"*
3. Canonical tag in `head.blade.php`: product page → its own URL; list page → URL with only the "real" filter (category/brand/gender), without `sort`, `q`.
4. `noindex, follow` on: search results (`?q=`), sorted lists, cart, checkout, account, login/register, password pages, order confirm pages.
5. `robots.txt`:
   ```
   User-agent: *
   Disallow: /admin
   Disallow: /cart
   Disallow: /checkout
   Disallow: /account
   Disallow: /orders
   Disallow: /login
   Disallow: /register
   Disallow: /*?*sort=
   Disallow: /*?*q=
   Sitemap: https://YOUR-DOMAIN.rs/sitemap.xml
   ```
6. `sitemap.xml` route (custom, ~40 lines, or `spatie/laravel-sitemap`): home, every active product (with `lastmod` = `updated_at`), every category/brand/gender landing page, active promotions, how-to-order, legal pages.
7. JSON-LD on the product page: `Product` + `Offer` (`price`, `priceCurrency: "RSD"`, `availability` from stock, `brand`, `image`, `sku`), plus `BreadcrumbList`. `Organization` + `WebSite` (with search action) on home.
8. Open Graph + Twitter tags: `og:title`, `og:description`, `og:image` (main product image), `og:type=product`, `og:locale=sr_RS`.
9. Image `alt` = "{brand} {name} – {color/side}", `loading="lazy"` on cards (not on the main product image), `width`/`height`.
10. Custom 404 page with search + popular categories.

**B. Strongly recommended (biggest ranking win, ≈2–3 days)**
11. **Real landing pages with clean URLs** instead of query strings, each with own H1, meta, 1–2 paragraphs of Serbian text at the top/bottom:
    - `/patike/trcanje`, `/patike/lifestyle`, `/patike/kosarka` (categories)
    - `/brend/nike`, `/brend/adidas` (brands)
    - `/muske-patike`, `/zenske-patike`, `/akcija` (gender, sale)
    - Keep the existing query filters working on top of them (and 301-redirect the old `?category=` URLs).
    - Admin: add `meta_title`, `meta_description`, `intro_text` fields to categories and brands.
12. Unique product descriptions in Serbian (not copied from Nike/Adidas — duplicate content). Even 3–4 sentences + material, purpose, fit ("radi pola broja veće").
13. Image pipeline on upload: resize to e.g. 1200px + 400px thumbnail, WebP, compress (`intervention/image`).
14. 301 redirect when a product slug changes (small `redirects` table or keep old slug).
15. Public pages: **O nama**, **Kontakt** (public, with phone, address, PIB), **Uslovi kupovine**, **Politika privatnosti**, **Reklamacije i povraćaj**, **Dostava**.

**C. After launch (ongoing)**
16. Google Search Console + Bing Webmaster: verify domain, submit sitemap, watch "Pages" and "Core Web Vitals".
17. Google Business Profile (if you have a physical address / pickup point).
18. Analytics: GA4 or privacy-friendly Plausible/Umami. With GA4 you need a cookie consent banner.
19. Speed check with PageSpeed Insights; aim for green on mobile.
20. Content: a small "Vodiči" / blog section (*"Kako izabrati patike za trčanje"*, *"Tabela veličina Nike vs Adidas"*) — this is what brings free traffic long-term.
21. Backlinks: Instagram/TikTok bio links, local fashion/running blogs, price-comparison sites (e.g. Shoppster/Idealno-type comparators — check which accept small shops).

---

## 2. Serbian default + English option

### How much does it complicate things?

Short answer: **the big job is not "adding English", it is moving the texts out of the templates.** Right now everything is hard-coded English. Whether you end up Serbian-only or Serbian + English, every phrase has to be touched once. Once that is done, a second language is mostly writing a second file.

| Option | Work | Ongoing cost |
|---|---|---|
| **Serbian only** (translate the hard-coded texts in place) | ~2–3 days | None |
| **Serbian + English, done properly** (translation keys, `/en` prefix) | ~4–6 days | Every new text written twice; product descriptions twice if you want them in English |
| Serbian + Google auto-translate widget | 1 hour | Ugly, bad SEO, **no control** over bad translations — not recommended |

My recommendation: **do it properly with translation files from the start, Serbian default, English as `/en/...`**. Even if you postpone English, using `__()` now costs almost nothing extra and leaves the door open.

### How it would work in this project

1. **Default Serbian, no prefix:** `ShopModa.rs/patike/nike-air-max-90`. **English with prefix:** `ShopModa.rs/en/patike/nike-air-max-90`. A small middleware reads the prefix and calls `app()->setLocale()`. (Paths can stay the same in both languages — simplest; translating the path words too is possible but doubles the route list.)
2. **Interface texts** go to `lang/sr.json` and `lang/en.json`:
   ```json
   // lang/sr.json
   { "Add to cart": "Dodaj u korpu", "Size (EU)": "Veličina (EU)", "Sold out": "Rasprodato" }
   ```
   In Blade: `{{ __('Add to cart') }}`. Laravel's own messages (validation, pagination, passwords) go to `lang/sr/validation.php` etc. — there is a community package `laravel-lang/lang` with Serbian Latin (`sr_Latn`) and Cyrillic ready-made, which you then edit.
3. **Database content** (product description, category name, promotion name/badge, announcements): add `description_en`, `name_en` columns (simplest), or use `spatie/laravel-translatable` (JSON column with `{"sr": "...", "en": "..."}`). Admin forms get a second field "English (optional)"; if empty, Serbian is shown. Brand and product names (Nike Air Max 90) stay the same.
4. **Emails** go out in the language the customer ordered in — store `locale` on the order.
5. **Dates and plurals:** `Carbon::setLocale('sr')`; Serbian has 3 plural forms (1 par, 2 para, 5 pari) — Laravel's `trans_choice` handles it: `"{1} :count par|[2,4] :count para|[5,*] :count pari"`.
6. **Language switch** in the navbar/footer (SR | EN) linking to the same page in the other language.
7. **SEO:** `hreflang` tags on every page (`sr`, `en`, `x-default` → Serbian) and both versions in the sitemap.
8. **Admin panel:** keep it in **one language** (Serbian or English, your choice). Translating admin is wasted effort.

### "How much control will I have over badly translated words?"

**Full control.** Nothing is machine-translated at runtime. Every word the customer sees is a line in `lang/sr.json` / `lang/en.json` (or a field you filled in admin). If a word is wrong, you change that one line and it changes everywhere. Typical workflow:
- I (or a tool) produce the first draft of both files.
- You read `sr.json` top to bottom and fix anything that sounds unnatural (e.g. "Korpa" vs "Korpa za kupovinu", "Poruči" vs "Naruči").
- Missing translations fall back to the key, so nothing breaks — and a small artisan command can list keys that are missing in one of the files.
- Optional later: a "Translations" page in admin to edit these lines in the browser without touching files.

**One decision to make up front: Latin or Cyrillic?** For an online sneaker shop, **latinica** is the norm (search queries are almost always Latin: "patike nike"). Cyrillic can be added later as a third locale (`sr-Cyrl`) and can even be auto-transliterated from Latin, which is one of the few safe automatic conversions.

---

## 3. AJAX add-to-cart with animation + cart sidebar

### What the customer sees
1. Picks size, clicks **Dodaj u korpu**.
2. Button instantly shows a small spinner, the page does **not** reload.
3. A copy of the product image **flies in an arc to the bag icon** in the navbar and shrinks into it (~600 ms).
4. The bag icon does a short "bump" and the counter changes `(2)` → `(3)`.
5. Button turns green-ish **✓ Dodato** for ~1.5 s, then back to normal.
6. A **sidebar (Bootstrap offcanvas) slides in from the right** with: the item just added (highlighted), other cart items with small image, size, quantity, price, the subtotal, active promo savings, and two buttons: **Nastavi kupovinu** / **Idi na plaćanje**.
7. Errors ("Ostalo je samo 2 para u veličini 42") appear inline under the sizes, sidebar does not open.
8. Clicking the bag icon on any page opens the same sidebar instead of going to `/cart` (the `/cart` page stays for editing, coupons are at checkout as now).

### How it is built (fits the existing code)
- **No new libraries.** Bootstrap 5 already has `offcanvas` (follows your "Bootstrap first" rule); animation with the browser's Web Animations API.
- `CartController@store`: if `$request->expectsJson()` → return JSON `{ count, html }` where `html` is the rendered `partials/cart-drawer-items.blade.php`; on error return 422 with the message. Otherwise keep the current redirect (works without JS).
- New `GET /cart/drawer` returns the same partial (when the bag icon is clicked).
- New `partials/cart-drawer.blade.php` included once in `layouts/layout.blade.php`.
- New `public/js/cart-drawer.js`: intercepts the product form submit, `fetch()` with the CSRF token, runs the fly animation, updates the counter, injects the HTML, opens the offcanvas.
- CSS for the flying image, bump, and the drawer goes into `shopmoda.css` (product page section; media queries at the bottom).
- Accessibility: counter in an `aria-live` region, focus moves into the drawer, `prefers-reduced-motion` → skip the flight, just open the drawer.
- Optional later: change quantity / remove directly in the drawer (same JSON approach for `PATCH`/`DELETE /cart/{key}`).

Estimated work: **~1 day** including the drawer from the navbar.

---

## 4. Whole-project briefing before going live

### What is left to do (suggested order)
1. **Language** — move texts to `__()`, Serbian default (§2).
2. **SEO block A + public legal/info pages** (§1).
3. **AJAX cart + drawer** (§3).
4. **Legal & business (Serbia)** — do in parallel, this often takes longest:
   - A registered business (preduzetnik or DOO) with online retail activity (šifra 4791) — talk to an accountant.
   - Required info on the site (Zakon o elektronskoj trgovini / Zakon o zaštiti potrošača): business name, address, PIB, matični broj, email, phone; **Uslovi kupovine**, **pravo na odustanak (14 dana)** with the withdrawal form, **reklamacije**, prices incl. PDV, delivery costs before ordering.
   - **Politika privatnosti** (Zakon o zaštiti podataka o ličnosti — you store names, addresses, phones, IPs, page views).
   - Cookie notice (minimal if you only use session cookies; a proper consent banner if you add Google Analytics / Meta pixel).
   - Ask the accountant about **fiscalization for cash-on-delivery orders**.
   - Contract with a courier (Post Express, D Express, BEX, AKS, City Express…) for pouzeće (cash on delivery) — they pay the collected money to your account.
5. **Production hardening** (code side, ~½ day):
   - `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=error`, `APP_URL=https://…`, `APP_LOCALE=sr`, `APP_FAKER_LOCALE=sr_RS`.
   - `SESSION_SECURE_COOKIE=true`, force HTTPS, `TrustProxies` if behind a proxy/Cloudflare.
   - New `APP_KEY` generated on the server (never reuse the local one).
   - Delete unused `resources/views/welcome.blade.php`.
   - Custom 404/419/500 pages.
   - Make contact form public (or add a public contact page) — currently `auth` only.
   - A **`ProductionSeeder`** (see §6).
6. **Test everything end-to-end on the live server** before announcing (§6 step 10).

---

## 5. Hosting — what you need and what to buy

### Requirements of this app
- PHP **8.2+** with usual extensions (pdo_mysql, mbstring, openssl, fileinfo, zip for the Excel export, gd/imagick if you add image resizing).
- MySQL 8 / MariaDB 10.6+.
- **SSH access + Composer** (strongly preferred).
- **Cron every minute** (`schedule:run`). Some cheap shared plans only allow every 5–15 min — then reminders/expiry still work, just a few minutes later; acceptable, but every minute is better.
- Ability to point the domain's **document root to `/public`**.
- Free SSL (Let's Encrypt).
- Daily backups, enough disk for product photos (5–20 GB is plenty to start).
- Email accounts on your domain (or a separate mail service).

### Three realistic options

| Option | Who it suits | Approx. cost* | Pros | Cons |
|---|---|---|---|---|
| **1. Serbian shared/"business" hosting** (e.g. mCloud, Orion Telekom hosting, Loopia RS, Hostinger with .rs) | You want simplest setup, support in Serbian, domain + mail + hosting in one bill, invoice for the company | ~5–15 €/month + .rs domain ~15–25 €/year | cPanel/DirectAdmin, mailboxes included, local support & invoices in RSD | Must check SSH/Composer/cron/PHP 8.2 per plan; less control |
| **2. Hostinger Business / Cloud Startup** | Cheap, Laravel-friendly, good panel | ~4–10 €/month (cheap first term, renewals higher) | SSH, Git, Composer, cron, free SSL, mailboxes, CDN | Support only in English; renewal price jumps |
| **3. VPS (Hetzner / DigitalOcean) + Ploi or Laravel Forge** | You want full control, best speed, room to grow | VPS ~5–8 €/month + Ploi/Forge ~8–12 €/month | Made for Laravel: push-to-deploy, cron, SSL, backups in clicks; very fast | You are "the sysadmin"; email must be a separate service |

\*Prices are rough ranges from memory for late 2026 — always compare the current price **and the renewal price** on the provider's site.

**My recommendation for ShopModa at launch:** **Option 1 or 2** — a business shared plan that explicitly lists **SSH + Composer + cron + PHP 8.2/8.3**, with the **.rs domain and mailboxes at the same provider**. It is the least that can go wrong for a first launch. Move to option 3 later if traffic grows. Before paying, ask support these 5 questions in writing:
1. Does the plan have SSH and Composer?
2. Can I run a cron job every minute?
3. Which PHP versions, and can I set the document root to a subfolder (`/public`)?
4. How are backups done and how long are they kept?
5. How many emails per hour can I send via your SMTP?

### Domain
- `.rs` (most trusted locally) — e.g. `shopmoda.rs`. Also consider grabbing `.com` / `.срб` to protect the name (optional).
- Registered via an RNIDS-accredited registrar (most Serbian hosts are). For a company, register it **on the company**, not on you personally.

### Email on your domain
Create mailboxes/aliases, e.g.:
- `porudzbine@shopmoda.rs` — receives new orders + daily report (set in Admin → Settings → order email).
- `podrska@shopmoda.rs` (or `kontakt@`) — contact form + replies to customers.
- `noreply@shopmoda.rs` or `prodavnica@shopmoda.rs` — the **From** address of the automatic mails.

**Sending the shop's automatic mails** (order confirmation codes, reminders, password codes): **do not use personal Gmail** — Gmail limits sending, the From address won't match the domain and mails land in spam. Two good choices:
- The hosting provider's SMTP of that mailbox (fine at low volume), or
- A transactional mail service (**Brevo** has a free tier ~300/day; Postmark, Mailgun, Amazon SES are paid but excellent deliverability). Recommended, because the order confirmation code mail is critical — if it lands in spam, orders expire.

Then set the DNS records the provider gives you: **SPF**, **DKIM**, **DMARC** (start with `p=none`). Test with mail-tester.com (aim for 9–10/10).

---

## 6. Step-by-step launch plan

### Step 1 — Prepare the code locally
1. `git init`, add a private GitHub repo, commit. (`.gitignore` already excludes `.env`, `vendor`, `node_modules`.)
2. Make sure `database/database.sqlite`, `storage/app/public/*` (your test uploads) and `.env` are **not** committed.
3. Create `database/seeders/ProductionSeeder.php` that only runs what a real shop needs:
   - `RoleSeeder` (roles),
   - one **admin user** with email/password read from env or typed in via `php artisan tinker` — **no fake customers**,
   - settings with **real** values (order email, contact email, phone) instead of the `*.test` ones in `SettingSeeder`,
   - optionally real categories/brands (the current `BrandSeeder`/`ProductSeeder`/`PromotionSeeder` use placeholder images — don't run them in production).
   - `OrderSeeder`, `CouponSeeder`, `UserLogSeeder`, the fake users from `UserSeeder` → **never** in production.
   Keep `DatabaseSeeder` as is for local development (`php artisan migrate:fresh --seed` still gives you test data locally).

### Step 2 — Buy hosting + domain, create mailboxes
As in §5. Enable SSL for the domain (usually one click, Let's Encrypt).

### Step 3 — Create the production database
In the hosting panel: new MySQL database + user with a strong password. Write them down in your password manager.

### Step 4 — Upload the code
- With SSH: `git clone` the repo into e.g. `~/shopmoda`, then point the domain's document root to `~/shopmoda/public`.
  (If the panel can't change document root: put the app outside `public_html` and make `public_html` a symlink to `shopmoda/public`.)
- `composer install --no-dev --optimize-autoloader`

### Step 5 — Production `.env` (on the server, never in git)
```
APP_NAME=ShopModa
APP_ENV=production
APP_DEBUG=false
APP_URL=https://shopmoda.rs
APP_TIMEZONE=Europe/Belgrade
APP_LOCALE=sr
APP_FALLBACK_LOCALE=sr
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=...        # from step 3
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync   # mails are sent directly, no worker needed

MAIL_MAILER=smtp
MAIL_HOST=...           # hosting SMTP or Brevo/Postmark
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="prodavnica@shopmoda.rs"
MAIL_FROM_NAME="ShopModa"
```
Then:
```
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Folder permissions: `storage/` and `bootstrap/cache/` writable by the web server.

### Step 6 — Cron
In the panel's "Cron jobs":
```
* * * * * cd ~/shopmoda && php artisan schedule:run >> /dev/null 2>&1
```
(Use the full PHP 8.x path the host tells you, e.g. `/usr/local/bin/php83`.)

### Step 7 — Domain, HTTPS, redirects
- Domain DNS → hosting (nameservers or A record).
- Force HTTPS, and choose **one** host: `shopmoda.rs` → redirect `www.shopmoda.rs` to it (or the other way round) — one canonical domain matters for SEO.

### Step 8 — Email DNS
SPF, DKIM, DMARC records; test with mail-tester.com; send yourself a password-reset code and an order confirmation to Gmail, Outlook and Yahoo — check they arrive in **Inbox**.

### Step 9 — Fill the shop with real content
Log in as admin → change the admin password → Settings (order email, contact email, phone) → categories, brands, models → products with real photos (compressed), Serbian descriptions, sizes and stock → maybe one launch promotion / coupon → announcement bar.

### Step 10 — Final checks before announcing
- Place a real test order as a guest and as a registered user: confirmation mail arrives, code works, admin sees it, stock decreases, daily report arrives next midnight.
- Let one test order expire (or run `php artisan orders:expire` / `orders:remind`) and check stock returns.
- Coupon, promotion, password reset, contact form.
- Mobile check on a real phone (iPhone + Android).
- 404 page, `/admin` blocked for non-admins, `APP_DEBUG=false` (visit a broken URL — no stack trace).
- PageSpeed Insights mobile; fix big images.
- Delete the test orders (Admin) so the analytics start clean; `page_views`/`user_logs` from testing can be truncated.

### Step 11 — After launch
- Google Search Console: verify domain (DNS TXT), submit `sitemap.xml`. Same for Bing.
- Backups: confirm the host's daily DB + files backup, and once a week download one copy yourself (or use `spatie/laravel-backup` to a cloud drive).
- Uptime monitor (UptimeRobot / Better Stack free plans) → alerts to your phone.
- Updates: `composer update` every month or two on a copy first; keep PHP version current.
- Deploying changes later: `git pull && composer install --no-dev -o && php artisan migrate --force && php artisan optimize` (put it in a small `deploy.sh`).

---

## 7. Decisions I need from you
1. **Latinica or ćirilica** for Serbian? (Recommendation: latinica.)
2. **English now or later?** (Recommendation: translation keys now, English file can follow.)
3. **Clean landing-page URLs** (`/patike/trcanje`, `/brend/nike`) — yes/no? (Recommendation: yes, before launch, so Google never indexes the old ones.)
4. **Admin panel language** — Serbian or English?
5. **Hosting option** 1, 2 or 3, and the domain name you want.
6. **Mail sending**: hosting SMTP or a transactional service (Brevo/Postmark)?
