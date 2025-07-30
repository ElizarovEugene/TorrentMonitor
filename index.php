<?php
// Setup libxml for PHP version compatibility
// libxml_disable_entity_loader() was deprecated in PHP 8.0
if (PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
    libxml_disable_entity_loader(false);
}

$dir = dirname(__FILE__)."/";
include_once $dir."config.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/System.class.php";

if (Sys::checkAuth())
    require_once "pages/main.php";
else
    require_once "pages/auth.php";
?>