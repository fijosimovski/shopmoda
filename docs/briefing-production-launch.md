# ShopModa — Production Launch Briefing

_Written 25.11.2026, after the AJAX cart page, error pages, Serbian translation and SEO work. Replaces the "what is left" parts of `briefing-seo-language-hosting.md`._

---

## 1. Where the project stands

| Area | State |
|---|---|
| Shop features | Done: catalog, filters, product page, promotions (4 types), coupons, session cart with AJAX drawer **and AJAX cart page**, checkout, email-code confirmation, reminders/expiry, accounts, contact. |
| Admin | Done: dashboard, analytics, visits, orders + export, catalog CRUD, promotions, coupons, announcements, users, logs, messages, settings. |
| Language | **Done.** The shop, emails and shop JS are Serbian Latin (validation via `lang/sr`); the admin panel is English (`AdminLocale` middleware). Database content (seeded demo products/promotions) is still English – it gets replaced by real content anyway. |
| Error pages | **Done.** 401/403/404/405/410/419/429 in the shop layout, 500/503 standalone (work without the database). |
| SEO | **Done (block A of the old briefing).** Titles/descriptions, canonical, noindex rules, `/robots.txt` + `/sitemap.xml` (routes), Open Graph/Twitter with logo-based image, JSON-LD (Organization, WebSite, Product, Breadcrumb), favicons + manifest, alt/lazy/sizes on images. |
| Documentation | **Done.** `docs/` → full technical, brief technical, admin guide (Serbian) as .docx. |
| Tests | 13 feature tests pass (`php artisan test`). |
| Git | **Not a git repository yet.** |

---

## 2. What is still open before going live

### Must do (blocking)
1. **Git** – `git init`, private GitHub repo, first commit. (Check `.env`, `database/database.sqlite`, `storage/app/public/*` and the `shopmoda` file in the project root are not committed.)
2. **Legal pages** (Serbian law on e-commerce and consumer protection): *O nama / Kontakt* with company name, address, PIB, matični broj, phone, email; *Uslovi kupovine*; *Pravo na odustanak (14 dana)* + withdrawal form; *Reklamacije*; *Politika privatnosti*; *Dostava* (price and time). The texts come from you / your accountant; adding the pages to the site is ~½ day (routes + views + footer links + sitemap).
3. **Production seeder** – a `ProductionSeeder` that creates only roles, one admin and real settings (no fake users, orders, coupons, logs). ~1 hour.
4. **Real content** – real brands, models, categories, products with Serbian descriptions and compressed photos; delete the demo data.
5. **Mail on your domain** – stop using personal Gmail for sending (see §4).
6. **Business side** – registered business (preduzetnik/DOO, activity 4791), courier contract for cash on delivery, ask the accountant about fiscalization of COD orders.

### Strongly recommended (not blocking)
7. Public contact page (today the contact form needs login; the footer shows phone/email from Settings).
8. Clean landing URLs (`/patike/trcanje`, `/brend/nike`, `/muske-patike`) with their own intro text – biggest SEO gain after launch.
9. Image pipeline on upload (resize + WebP) – today photos are stored as uploaded, so compress them before uploading.
10. 301 redirect when a product slug changes.
11. Cookie notice (only a short info note while you use session cookies only; a real consent banner the moment you add Google Analytics / Meta Pixel).

### Small code-review notes I noticed
- A few pre-existing inline `onclick`/`style=""` attributes remain in views (product thumbnails, sort selects, admin tables). They work; move them to JS/CSS during the review if you want to follow your CSS rule strictly.
- Artisan console output (`orders:expire`, etc.) is still English – only visible in the terminal/cron log.
- Analytics uses MySQL-only SQL – production must be MySQL/MariaDB, not SQLite.
- `resources/views/welcome.blade.php` is unused and can be deleted.

---

## 3. Hosting — what to buy

