<?php include_once "header.php"; ?>
<!-- END Заголовок страницы -->
<!-- !Форма авторизации -->
<div id="wrapper">
    <header id="header"></header>
    <div id="content">
		<div class="enter">
			<h2 class="settings-title">Вход в систему</h2>
			<form id="enter">
				<p><label class="label-name">Пароль</label><input type="password" name="password" id="password"></p>
				<p><label class="label-remember">Запомнить?</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="remember" id="remember"></p>
				<button class="form-button">Войти</button>
			</form>
		</div>
        <div class="clear-both"></div>
    </div>
</div>
<!-- END Форма авторизации -->
<!-- !Подвал страницы -->
<?php include_once "footer.php"; ?>
<!-- END Подвал страницы -->