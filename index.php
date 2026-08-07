<?php
// Setup libxml for PHP version compatibility
// libxml_disable_entity_loader() was deprecated in PHP 8.0
if (PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
    libxml_disable_entity_loader(true);
}

$dir = dirname(__FILE__)."/";
include_once $dir."config.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/System.class.php";

if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

if (empty($_SESSION['csrf_token']))
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if (Sys::checkAuth())
    require_once "pages/main.php";
else
    require_once "pages/auth.php";
?>