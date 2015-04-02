<?php
$dir = str_replace('include', '', dirname(__FILE__));

include_once $dir."class/System.class.php";

if ( ! Sys::checkAuth())
  die(header('Location: ../'));

include_once $dir."class/Update.class.php";
include_once $dir."class/rain.tpl.class.php";

// заполнение шаблона
raintpl::configure("root_dir", $dir );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;
$tpl->assign( "title", 'Список изменений' );
$tpl->assign( "changelog", Update::getUpdateInfo() );

$tpl->draw( 'update' );

?>
