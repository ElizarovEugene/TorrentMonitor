<div class="modal__backdrop"
    x-show="modalEditThemeShow"
    x-transition.opacity
    x@click="closeModalEditTheme()"
    >
    <div class="modal container-sm:max p-0" x-transition.scale @click.stop>
        <div class="modal__bar">
            <div class="modal__title">Редактирование темы</div>
            <button class="modal__close" @click="closeModalEditTheme()"><svg><use href="assets/img/sprite.svg#close" /></svg></button>
        </div>

        <div x-show="editData.closed" class="form-error">Тема закрыта на форуме!</div>

        <form @submit.prevent="updateItem($el, editData.id)" action="action.php">

            <div class="modal__body">
                <label class="row">
                    <div class="col --12 mb-1">Название:</div>
                    <div class="col --12 mb-2">
                        <input type="text" name="name" x-model="editData.name">
                        <div class="form-help">Не обязательно</div>
                    </div>
                </label>
                <label class="row">
                    <div class="col --12 mb-1">Ссылка на тему:</div>
                    <div class="col --12 mb-2">
                        <input type="url" name="url" x-model="editData.url" required>
                        <div class="form-help">Пример: http://rutracker.org/forum/viewtopic.php?t=4201572</div>
                    </div>
                </label>
                <label class="row">
                    <div class="col --12 mb-1">Директория для скачивания:</div>
                    <div class="col --12 mb-2">
                        <input type="text" name="path" x-model="editData.path">
                        <div class="form-help">Например: /var/lib/transmission/downloads или C:/downloads/</div>
                    </div>
                </label>
                <label class="row">
                    <div class="col --12 mb-1">Выполнить скрипт:</div>
                    <div class="col --12 mb-2">
                        <input type="text" name="script" x-model="editData.script">
                        <div class="form-help">Например: /home/user/check.sh</div>
                    </div>
                </label>
                <label class="row" @click="editData.auto_update = !editData.auto_update">
                    <div class="col --12 mb-2 toggler-wrap">
                        <div class="toggler" :class="editData.auto_update && '--done'"></div> Обновлять заголовок автоматически
                    </div>
                </label>
                <label class="row" @click="editData.pause = !editData.pause">
                    <div class="col --12 mb-2 toggler-wrap">
                        <div class="toggler" :class="editData.pause && '--done'"></div> Поставить раздачу на паузу
                    </div>
                </label>
                <label class="row" @click="editData.reset = !editData.reset">
                    <div class="col --12 toggler-wrap">
                        <div class="toggler" :class="editData.reset && '--done'"></div> Сбросить время последнего обновления
                    </div>
                </label>
            </div>

            <div class="modal__buttons">
                <button
                    @click="closeModalEditTheme()"
                    type="button"
                    class="btn btn--secondary"
                    >Закрыть</button>
                <button
                    type="submit"
                    class="btn btn--primary"
                    >Сохранить</button>
            </div>

        </form>
    </div>
</div>


<div class="modal__backdrop"
    x-show="modalEditSeriesShow"
    x-transition.opacity
    x@click="closeModalEditSeries()"
    >
    <div class="modal container-sm:max p-0" x-transition.scale @click.stop>
        <div class="modal__bar">
            <div class="modal__title">Редактирование сериала</div>
            <button class="modal__close" @click="closeModalEditSeries()"><svg><use href="assets/img/sprite.svg#close" /></svg></button>
        </div>

        <form @submit.prevent="updateItem($el, editData.id)" action="action.php">

            <div class="modal__body">
                <label class="row">
                    <div class="col --12 mb-1">Название:</div>
                    <div class="col --12 mb-2">
                        <input type="text" name="name" x-model="editData.name" required>
                    </div>
                </label>

                <template x-if="editData.tracker == 'baibako.tv' || editData.tracker == 'hamsterstudio.org' || editData.tracker == 'newstudio.tv'">
                <div class="row">
                    <div class="col --12 mb-1">Качество:</div>
                    <div class="col --12 mb-2">
                        <div class="quality-select">
                            <button type="button" :class="editData.hd == 0 && '--current'" @click="editData.hd = 0">SD</button>
                            <button type="button" :class="editData.hd == 1 && '--current'" @click="editData.hd = 1">HD 720</button>
                            <button type="button" :class="editData.hd == 2 && '--current'" @click="editData.hd = 2">FHD 1080</button>
                        </div>
                    </div>
                </div>
                </template>

                <template x-if="editData.tracker == 'lostfilm.tv' || editData.tracker == 'lostfilm-mirror'">
                <div class="row">
                    <div class="col --12 mb-1">Качество:</div>
                    <div class="col --12 mb-2">
                        <div class="quality-select">
                            <button type="button" :class="editData.hd == 0 && '--current'" @click="editData.hd = 0">SD</button>
                            <button type="button" :class="editData.hd == 2 && '--current'" @click="editData.hd = 2">HD 720 MP4</button>
                            <button type="button" :class="editData.hd == 1 && '--current'" @click="editData.hd = 1">FHD 1080</button>
                        </div>
                    </div>
                </div>
                </template>

                <label class="row">
                    <div class="col --12 mb-1">Директория для скачивания:</div>
                    <div class="col --12 mb-2">
                        <input type="text" name="path" x-model="editData.path">
                        <div class="form-help">Например: /var/lib/transmission/downloads или C:/downloads</div>
                    </div>
                </label>
                <label class="row">
                    <div class="col --12 mb-1">Выполнить скрипт:</div>
                    <div class="col --12 mb-2">
                        <input type="text" name="script" x-model="editData.script">
                        <div class="form-help">Например: /home/user/check.sh</div>
                    </div>
                </label>
                <label class="row" @click="editData.pause = !editData.pause">
                    <div class="col --12 mb-2 toggler-wrap">
                        <div class="toggler" :class="editData.pause && '--done'"></div> Поставить раздачу на паузу
                    </div>
                </label>
                <label class="row" @click="editData.reset = !editData.reset">
                    <div class="col --12 toggler-wrap">
                        <div class="toggler" :class="editData.reset && '--done'"></div> Сбросить время последнего обновления
                    </div>
                </label>
            </div>

            <div class="modal__buttons">
                <button
                    @click="closeModalEditSeries()"
                    type="button"
                    class="btn btn--secondary"
                    >Закрыть</button>
                <button
                    type="submit"
                    class="btn btn--primary"
                    >Сохранить</button>
            </div>

        </form>
    </div>
</div>
