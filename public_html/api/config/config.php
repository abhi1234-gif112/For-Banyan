<?php
// ============================================================
// NAZAR — Configuration File
// Edit this file with your credentials after uploading to Hostinger
// ============================================================

// --- DATABASE (get these from Hostinger cPanel → MySQL Databases) ---
define('DB_HOST', 'localhost');          // Always localhost on Hostinger
define('DB_NAME', 'your_db_name');      // e.g. u123456789_nazar
define('DB_USER', 'your_db_user');      // e.g. u123456789_nazar
define('DB_PASS', 'your_db_password');  // The password you set in cPanel

// --- YOUR WEBSITE URL (no trailing slash) ---
define('APP_URL', 'https://yoursite.hostingersite.com');

// --- ANTHROPIC CLAUDE API (get from console.anthropic.com) ---
define('ANTHROPIC_API_KEY', 'sk-ant-your-key-here');

// --- TWITTER / X API (get from developer.twitter.com) ---
define('TWITTER_BEARER_TOKEN', 'your-bearer-token');

// --- YOUTUBE API (get from console.cloud.google.com) ---
define('YOUTUBE_API_KEY', 'your-youtube-api-key');

// --- META API (get from developers.facebook.com) ---
define('META_ACCESS_TOKEN', 'your-meta-access-token');
define('META_IG_USER_ID', 'your-instagram-business-account-id');

// --- TELEGRAM BOT (get from t.me/BotFather on Telegram) ---
define('TELEGRAM_BOT_TOKEN', 'your-telegram-bot-token');

// --- SECURITY ---
define('JWT_SECRET', 'change-this-to-a-random-64-character-string-right-now');
define('CRON_SECRET', 'change-this-to-another-random-string-for-cron-security');

// --- OPTIONAL: EMAIL REPORTS (leave blank to disable) ---
define('REPORT_EMAIL', '');             // e.g. abhi@saptanga.com
define('SENDGRID_API_KEY', '');

// ============================================================
// DO NOT EDIT BELOW THIS LINE
// ============================================================
define('BASE_PATH', dirname(__DIR__, 1));
define('REPORTS_PATH', BASE_PATH . '/reports/files/');
define('VENDOR_PATH', dirname(__DIR__, 3) . '/vendor/');
