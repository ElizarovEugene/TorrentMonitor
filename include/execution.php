<?php
$dir = dirname(__FILE__).'/../';
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));
?>
<div class="top-bar mb-2">
    <div class="top-bar__title"><svg><use href="assets/img/sprite.svg#play" /></svg> Запуск</div>
</div>
<div class="check">
<?php include_once $dir.'engine.php'; ?>
</div>
