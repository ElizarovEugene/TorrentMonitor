<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));

$users = Database::getUserToWatch();
?>
<?php if (empty($users)) { ?>
    <div class="torrents-fs --empty"><div>Нет пользователей для мониторинга</div></div>
<?php die; } ?>

<div x-data='users(<?= json_encode($users, JSON_NUMERIC_CHECK) ?>)'>
    <div class="top-bar">
        <div class="top-bar__select">
            <select @change="setUser($el.value)" x-model.number="currentUser">
                <option value="-1" disabled>Выберите пользователя</option>
                <template x-for='user in usersList'>
                    <option :value="user.id" x-text="`Раздачи пользователя ${user.name} на ${user.tracker}`" :selected="currentUser == user.id"></option>
                </template>
            </select>
        </div>
        <div x-show="currentUser > 0" class="top-bar__actions">
            <div class="popover__wrap">
                <button class="btn btn--secondary" @click="showWatched = true"><svg><use href="assets/img/sprite.svg#watched" /></svg></button>

                <div class="popover"
                    x-cloak
                    x-show="showWatched"
                    @click.away="showWatched = false"
                    >
                    <div class="popover__body"><svg><use href="assets/img/sprite.svg#watched" /></svg>Пометить темы как просмотренные?</div>
                    <div class="popover__actions">
                        <button class="btn btn--primary" @click="markWatched(currentUser)">Да</button>
                        <button class="btn btn--secondary" @click="showWatched = false">Отмена</button>
                    </div>
                </div>
            </div>

            <div class="popover__wrap">
                <button class="btn btn--danger" @click="showDelete = true"><svg><use href="assets/img/sprite.svg#trash" /></svg></button>

                <div class="popover"
                    x-cloak
                    x-show="showDelete"
                    @click.away="showDelete = false"
                    >
                    <div class="popover__body"><svg class="c-danger"><use href="assets/img/sprite.svg#trash" /></svg>Правда удалить пользователя?</div>
                    <div class="popover__actions">
                        <button class="btn btn--danger" @click="deleteUser(currentUser)">Да</button>
                        <button class="btn btn--secondary" @click="showDelete = false">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="currentUser == -1">
        <div class="torrents-fs --empty"><div>Выберите пользователя вверху <svg><use href="assets/img/sprite.svg#arrow" /></svg></div></div>
    </div>

    <div x-html="userThemes"></div>
</div>
