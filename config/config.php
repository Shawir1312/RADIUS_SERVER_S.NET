<?php
/**
 * S.NET RADIUS Hotspot Management
 * Application Configuration Constants
 */

define('APP_NAME',     'S.NET RADIUS Manager');
define('APP_VERSION',  '1.0.0');
define('APP_COMPANY',  'PT Network Inovation Solutions');
define('APP_URL',      '');   // e.g. https://radius.snet.id — leave empty for relative

// Session settings
define('SESSION_LIFETIME', 7200);  // 2 hours
define('SESSION_NAME',     'SNET_RADIUS');

// RADIUS CoA settings (RFC 3576)
define('COA_PORT',    3799);
define('COA_TIMEOUT', 5);     // seconds

// Voucher generation
define('VOUCHER_MAX_BATCH', 2000);
define('VOUCHER_RETRY',     10);   // retry attempts for unique username collision

// Paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', __DIR__);
define('ASSETS_PATH', BASE_PATH . '/assets');
define('LIB_PATH',    BASE_PATH . '/lib');
define('LOG_PATH',    BASE_PATH . '/logs');

// Timezone
define('APP_TIMEZONE', 'Asia/Jayapura');  // WIT — change as needed
date_default_timezone_set(APP_TIMEZONE);

// Pagination
define('PER_PAGE', 25);

// Color theme (for CSS variables)
define('COLOR_PRIMARY', '#1565C0');  // Blue
define('COLOR_ACCENT',  '#C62828');  // Red
define('COLOR_WHITE',   '#FFFFFF');
