<?php
// PHP 7.x compatible — no declare(strict_types), no mixed type hints

define('APP_CONFIG', array(
    'DB_HOST'            => 'sql100.infinityfree.com',
    'DB_PORT'            => '3306',
    'DB_DATABASE'        => 'if0_41473140_php_form_builder',
    'DB_USERNAME'        => 'if0_41473140',
    'DB_PASSWORD'        => 'WkB2XK5UkAFg',
    'APP_ENV'            => 'production',
    'APP_URL'            => 'https://priyeshsurti-phpformbuilder.infinityfree.me/php-form-builder',
    'JWT_SECRET'         => 'Priyesh@FormCraft2024#SecretKey!XyZ99',
    'JWT_EXPIRY'         => '3600',
    'JWT_REFRESH_EXPIRY' => '604800',
));

function env($key, $default = null)
{
    return isset(APP_CONFIG[$key]) ? APP_CONFIG[$key] : $default;
}
