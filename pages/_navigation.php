<nav class="navigation">
    <button class="btn navigation-add" @click="showModalAdd()"><svg><use href="assets/img/sprite.svg#add" /></svg>Добавить</button>
    <button class="btn" :class="{ 'active': pageCurrent == 'show_table' }" @click="showPage('show_table')"><svg><use href="assets/img/sprite.svg#download" /></svg>Торренты</button>
    <button class="btn" :class="{ 'active': pageCurrent == 'show_watching' }" @click="showPage('show_watching')"><svg><use href="assets/img/sprite.svg#users" /></svg>Пользователи</button>
    <div class="navigation-sep"></div>
    <button class="btn" :class="{ 'active': pageCurrent == 'credentials' }" @click="showPage('credentials')"><svg><use href="assets/img/sprite.svg#profile" /></svg>Учётные данные</button>
    <button class="btn" :class="{ 'active': pageCurrent == 'settings' }" @click="showPage('settings')"><svg><use href="assets/img/sprite.svg#settings" /></svg>Настройки</button>
    <div class="navigation-sep"></div>
    <template x-if="countErrors > 0">
        <button class="btn" :class="{ 'active': pageCurrent == 'show_warnings' }" @click="showPage('show_warnings')">
            <svg class="c-danger"><use href="assets/img/sprite.svg#errors-has" /></svg>Ошибки <div class="navigation-badge"><span x-text="countErrors"></span></div>
        </button>
    </template>
    <template x-if="countErrors <= 0">
        <div class="btn">
            <svg><use href="assets/img/sprite.svg#errors" /></svg>Ошибок нет
        </div>
    </template>
    <button class="btn" :class="{ 'active': pageCurrent == 'check' }" @click="showPage('check')"><svg><use href="assets/img/sprite.svg#health" /></svg>Тестирование</button>
    <button class="btn" :class="{ 'active': pageCurrent == 'execution' }" @click="showPage('execution')"><svg><use href="assets/img/sprite.svg#play" /></svg>Запуск</button>
    <div class="navigation-sep"></div>
    <button class="btn" :class="{ 'active': pageCurrent == 'news' }" @click="showPage('news')"><svg><use href="assets/img/sprite.svg#news" /></svg>Новости
        <template x-if="countNews > 0">
            <div class="navigation-badge"><span x-text="countNews"></span></div>
        </template>
    </button>
    <button class="btn" :class="{ 'active': pageCurrent == 'help' }" @click="showPage('help')"><svg><use href="assets/img/sprite.svg#question" /></svg>Справка</button>
    <div class="navigation-sep"></div>
    <div class="navigation-info">Синхронизация: <br><?= $lastStartTime ?></div>
</nav>
