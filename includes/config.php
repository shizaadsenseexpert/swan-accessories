<?php
// Core site configuration. Edit these values to customize the store.

define('SITE_NAME', 'Swan Accessories');
define('SITE_TAGLINE', 'Timeless pieces, everyday grace.');
define('SITE_CURRENCY_SYMBOL', 'Rs. ');

// Contact & social links — shown in the footer and Contact Us page.
define('SITE_PHONE_DISPLAY', '0342-4533765');
define('SITE_WHATSAPP_NUMBER', '923424533765'); // country code + number, no + or leading 0, used for wa.me links
define('SITE_EMAIL', 'muhammadqandeel854@gmail.com');
define('SITE_FACEBOOK_URL', ''); // add your Facebook page URL here when ready
define('SITE_INSTAGRAM_URL', 'https://www.instagram.com/swanaccessories.pk/');

// Change this immediately after first login: admin/login.php
define('ADMIN_USERNAME', 'admin');
// Default password: SwanAdmin123  (change via ADMIN_PASSWORD_HASH below)
define('ADMIN_PASSWORD_HASH', '$2y$10$ymbBtYEh8J9Dtr5K1lfuheBlTME.ptd2DDOFsTYYSgr/Y8Hdtf7ke');

define('BASE_PATH', dirname(__DIR__));
define('DB_PATH', BASE_PATH . '/data/store.sqlite');

session_start();
