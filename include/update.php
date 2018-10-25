<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/Update.class.php";

if ( ! Sys::checkAuth())
  die(header('Location: ../'));

Update::main();  
?>
