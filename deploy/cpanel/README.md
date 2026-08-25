# cPanel deployment — SaaS LeadFlow

This app can run on **cPanel with SSH + PHP 8.3**, but it is a compromise vs Docker/VM.
Shared hosts without SSH, Postgres (`pdo_pgsql`), or cron will **not** run outreach queues / Graph webhooks correctly.

Use the **`cpanel` branch** on GitHub for production cPanel deployments.

---

## Deploy from GitHub (recommended)

### Your workflow

```text
main (development)  →  merge to cpanel  →  cPanel pulls / deploys cpanel branch
```

1. Develop on `main` (or feature branches).
2. When ready for production, merge into `cpanel`:

```bash
git checkout cpanel
git merge main
git push origin cpanel
```

3. On cPanel, **Pull** or **Deploy HEAD Commit** (see below).

### One-time cPanel setup (Git Version Control)

1. cPanel → **Git Version Control** → **Create**
2. Clone URL:

```text
https://github.com/TanmoyDhar-Dev/lead-saas.git
```

3. **Repository Path:** e.g. `/home/USER/lead-saas` (outside `public_html` is fine)
4. **Branch:** `cpanel` (not `main`)
5. Click **Create**

**Point the site at Laravel:**

- **Option A (recommended):** cPanel → **Domains** → document root → `/home/USER/lead-saas/public`
- **Option B:** Clone into `public_html`; root `.htaccess` sends traffic to `public/`

**Create `.env` on the server only** (never commit):

```bash
cd ~/lead-saas
cp .env.cpanel.example .env
nano .env   # set APP_URL, DB, Azure, n8n, etc.
php artisan key:generate
chmod -R 775 storage bootstrap/cache
```

First deploy (SSH or Terminal):

```bash
cd ~/lead-saas
composer install --no-dev --optimize-autoloader
sh deploy/cpanel/post-deploy.sh
```

Add cron jobs (see Step 6 below).

### Every update from GitHub

**Option 1 — cPanel UI**

1. Git Version Control → **Manage** your repo
2. **Update from Remote** (pull latest `cpanel`)
3. **Deploy HEAD Commit** — runs `.cpanel.yml` (composer + migrate + cache)

**Option 2 — SSH**

```bash
cd ~/lead-saas
git pull origin cpanel
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Or:

```bash
sh deploy/cpanel/post-deploy.sh
```

### What is in Git vs built on server

| Item | In Git (`cpanel` branch) | On server after deploy |
|---|---|---|
| Source code | Yes | Pulled from GitHub |
| `public/build` (CSS/JS) | Yes (committed) | From Git |
| `vendor/` | No (gitignored) | `composer install` |
| `.env` | No (gitignored) | You create once on server |
| `node_modules/` | No | Not needed on server |

After changing frontend (Vite), on your PC:

```bash
npm run build
git add public/build
git commit -m "Build assets for cPanel"
git push origin cpanel
```

---

## Manual zip upload (alternative)

If Git is not available on your host, use the zip method below.

## Requirements (check in cPanel first)

- PHP **8.3+** (MultiPHP Manager)
- Extensions: `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`
- Database: **`pdo_pgsql`** if using Supabase, or MySQL/`pdo_mysql` if using cPanel MySQL
- Cron Jobs enabled
- SSH Terminal (recommended) or Terminal in cPanel
- Document root can stay as project root (root `.htaccess` routes to `public/`)

## Step 1 — Build package on your PC

From the project root (Windows):

```powershell
powershell -ExecutionPolicy Bypass -File deploy/cpanel/build-package.ps1
```

Zip output:

`deploy/cpanel/dist/saas-leadflow-cpanel.zip`

## Step 2 — Upload to cPanel

1. Open **File Manager**
2. Go to `public_html` (or a subdomain folder)
3. Upload `saas-leadflow-cpanel.zip`
4. Extract it
5. If files landed in a subfolder, move contents so you see `artisan`, `public/`, `.htaccess` in the web root

Preferred layout:

```text
/home/USER/public_html/   (or /home/USER/saas-leadflow/)
  app/
  bootstrap/
  public/
  artisan
  .htaccess
  .env          ← create this (not in zip)
  vendor/
```

Optional better setup: put the app outside `public_html`, then set the domain document root to `.../public` in cPanel Domains. If you do that, you can skip the root `.htaccess` rewrite.

## Step 3 — Create `.env`

1. Copy `.env.cpanel.example` → `.env`
2. Fill secrets (DB, Azure, n8n, admin)
3. Set:

```env
APP_URL=https://your-domain.com
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```

4. Generate `APP_KEY` (SSH):

```bash
cd ~/public_html   # or your app path
php artisan key:generate
```

## Step 4 — Permissions

In File Manager (or SSH):

```bash
chmod -R 775 storage bootstrap/cache
```

## Step 5 — Finish install (SSH)

```bash
cd ~/public_html
php artisan storage:link --force
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Or:

```bash
sh deploy/cpanel/post-deploy.sh
```

Login uses the seeder admin from `.env` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`), or your existing Supabase users if that DB already has data.

## Step 6 — Cron Jobs (required)

cPanel → **Cron Jobs** → every minute:

```cron
* * * * * cd /home/USER/public_html && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

```cron
* * * * * cd /home/USER/public_html && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=50 --tries=3 >> /dev/null 2>&1
```

Replace `/home/USER/public_html` and `/usr/local/bin/php` with your real paths (`which php` in Terminal).

Without these crons:

- Bulk reply jobs will not process
- Inbound Graph email jobs will not process
- Graph subscription renewal will not run

## Step 7 — Azure / Graph

In Azure App Registration, add:

- `https://your-domain.com/auth/microsoft/callback`

And ensure:

```env
AZURE_REDIRECT_URI="${APP_URL}/auth/microsoft/callback"
GRAPH_WEBHOOK_URL="${APP_URL}/webhooks/graph/notifications"
```

Graph webhooks require a real public **HTTPS** domain (not `localhost`).

## Step 8 — Verify

Open:

- `https://your-domain.com/up` → should return OK
- `https://your-domain.com/login` → login page

## What works / what is weaker on cPanel

| Feature | On cPanel |
|---|---|
| Login, UI, lead import | Yes (if PHP extensions OK) |
| Outlook connect | Yes (HTTPS + Azure URI) |
| Queue (bulk reply / inbound) | Yes only with cron worker |
| Graph subscription renew | Yes only with schedule cron |
| Performance under load | Weaker than Docker/VM |
| Redis | Not used (database drivers) |

## Updates later

**GitHub:** merge to `cpanel`, push, then Pull + Deploy on cPanel (or `git pull` + post-deploy script).

**Zip:** rebuild zip, upload, then:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## If something fails

- **500 error:** check `storage/logs/laravel.log` and PHP version 8.3  
- **Blank page:** enable temporary `APP_DEBUG=true`, fix, then turn off  
- **DB connection:** confirm `pdo_pgsql` exists (`php -m | grep pgsql`)  
- **Assets missing:** rebuild with `build-package.ps1` so `public/build` is included  
- **Jobs stuck:** confirm both cron jobs are running
