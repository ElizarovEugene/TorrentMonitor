<div class="modal__backdrop"
    x-data="add"
    x-show="modalAdd"
    x-transition.opacity
    >
    <div class="modal container-sm:max p-0" x-transition.scale @click.stop>
        <div class="modal__bar">
            <nav class="modal__tabs">
                <button :class="type == 'theme' && '--current'" @click="type = 'theme'">Тему</button>
                <button :class="type == 'series' && '--current'" @click="type = 'series'">Сериал</button>
                <button :class="type == 'user' && '--current'" @click="type = 'user'">Пользователя</button>
            </nav>
            <button class="modal__close" @click="closeModalAdd()"><svg><use href="assets/img/sprite.svg#close" /></svg></button>
        </div>

        <form x-show="type == 'theme'" @submit.prevent="addTheme('torrent_add', $el)" action="action.php">

            <div class="modal__body">
                <label class="row">
                    <div class="col --12 mb-1">Название:</div>
                    <div class="col --12 mb-2">
                        <input type="text" name="name" x-model="theme.name">
                        <div class="form-help">Не обязательно</div>
                    </div>
                </label>
                <label class="row">
                    <div class="col --12 mb-1">Ссылка на тему:</div>
                    <div class="col --12 mb-2">
                        <input type="url" name="url" x-model="theme.url" :required="type == 'theme'">
                        <div class="form-help">Пример: http://rutracker.org/forum/viewtopic.php?t=4201572</div>
                    </div>
                </label>
                <label class="row">
                    <div class="col --12 mb-1">Директория для скачивания:</div>
                    <div class="col --12 mb-2">
                        <input type="text" name="path" x-model="theme.path">
                        <div class="form-help">Например: /var/lib/transmission/downloads или C:/downloads/</div>
                    </div>
                </label>
                <label class="row" @click="theme.update_header = !theme.update_header">
                    <div class="col --12 toggler-wrap">
                        <div class="toggler" :class="theme.update_header && '--done'"></div> Обновлять заголовок автоматически
                    </div>
                </label>
            </div>

            <div class="modal__buttons">
                <button
                    @click="closeModalAdd()"
                    type="button"
                    class="btn btn--secondary"
                    >Закрыть</button>
                <button
                    type="submit"
                    class="btn btn--primary"
                    >Добавить</button>
            </div>

        </form>


        <form x-show="type == 'series'" @submit.prevent="addSeries('serial_add', $el)" action="action.php">

            <div class="modal__body">
                <label class="row">
                    <div class="col --12 mb-1">Трекер:</div>
                    <div class="col --12 mb-2">
                        <select x-model="series.tracker" :required="type == 'series'">
                            <option value="" disabled>выберите</option>
                            <template x-for='tracker in ["baibako.tv","hamsterstudio.org","lostfilm.tv","lostfilm-mirror","newstudio.tv"]'>
                                <option x-text="tracker" :selected="series.tracker == tracker"></option>
                            </template>
                        </select>
                    </div>
                </label>
                <label class="row">
                    <div class="col --12 mb-1">Название:</div>
                    <div class="col --12 mb-2">
                        <input type="text" name="name" x-model="series.name" :required="type == 'series'">
                        <div class="form-help">На английском языке<br/>Пример: House, Lie to me</div>
                    </div>
                </label>

                <template x-if="series.tracker == 'baibako.tv' || series.tracker == 'hamsterstudio.org' || series.tracker == 'newstudio.tv'">
                <div class="row">
                    <div class="col --12 mb-1">Качество:</div>
                    <div class="col --12 mb-2">
                        <div class="quality-select">
                            <button type="button" :class="series.hd == 0 && '--current'" @click="series.hd = 0">SD</button>
                            <button type="button" :class="series.hd == 1 && '--current'" @click="series.hd = 1">HD 720</button>
                            <button type="button" :class="series.hd == 2 && '--current'" @click="series.hd = 2">FHD 1080</button>
                        </div>
                    </div>
                </div>
                </template>

                <template x-if="series.tracker == 'lostfilm.tv' || series.tracker == 'lostfilm-mirror'">
                <div class="row">
                    <div class="col --12 mb-1">Качество:</div>
                    <div class="col --12 mb-2">
                        <div class="quality-select">
                            <button type="button" :class="series.hd == 0 && '--current'" @click="series.hd = 0">SD</button>
                            <button type="button" :class="series.hd == 2 && '--current'" @click="series.hd = 2">HD 720 MP4</button>
                            <button type="button" :class="series.hd == 1 && '--current'" @click="series.hd = 1">FHD 1080</button>
                        </div>
                    </div>
                </div>
                </template>

                <label class="row">
                    <div class="col --12 mb-1">Директория для скачивания:</div>
                    <div class="col --12">
                        <input type="text" name="path" x-model="series.path">
                        <div class="form-help">Например: /var/lib/transmission/downloads или C:/downloads</div>
                    </div>
                </label>
            </div>

            <div class="modal__buttons">
                <button
                    @click="closeModalAdd()"
                    type="button"
                    class="btn btn--secondary"
                    >Закрыть</button>
                <button
                    type="submit"
                    class="btn btn--primary"
                    >Добавить</button>
            </div>

        </form>


        <form x-show="type == 'user'" @submit.prevent="addUser('user_add', $el)" action="action.php">

            <div class="modal__body">
                <label class="row">
                    <div class="col --12 mb-1">Трекер:</div>
                    <div class="col --12 mb-2">
                        <select x-model="user.tracker" :required="type == 'user'">
                            <option value="" disabled>выберите</option>
                            <template x-for='tracker in ["booktracker.org","nnmclub.to","pornolab.net","rutracker.org","tfile.cc"]'>
                                <option x-text="tracker" :selected="user.tracker == tracker"></option>
                            </template>
                        </select>
                    </div>
                </label>
                <label class="row">
                    <div class="col --12 mb-1">Имя:</div>
                    <div class="col --12">
                        <input type="text" name="name" x-model="user.name" :required="type == 'user'">
                        <div class="form-help">Пример: KorP</div>
                    </div>
                </label>
            </div>

            <div class="modal__buttons">
                <button
                    @click="closeModalAdd()"
                    type="button"
                    class="btn btn--secondary"
                    >Закрыть</button>
                <button
                    type="submit"
                    class="btn btn--primary"
                    >Добавить</button>
            </div>

        </form>
    </div>
</div>
