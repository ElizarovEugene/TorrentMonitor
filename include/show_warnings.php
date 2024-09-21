<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/Errors.class.php";
include_once $dir."class/Url.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));
?>

<div class="top-bar mb-2">
    <div class="top-bar__title"><svg><use href="assets/img/sprite.svg#errors" /></svg> Ошибки</div>
</div>

<?php
$trackers = Database::getWarningsCount();

if ($trackers != NULL) {
    foreach ($trackers as $tkey => $tracker) {
        $errors = Database::getWarningsList($tracker['where']);
        if ($errors != NULL) {
            $groups = [];
            foreach ($errors as $error) {
                $key = $error['reason'];
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'errors' => [$error],
                        'count' => 1,
                    ];
                } else {
                    $groups[$key]['errors'][] = $error;
                    $groups[$key]['count'] += 1;
                }
            }
            $tracker['errors'] = $groups;
        } ?>

        <div class="mb-4"
            x-data="warnings"
            x-show="!cleared"
            x-transition:leave="tm-item--leaving"
            x-transition:leave-start="tm-item--leaving-start"
            x-transition:leave-end="tm-item--leaving-end"
            >
            <div class="fz-md text-center mb-1 d-flex">
                Ошибки:&nbsp;<strong><?= $tracker['where'] ?></strong>
                <button @click.prevent="clear('<?= $tracker['where'] ?>', <?= $tracker['count'] ?>)" class="btn btn--danger btn--round ml-auto" title="Очистить ошибки"><svg><use href="assets/img/sprite.svg#trash" /></svg></button>
            </div>

            <?php foreach ($tracker['errors'] as $root => $errors) { ?>
                <div x-data="{expanded: false}"
                    @click="expanded = !expanded"
                    class="warning-root"
                    :class="{'--expanded': expanded}"
                    >
                    <div class="warning-root__title">
                        <?= Errors::getWarning($root) ?> (<?= $errors['count'] ?>)
                        <svg><use href="assets/img/sprite.svg#plus" /></svg>
                    </div>
                    <div x-show="expanded" class="warning-root__body">
                    <?php foreach ($errors['errors'] as $error) {
                        $date = $error['day']." ".Sys::dateNumToString($error['month'])." ".$error['year']." ".$error['time'];
                        ?>
                        <div class="row tm-item">
                            <div class="col --auto">
                                <div class="tracker-icon" style="background-image: url(img/<?= $error['where'] ?>.ico)" title="<?= $error['where'] ?>"></div>
                            </div>
                            <div class="col">
                                <?= Errors::getWarning($error['reason']) ?>
                                <?php if ($error['id'] != NULL) {
                                    $torrent = Database::getTorrent($error['id']);
                                    $link = Url::create($torrent[0]['tracker'], $torrent[0]['name'], $torrent[0]['torrent_id']); ?>
                                    <br>Раздача: <?= $link['url'] ?>
                                <?php } ?>
                            </div>
                            <div class="col --12 d-block d-none:xl tm-item__spacer"></div>
                            <div class="col --auto tm-item__date">
                                <?= $date ?>
                            </div>
                        </div>
                    <?php } ?>
                    </div>
                </div>
            <?php } ?>


        </div>

    <?php }
}
