<?php
$dir = dirname(__FILE__).'/../';
include_once $dir.'config.php';
include_once $dir.'class/System.class.php';
include_once $dir.'class/Database.class.php';
include_once $dir."class/Url.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

$date_today = date('d-m-Y');
$order = 'name';
$orderDir = 'asc';
if (isset($_COOKIE['order']) && isset($_COOKIE['orderDir']))
{
    if (in_array($_COOKIE['order'], ['name', 'date'])) {
        $order = $_COOKIE['order'];
    }
    if (in_array($_COOKIE['orderDir'], ['asc', 'desc'])) {
        $orderDir = $_COOKIE['orderDir'];
    }
}
$torrents_list = Database::getTorrentsList($order, $orderDir);
?>
<?php if (empty($torrents_list)) { ?>
    <div class="torrents-fs --empty"><div>Нет тем для мониторинга</div></div>
<?php } ?>

<?php if ( ! is_array($torrents_list)) { ?>
    <div class="torrents-fs --error"><div><?= $torrents_list ?></div></div>
<?php } else { ?>


<div class="top-bar">
    <div x-data="filterThemes" class="top-bar__search">
        <input type="text" @keyup="go()" x-model="filterValue" placeholder="Фильтр тем">
        <button class="btn btn--icon" x-show="filterValue != ''" @click="filterValue = ''; go()"><svg><use href="assets/img/sprite.svg#close" /></svg></button>
    </div>
    <div class="top-bar__sort">
        <button class="btn<?= $order == 'name' ? ' --active' : '' ?>" @click="setSort('name', '<?= $orderDir == 'asc' ? 'desc': 'asc' ?>')">
            По имени
            <svg class="<?= $orderDir == 'desc' ? 'flip-y' : '' ?>" <?= $order == 'date' ? 'hidden' : '' ?>><use href="assets/img/sprite.svg#arrow" /></svg>
        </button>
        <button class="btn<?= ($order == 'date') ? ' --active' : '' ?>" @click="setSort('date', '<?= $orderDir == 'asc' ? 'desc': 'asc' ?>')">
            По дате
            <svg class="<?= $orderDir == 'desc' ? 'flip-y' : '' ?>" <?= $order == 'name' ? 'hidden' : '' ?>><use href="assets/img/sprite.svg#arrow" /></svg>
        </button>
    </div>
</div>

<div id="filterable">
<?php
    foreach($torrents_list as $row)
    {
        extract($row);
        $link = Url::create($tracker, $name, $torrent_id, $hd);
        $has_info = (!empty($path) or ($type == 'RSS' && $ep) or $error or $closed) ? true : false;
    ?>


<div
    class="row tm-item sort"
    x-data="item"
    x-show="!deleted"
    x-transition:leave="tm-item--leaving"
    x-transition:leave-start="tm-item--leaving-start"
    x-transition:leave-end="tm-item--leaving-end"
    >

    <div class="col --auto">
        <div class="tracker-icon" style="background-image: url(img/<?= $tracker ?>.ico)" title="<?= $tracker ?>"></div>
    </div>

    <div class="col">

        <div class="tm-item__title">
            <?= $link['url'] ?>
        </div>

        <div x-cloak x-show="showInfo" x-transition>
            <?php if ($type == 'RSS' && $ep) { ?>
                <div class="tm-item__meta" title="Последняя скачаная серия"><svg><use href="assets/img/sprite.svg#ep" /></svg><?= $ep ?></div>
            <?php } ?>
            <?php if (! empty($path)) { ?>
                <div class="tm-item__meta" title="Путь сохранения"><svg><use href="assets/img/sprite.svg#save-to" /></svg><?= $path ?></div>
            <?php } ?>
            <?php if ($error) { ?>
                <div class="tm-item__meta c-danger"><svg><use href="assets/img/sprite.svg#errors-has" /></svg>Есть ошибки</div>
            <?php } ?>
            <?php if ($closed) { ?>
                <div class="tm-item__meta c-danger"><svg><use href="assets/img/sprite.svg#closed" /></svg>Тема закрыта</div>
            <?php } ?>
        </div>

    </div>

    <?php if ($link['quality']) { ?>
    <div class="col --auto">
        <div class="tm-item__quality"><?= $link['quality'] ?></div>
    </div>
    <?php } ?>

    <div class="col --12 d-block d-none:xl tm-item__spacer"></div>
    <div class="col --6 --1:xl tm-item__date">

        <?php
        if ($timestamp == '0000-00-00 00:00:00' || $timestamp == NULL || $timestamp == '2000-01-01 00:00:00'  || $timestamp == '1970-01-01 00:00:00') {}
        else
        {
            $date_update = $day.' '.Sys::dateNumToString($month).' '.$year.' '.$time;
            $date = $day.'-'.$month.'-'.$year;
            if (stripos($date, $date_today) !== FALSE)
                echo '<u>'.$date_update.'</u>';
            else
                echo $date_update;
            ?>
        <?php } ?>
    </div>

    <div class="col --6 --auto:xl tm-item__actions">
        <?php if ($pause) { ?>
            <div class="btn btn--icon tm-item__icon btn--success" title="Раздача на паузе"><svg><use href="assets/img/sprite.svg#pause" /></svg></div>
        <?php } ?>
        <?php if ($has_info) { ?>
            <button class="d-none:xl btn btn--icon tm-item__icon <?= ($error or $closed) ? 'btn--danger' : 'btn--primary' ?>" @click="showInfo = !showInfo" title="Показать дополнительную информацию"><svg><use href="assets/img/sprite.svg#info" /></svg></button>
            <div class="d-none d-block:xl btn btn--icon tm-item__icon <?= ($error or $closed) ? 'btn--danger' : 'btn--primary' ?>">
                <svg><use href="assets/img/sprite.svg#info" /></svg>

                <div class="tm-item__popup">
                    <?php if ($type == 'RSS' && $ep) { ?>
                        <div class="tm-item__meta" title="Последняя скачаная серия"><svg><use href="assets/img/sprite.svg#ep" /></svg><?= $ep ?></div>
                    <?php } ?>
                    <?php if (! empty($path)) { ?>
                        <div class="tm-item__meta" title="Путь сохранения"><svg><use href="assets/img/sprite.svg#save-to" /></svg><?= $path ?></div>
                    <?php } ?>
                    <?php if ($error) { ?>
                        <div class="tm-item__meta c-danger"><svg><use href="assets/img/sprite.svg#errors-has" /></svg>Есть ошибки</div>
                    <?php } ?>
                    <?php if ($closed) { ?>
                        <div class="tm-item__meta c-danger"><svg><use href="assets/img/sprite.svg#closed" /></svg>Тема закрыта</div>
                    <?php } ?>
                </div>

            </div>
        <?php } ?>
        <?php if ($link['quality']) { ?>
            <button @click="modalEditSeries(<?= $id ?>)" class="btn tm-item__icon tm-item__icon--edit"><svg><use href="assets/img/sprite.svg#edit" /></svg></button>
        <?php } else { ?>
            <button @click="modalEditTheme(<?= $id ?>)" class="btn tm-item__icon tm-item__icon--edit"><svg><use href="assets/img/sprite.svg#edit" /></svg></button>
        <?php } ?>

        <div class="confirm__wrap" title="Удалить тему">
            <button class="btn tm-item__icon tm-item__icon--delete" @click="showDelete = true"><svg><use href="assets/img/sprite.svg#trash" /></svg></button>

            <div class="confirm confirm--danger"
                x-cloak
                x-show="showDelete"
                @click.away="showDelete = false"
                >
                <div class="confirm__title">Правда удалить?</div>
                <div class="confirm__actions">
                    <button class="btn btn--secondary" @click="deleteItem(<?= $id ?>)">ОК</button>
                </div>
                <button class="btn-unset confirm__icon" @click.prevent="showDelete = false" title="Отмена"><svg><use href="assets/img/sprite.svg#close" /></svg></button>
            </div>
        </div>
    </div>

</div>


<?php } ?>
</div>
<?php } ?>
