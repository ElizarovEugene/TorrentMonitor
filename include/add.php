<h2 class="monitoring-title">Добавить тему</h2>
<form id="torrent_add">
    <p>
        <label class="label-name">Название</label>
        <input type="text" name="name"><br/>
        <span class="subinput-text">Необязательно</span>
    </p>
    <p>
        <label class="label-name">Ссылка на тему</label>
        <input type="text" name="url">
        <span class="subinput-text">Пример: http://rutracker.org/forum/viewtopic.php?t=4201572</span>
    </p>
    <button class="form-button">Добавить</button>
</form>
<br/>
<br/>

<h2 class="monitoring-title">Добавить сериал</h2>
<form id="serial_add">
    <p>
        <label class="label-name">Трекер</label>
        <select id="tracker" name="tracker" onchange="changeField()">
            <option></option>
            <option value="lostfilm.tv">lostfilm.tv</option>
            <option value="novafilm.tv">novafilm.tv</option>
        </select>
    </p>
    <p>
        <label class="label-name">Название</label>
        <input type="text" name="name"><br/>
        <span class="subinput-text">На английском языке<br/>Пример: House, Lie to me</span>
    </p>
    <p>
        <label class="label-name"></label>
        <span id="changedField"></span>
    </p>
    <button class="form-button">Добавить</button>
</form>
<br/>
<br/>

<h2 class="monitoring-title">Добавить пользователя</h2>
<form id="user_add">
    <p>
        <label class="label-name">Трекер</label>
        <select name="tracker">
            <option></option>
            <option>nnm-club.me</option>
            <option>rutracker.org</option>
            <option>tfile.me</option>
        </select>
    </p>
    <p>
        <label class="label-name">Имя</label>
        <input type="text" name="name">
        <span class="subinput-text">Пример: KorP</span>
    </p>
    <button class="form-button">Добавить</button>
</form>
<div class="clear-both"></div>
<script src="js/user-func.js"></script>