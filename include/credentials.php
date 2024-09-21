<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));

$credentials = Database::getAllCredentials();
$trackers = Database::getTrackersList();
?>
<div class="top-bar mb-2">
    <div class="top-bar__title"><svg><use href="assets/img/sprite.svg#profile" /></svg> Учётные данные</div>
</div>

<div x-data='credentials(<?= json_encode($credentials, JSON_NUMERIC_CHECK) ?>)'>

    <label class="row">
        <div class="col --2:lg mb-1">Трекер:</div>
        <div class="col --5:lg mb-2">
            <select @change="setTracker()" x-model="trackerIndex">
                <option value="" disabled>Выберите трекер</option>
                <template x-for="(tracker, index) in trackers" :key="tracker.id">
                    <option :value="index" x-text="tracker.tracker"></option>
                </template>
            </select>
        </div>
    </label>

<div x-show="tracker != ''">
    <div x-show="!tracker.necessarily">Учётные данные не требуются.</div>
    <div x-show="tracker.necessarily">
        <form @submit.prevent="updateTracker($el)" action="action.php">
            <input type="hidden" name="id" x-model="tracker.id">
            <label class="row">
                <div class="col --2:lg mb-1">Логин:</div>
                <div class="col --5:lg mb-2">
                    <input type="text" name="log" x-model="tracker.log" required>
                </div>
            </label>
            <label class="row">
                <div class="col --2:lg mb-1">Пароль:</div>
                <div class="col --5:lg  mb-2">
                    <input type="password" name="pass" x-model="tracker.pass" required>
                </div>
            </label>

            <template x-if="tracker.tracker == 'baibako.tv'">
                <label class="row">
                    <div class="col --2:lg mb-1">Passkey</div>
                    <div class="col --5:lg  mb-2">
                        <input type="text" name="passkey" x-model="tracker.passkey" required>
                    </div>
                </label>
            </template>

            <div class="row mt-2">
                <div class="col --2:lg"></div>
                <div class="col">
                    <button type="submit" class="btn btn--primary">Сохранить</button>
                </div>
            </div>
        </form>
    </div>
</div>

</div>
