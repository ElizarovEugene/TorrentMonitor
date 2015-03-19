<?php
$dir = dirname(__FILE__).'/../';
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));
include_once $dir.'engine.php';
?>
<div class="clear-both"></div>