<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));


$user_id = !empty($_GET['id']) ? $_GET['id'] : false ;
$themes = Database::getThremesFromBuffer($user_id);

if (is_array($themes) && count($themes) > 0) {
    foreach ($themes as $item) { ?>
    <div
        class="row tm-item"
        x-data="userItem"
        x-show="!deleted"
        x-transition:leave="tm-item--leaving"
        x-transition:leave-start="tm-item--leaving-start"
        x-transition:leave-end="tm-item--leaving-end"
        >

        <div class="col --auto">
            <div class="tracker-icon" style="background-image: url(img/<?= $item['tracker'] ?>.ico)" title="<?= $item['tracker'] ?>"></div>
        </div>
        <div class="col">
            <div class="tm-item__section">
                <?= $item['section'] ?>
            </div>
            <div class="tm-item__title">
                <a href="<?= 'http://'.$item['tracker'].'/forum/viewtopic.php?t='.$item['threme_id'] ?>" target="_blank"><?= $item['threme']?></a>
            </div>
        </div>

        <div class="col --12 d-block d-none:xl tm-item__spacer"></div>

        <div class="col --6 --1:xl tm-item__date">
            <?php
            $arr = preg_split('/-/', $item['timestamp']);
            $date = $arr[2].' '.Sys::dateNumToString($arr[1]).' '.$arr[0];
            echo $date;
            ?>
        </div>

        <div class="col --6 --auto:xl tm-item__actions">
            <div class="confirm__wrap" title="Скачать тему">
                <button class="btn tm-item__icon tm-item__icon--edit" @click="showDownload = true"><svg><use href="assets/img/sprite.svg#down" /></svg></button>

                <div class="confirm confirm--action"
                    x-cloak
                    x-show="showDownload"
                    @click.away="showDownload = false"
                    >
                    <div class="confirm__title">Скачать тему?</div>
                    <div class="confirm__actions">
                        <button class="btn btn--primary" @click="downloadItem(<?= $item['id'] ?>, <?= $user_id ?>)">ОК</button>
                    </div>
                    <button class="btn-unset confirm__icon" @click.prevent="showDownload = false" title="Отмена"><svg><use href="assets/img/sprite.svg#close" /></svg></button>
                </div>
            </div>

            <div class="confirm__wrap" title="Добавить тему в мониторинг">
                <button class="btn tm-item__icon tm-item__icon--edit" @click="showMonitor = true"><svg><use href="assets/img/sprite.svg#plus" /></svg></button>

                <div class="confirm confirm--action"
                    x-cloak
                    x-show="showMonitor"
                    @click.away="showMonitor = false"
                    >
                    <div class="confirm__title">Добавить тему в мониторинг?</div>
                    <div class="confirm__actions">
                        <button class="btn btn--primary" @click="monitorItem(<?= $item['id'] ?>)">ОК</button>
                    </div>
                    <button class="btn-unset confirm__icon" @click.prevent="showMonitor = false" title="Отмена"><svg><use href="assets/img/sprite.svg#close" /></svg></button>
                </div>
            </div>

            <div class="confirm__wrap" title="Удалить тему">
                <button class="btn tm-item__icon tm-item__icon--delete" @click="showDelete = true"><svg><use href="assets/img/sprite.svg#trash" /></svg></button>

                <div class="confirm confirm--danger"
                    x-cloak
                    x-show="showDelete"
                    @click.away="showDelete = false"
                    >
                    <div class="confirm__title">Правда удалить?</div>
                    <div class="confirm__actions">
                        <button class="btn btn--secondary" @click="deleteItem(<?= $item['id'] ?>)">ОК</button>
                    </div>
                    <button class="btn-unset confirm__icon" @click.prevent="showDelete = false" title="Отмена"><svg><use href="assets/img/sprite.svg#close" /></svg></button>
                </div>
            </div>
        </div>

    </div>
    <?php } ?>

<?php } elseif ($user_id == -1) { ?>
    <div class="torrents-fs --empty"><div>Выберите пользователя вверху <svg><use href="assets/img/sprite.svg#arrow" /></svg></div></div>
<?php } else { ?>
    <div class="torrents-fs --empty"><div>Все темы просмотрены, или их ещё нет.</div></div>
<?php } ?>