### What the app needs
- PHP **8.2 or 8.3** with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip` (Excel export), `gd` (future image resizing).
- MySQL 8 / MariaDB 10.6+.
- **SSH + Composer**, **cron every minute**, document root pointing to `/public`, free SSL (Let's Encrypt), daily backups, 5–20 GB disk.

### Recommendation
**Business shared hosting with SSH**, domain **.rs** and mailboxes at the same provider (Serbian provider for local support and invoices in RSD, or Hostinger Business/Cloud Startup for the price). Move to a VPS (Hetzner + Ploi/Forge) only if traffic grows.

Ask support in writing before paying:
1. SSH and Composer included?
2. Cron every minute allowed?
3. PHP 8.3 available, and can the document root be set to a subfolder (`/public`)?
4. How are backups made and how long are they kept?
5. How many emails per hour can I send through your SMTP?

Prices change — compare the **renewal** price, not just the first-year offer.

### Domain
`shopmoda.rs` (registered on the company, through an RNIDS-accredited registrar). Pick **one** canonical host (with or without `www`) and redirect the other.

---

## 4. Email setup
- Mailboxes: `porudzbine@` (order email + daily report), `podrska@` / `kontakt@` (contact form), `prodavnica@` or `noreply@` (From address of automatic mails).
- Sending: hosting SMTP is fine at low volume; a transactional service (Brevo free tier, Postmark, Mailgun, SES) gives better inbox delivery. The confirmation-code mail is critical – if it lands in spam, orders expire.
- DNS: **SPF, DKIM, DMARC** (`p=none` to start). Test at mail-tester.com (aim 9–10/10) and send test orders to Gmail, Outlook and Yahoo.

---

## 5. Go-live steps (in order)

1. Git repo + push.
2. Buy hosting + domain, enable SSL, create mailboxes.
3. Create the MySQL database and user.
4. On the server: `git clone` → document root to `public/` → `composer install --no-dev --optimize-autoloader`.
5. Production `.env`:
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
   DB_HOST=... DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...
   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   MAIL_MAILER=smtp
   MAIL_HOST=... MAIL_PORT=587 MAIL_USERNAME=... MAIL_PASSWORD=...
   MAIL_FROM_ADDRESS="prodavnica@shopmoda.rs"
   MAIL_FROM_NAME="ShopModa"
   ```
6. `php artisan key:generate` (new key, never the local one) → `php artisan migrate --force` → `php artisan db:seed --class=ProductionSeeder --force` → `php artisan storage:link` → `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
7. Permissions: `storage/` and `bootstrap/cache/` writable.
8. Cron: `* * * * * cd ~/shopmoda && php artisan schedule:run >> /dev/null 2>&1` (use the host's full PHP 8.x path).
9. DNS for mail (SPF/DKIM/DMARC), force HTTPS, `www` redirect.
10. Admin → change password → Settings → real catalog.
11. **Final checks:** guest + registered test order (code mail, confirmation, admin notice, stock decrease); let one expire (`php artisan orders:expire`); coupon; promotion; password reset; contact; 404 page and no stack trace on errors; `/admin` blocked for customers; `/robots.txt` shows the real rules and the sitemap URL (it says `Disallow: /` everywhere except `APP_ENV=production`); mobile check on iPhone + Android; PageSpeed Insights.
12. Delete test orders, truncate `page_views` / `user_logs` from testing.
13. After launch: Google Search Console + Bing (submit `https://shopmoda.rs/sitemap.xml`), test link previews in the Facebook Sharing Debugger, uptime monitor, weekly backup download, monthly `composer update` on a copy first.

---

## 6. Decisions I need from you
1. Hosting option and the exact domain.
2. Mail sending: hosting SMTP or a transactional service?
3. Should I add the legal pages and the public contact page now (you provide the texts / company data)?
4. Should I write the `ProductionSeeder`?
5. Clean landing URLs before launch (recommended) or after?
