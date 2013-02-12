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
        <select name="tracker">
            <option></option>
            <option>lostfilm.tv</option>
            <option>novafilm.tv</option>
        </select>
    </p>
    <p>
        <label class="label-name">Название</label>
        <input type="text" name="name"><br/>
        <span class="subinput-text">На английском языке<br/>Пример: House, Lie to me</span>
    </p>
    <p>
        <label class="label-name quality">Качество</label>
        <span class="quality">
             <input type="radio" name="hd" value="0"> SD качество<br/>
             <input type="radio" name="hd" value="1"> HD качество<br/>
             <input type="radio" name="hd" value="2"> HD MP4
        </span>
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
            <option>rutracker.org</option>
            <option>nnm-club.ru</option>
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
