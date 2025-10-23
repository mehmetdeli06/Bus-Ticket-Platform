<?php
declare(strict_types=1);

define('APP_DEBUG', true); 

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

date_default_timezone_set('Europe/Istanbul');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

define('BASE_PATH', dirname(__DIR__, 2));

define('DB_PATH', BASE_PATH . '/storage/app.db');

const ROLE_USER       = 'user';
const ROLE_COMPANY    = 'company';
const ROLE_ADMIN      = 'admin';
const ROLE_FIRM_ADMIN = ROLE_COMPANY;
