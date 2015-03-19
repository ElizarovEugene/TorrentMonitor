<?php
$dir = dirname(__FILE__).'/../';
include_once $dir."class/Database.class.php";
include_once $dir."class/System.class.php";
if(!Sys::checkAuth())
    die(header('Location: '.Database::getSetting('serverAddress')));
include_once $dir.'engine.php';
?>
<div class="clear-both"></div>