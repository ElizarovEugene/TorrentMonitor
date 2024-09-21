<?php
libxml_disable_entity_loader(false);
	
$dir = dirname(__FILE__)."/";
include_once $dir."config.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/System.class.php";

if (Sys::checkAuth())
    require_once "pages/main.php";
else
    require_once "pages/auth.php";
?>