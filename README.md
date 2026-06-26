# NAZAR — Political Intelligence Platform

**NAZAR** is a full-stack political intelligence and digital monitoring platform built for Indian politicians and their teams. It collects mentions across Twitter, YouTube, Facebook, Instagram, Telegram, Reddit and news RSS, enriches them with AI sentiment analysis via Claude, detects threats, and enables rapid-response content generation.

---

## Quick Deploy to Hostinger Shared Hosting

### Prerequisites
- Hostinger Shared Hosting plan (Business or higher — need cron jobs)
- PHP 8.1 enabled
- MySQL 8.0 database created in hPanel
- Domain pointed to `public_html/`

### Step 1 — Upload files

1. Download this repo as a ZIP from GitHub
2. In hPanel → **Website** → **Migrate Website** (or File Manager)
3. Upload and extract so your directory looks like:
   ```
   public_html/
     index.html
     assets/
     api/
     cron/
     reports/
     .htaccess
   vendor/          ← one level above public_html
   database/
   ```

### Step 2 — Create database

1. hPanel → **Databases** → **MySQL Databases** → create `nazar_db`
2. Create a DB user, add it to the database with all privileges
3. hPanel → **phpMyAdmin** → select `nazar_db` → **Import** → upload `database/schema.sql`

### Step 3 — Edit config

Edit `public_html/api/config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

define('APP_URL',          'https://yourdomain.com');
define('ANTHROPIC_API_KEY','sk-ant-...');
define('JWT_SECRET',       'change-this-to-a-random-string-50-chars');
define('CRON_SECRET',      'another-random-string');

// Optional social API keys
define('TWITTER_BEARER_TOKEN', '');
define('YOUTUBE_API_KEY',      '');
define('META_ACCESS_TOKEN',    '');
```

### Step 4 — Set up cron jobs

In hPanel → **Advanced** → **Cron Jobs**, add (replace values):

```
*/15 * * * *  curl -s "https://yourdomain.com/cron/collect_rss_news.php?cron_secret=YOUR_CRON_SECRET"
*/15 * * * *  curl -s "https://yourdomain.com/cron/collect_twitter.php?cron_secret=YOUR_CRON_SECRET"
*/30 * * * *  curl -s "https://yourdomain.com/cron/collect_youtube.php?cron_secret=YOUR_CRON_SECRET"
*/30 * * * *  curl -s "https://yourdomain.com/cron/collect_facebook.php?cron_secret=YOUR_CRON_SECRET"
*/30 * * * *  curl -s "https://yourdomain.com/cron/collect_instagram.php?cron_secret=YOUR_CRON_SECRET"
*/5  * * * *  curl -s "https://yourdomain.com/cron/collect_telegram.php?cron_secret=YOUR_CRON_SECRET"
*/20 * * * *  curl -s "https://yourdomain.com/cron/collect_reddit.php?cron_secret=YOUR_CRON_SECRET"
*/10 * * * *  curl -s "https://yourdomain.com/cron/enrich_mentions.php?cron_secret=YOUR_CRON_SECRET"
*/5  * * * *  curl -s "https://yourdomain.com/cron/detect_alerts.php?cron_secret=YOUR_CRON_SECRET"
0    * * * *   curl -s "https://yourdomain.com/cron/score_individuals.php?cron_secret=YOUR_CRON_SECRET"
0    7 * * *   curl -s "https://yourdomain.com/cron/daily_report.php?cron_secret=YOUR_CRON_SECRET"
```

### Step 5 — Log in

Visit `https://yourdomain.com` and log in with:

| Email | Password | Role |
|-------|----------|------|
| admin@saptanga.in | Saptanga@2024 | Super Admin |
| analyst1@saptanga.in | Saptanga@2024 | Analyst |
| viewer1@saptanga.in | Saptanga@2024 | Viewer |

---

## Architecture

```
For-Banyan/
├── database/
│   └── schema.sql          ← Run once in phpMyAdmin
├── public_html/            ← Hostinger public_html
│   ├── index.html          ← React SPA entry (pre-built)
│   ├── assets/             ← Compiled JS/CSS chunks
│   ├── .htaccess           ← SPA routing + API routing + security
│   ├── api/
│   │   ├── config/         ← config.php, db.php, auth.php, helpers.php, cors.php
│   │   ├── auth/           ← login, logout, me
│   │   ├── mentions/       ← list, stats, upload
│   │   ├── alerts/         ← list, update
│   │   ├── actions/        ← trigger, list
│   │   ├── keywords/       ← list, manage
│   │   ├── clients/        ← list, manage
│   │   ├── individuals/    ← list, profile
│   │   ├── reports/        ← generate, list
│   │   ├── settings/       ← users, cron_status, change_password
│   │   └── health.php
│   ├── cron/               ← Data collection + AI enrichment scripts
│   └── reports/files/      ← Generated PDFs (auto-created, writable)
└── vendor/                 ← PHP dependencies (committed, no Composer needed on server)
    ├── firebase/php-jwt
    └── mpdf/mpdf
```

## AI Actions

NAZAR can generate 10 types of AI content via the Claude API:

| Action | Description |
|--------|-------------|
| Counter Brief | Factual rebuttal to opposition attacks |
| Rapid Response | 5 tweet/WhatsApp ready responses |
| Press Kit | Full press release + talking points |
| Outreach Message | Personalised message for an individual |
| WhatsApp Forward | Viral forward in Hindi + English |
| Keyword Blocking List | Words to mute/block on all platforms |
| Narrative Brief | Strategic narrative document |
| Individual Strategy | Per-person engagement strategy |
| Daily Report | Automated morning intelligence brief |
| Weekly Digest | Weekly political intelligence summary |

## Roles

| Role | Access |
|------|--------|
| viewer | Read-only: Dashboard, Mentions, Individuals, Alerts |
| analyst | All of above + upload mentions, trigger actions, manage keywords |
| super_admin | Full access + client management, user management, settings |

---

Built by Saptanga Labs LLP · NAZAR v2.0
