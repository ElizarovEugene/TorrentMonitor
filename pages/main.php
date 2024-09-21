<?php include_once "header.php" ?>

<?php
//Проверка обновления
$dir = __DIR__.'/';
include_once $dir.'../class/System.class.php';
$update = Sys::checkUpdate();
$version = Sys::version();

if ($version['system'] == $version['database'] or $version['system'] < $version['database']) {
    $version = $version['system'];
} else {
    $version = $version['database'];
}

$errors = Database::getWarningsCountSimple();
$errors_count = 0;
if (! empty($errors)) {
    if ($errors['count'] > 0) {
        $errors_count = $errors['count'];
    }
}

$news = Database::getNewsCount();
$news_count = 0;
if (! empty($news)) {
    if ($news['count'] > 0) {
        $news_count = $news['count'];
    }
}

$lastStart = @file_get_contents(dirname(__FILE__).'/../laststart.txt');
if (! empty($lastStart)) {
    $date = explode('-', $lastStart);
    $lastStartTime =  $date[0].' '.Sys::dateNumToString($date[1]).' '.$date[2];
} else {
    $lastStartTime =  'Ещё не производилась.';
}
?>


<div x-cloak x-data="tm(<?= $errors_count ?>, <?= $news_count ?>)">

<div class="container-fluid">

    <div class="row header-mobile d-none:md">
        <div class="col">
            <div class="logo">
                <svg class="d-none d-block:sm"><use href="assets/img/sprite.svg#logo" /></svg>
                <svg class="d-block d-none:sm"><use href="assets/img/sprite.svg#logo-xs" /></svg>
                <div class="logo-version"><?= $version ?></div>
            </div>
            <button x-cloak class="header-mobile__btn" @click="showTopNav = !showTopNav">
                <svg x-show="!showTopNav"><use href="assets/img/sprite.svg#menu" /></svg>
                <svg x-show="showTopNav"><use href="assets/img/sprite.svg#close" /></svg>
            </button>
        </div>

        <div x-show="showTopNav" class="header-mobile__nav" x-collapse>
            <?php include '_navigation.php' ?>
        </div>
    </div>

    <div class="row">

        <aside class="sidebar col --4:md --3:lg d-none d-block:md">
            <div class="logo">
                <svg><use href="assets/img/sprite.svg#logo" /></svg>
                <div class="logo-version"><?= $version ?></div>
            </div>
            <?php if ($update) { ?>
            <div class="tm-update">
                Доступна новая версия TorrentMonitor. <br>Пожалуйста, <button type="button" class="btn btn--text" @click="showPage('update')">обновитесь</button>
            </div>
            <?php } ?>
            <?php include '_navigation.php' ?>

        </aside>


        <main class="main col">
            <div class="loader" x-show="pageLoading">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                    <circle cx="50" cy="50" r="40" stroke="var(--c-primary)" fill="none" stroke-width="20" stroke-linecap="round"></circle>
                    <circle cx="50" cy="50" r="40" stroke="var(--c-bg)" fill="none" stroke-width="10" stroke-linecap="round">
                        <animate attributeName="stroke-dashoffset" dur="2s" repeatCount="indefinite" from="0" to="502"></animate>
                        <animate attributeName="stroke-dasharray" dur="2s" repeatCount="indefinite" values="225.9 25.099999999999994;1 250;225.9 25.099999999999994"></animate>
                    </circle>
                </svg>
            </div>
            <div class="row">
                <div x-html="pageContents"></div>
            </div>
        </main>

    </div>
</div>

<?php include_once "_modal-add.php" ?>
<?php include_once "_modal-edit.php" ?>

</div>
<?php include_once "footer.php" ?>
