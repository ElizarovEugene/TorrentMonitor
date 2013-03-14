$(function() {

    // Скользящее меню
    $(".h-menu li").hover(
        function() {
            $(this).stop().animate({width: "235px"}, 500);
        },
        function() {
            if ($(this).hasClass("active")==false) {
                $(this).stop().animate({width: "27px"}, 500);
            }
        }
    );
    
    // Раскрывающийся список торрентов пользователя
    $(".user-torrent").click(function() {
        $(this).toggleClass("active");
        $(this).next().toggle();
    });

});

// Меню
$(".h-menu li").click(function() {
    $(".h-menu li").stop().animate({width: "27px"}, 500);
    $(".h-menu li").removeClass("active");
    $(this).stop().animate({width: "235px"}, 500);
    $(this).addClass("active");
});


//Подгрузка страниц
function show(name)
{
	$.post("include/"+name+".php",
		function(data) {
			$('#content').empty().append(data);
		}
	);
	
	if (name == 'show_table')
	{
		window.clearTimeout(this.timeoutID);
		this.timeoutID = window.setTimeout(function(){ show('show_table') },7000);
	}
	else if (name == 'show_warnings')
	{
		window.clearTimeout(this.timeoutID);
		this.timeoutID = window.setTimeout(function(){ show('show_warnings') },7000);
	}
	else
	{
		window.clearTimeout(this.timeoutID);
		delete this.timeoutID;
	}
		
	return false;
}

//Разворачивание подробностей о сезоне и серии
function expand(id)
{
	var div = "#"+id;
	if ($(div).is(":hidden"))
		$(div).slideDown("slow");
	else 
		$(div).slideUp("slow");
	return false;
}

//Передаём пароль
$("#enter").submit(function() {
	var $form = $(this),p = $form.find('input[name="password"]').val();
	
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'enter', password: p},
		function(data) {
			if (data.error)
				$('#notice').empty().attr('background', '#FF6633').append(data.msg).delay(3000).fadeOut(400);
			else
          		document.location.reload();
		}, "json"
	);
	return false;
});

//Передаём тему для мониторинга
$("#torrent_add").submit(function()
{
	var $form = $(this),
		s = $form.find('input[type=submit]'),
		n_f = $form.find('input[name="name"]'),
		n = $(n_f).val(),
		u_f = $form.find('input[name="url"]');
		u = $(u_f).val();
	
	$(s).attr('disabled', 'disabled');
								
	if (u != '')
	{
		$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
		$.post("action.php",{action: 'torrent_add', name: n, url: u},
			function(data) {
				$('#notice').empty().append(data).delay(3000).fadeOut(400);
				$(s).removeAttr('disabled');
				$(n_f).val('');
				$(u_f).val('');
			}
		);
	}
	else 
	{
		alert("Вы не указали ссылку на тему!");
		$(s).removeAttr('disabled');
	}
	return false;
});

//Передаём сериал для мониторинга
$("#serial_add").submit(function()
{
	var $form = $(this),
		s = $form.find('input[type=submit]'),
		t = $form.find('select[name="tracker"]').val(),
		n_f = $form.find('input[name="name"]'),
		n = $(n_f).val(),
		h_f = $form.find('input[name="hd"]');
		
		if (t == 'novafilm.tv')
		{
			h = $(h_f).attr('checked');
			if (h == 'checked')
				var $form = $(this), h = 1;
			else
				var $form = $(this), h = 0;
		}
		if (t == 'lostfilm.tv')
		{
			h = $(h_f).val();
			for (var i = 0; i < h_f.length; i++)
			{
				if (h_f[i].checked)
				{
					var $form = $(this), h = h_f[i].value
				}
			}
		}

	$(s).attr('disabled', 'disabled');

	if (t != '' && n != '') 
	{
		$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
		$.post("action.php",{action: 'serial_add', tracker: t, name: n, hd: h},
			function(data) {
				$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
				$(s).removeAttr('disabled');
				$(n_f).val('');
				$(h_f).removeAttr('checked');
			}
		);
	}
	else
	{
		if (t == '') {
			alert("Вы не выбрали трекер!");
			$(s).removeAttr('disabled');
		}
		if (n == '')
		{
			alert("Вы не указали название сериала!");
			$(s).removeAttr('disabled');	
		}
	}
	return false;
});

//Передаём пользователя для мониторинга
$("#user_add").submit(function()
{
	var $form = $(this),
		s = $form.find('input[type=submit]'),
		t = $form.find('select[name="tracker"]').val(),
		n_f = $form.find('input[name="name"]'),
		n = $(n_f).val();

	$(s).attr('disabled', 'disabled');
	
	if (t != '' && n != '') 
	{
		$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
		$.post("action.php",{action: 'user_add', tracker: t, name: n},
			function(data) {
				$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
				$(s).removeAttr('disabled');
				$(n_f).val('');
			}
		);
	}
	else
	{
		if (t == '') {
			alert("Вы не выбрали трекер!");
			$(s).removeAttr('disabled');
		}
		if (n == '')
		{
			alert("Вы не указали имя пользователя!");
			$(s).removeAttr('disabled');	
		}
	}
	return false;
});

//Удаляем пользователя
function delete_user(id)
{
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'delete_user', user_id: id},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$.post("include/show_watching.php",
        		function(data) {
        			$('#content').delay(3000).empty().append(data);
        		}
        	);
		}
	);
	return false;
}

//Удаляем тему из буфера
function delete_from_buffer(id)
{
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'delete_from_buffer', id: id},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$.post("include/show_watching.php",
        		function(data) {
        			$('#content').delay(3000).empty().append(data);
        		}
        	);
		}
	);
	return false;
}

//Перемещаем тему из буфера в мониторинг постоянный
function transfer_from_buffer(id)
{
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'transfer_from_buffer', id: id},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$.post("include/show_watching.php",
        		function(data) {
        			$('#content').delay(3000).empty().append(data);
        		}
        	);
		}
	);
	return false;

}

//Передаём темы для скачивания
function threme_add(id, user_id)
{
	$.post("action.php",{action: 'threme_add', id: id, user_id: user_id},
		function(data) {
			if (data.error)
			{
				$('#notice').empty().attr('background', '#FF6633').append('Ошибка передачи данных<br/>Попробуйте ещё раз.').delay(3000).fadeOut(400);
			}
			else
			{
				$.post("include/show_watching.php",
					function(data) {
						$('#content').empty().append(data);
					}
				);
				return false;				
			}
		}, "json"
	);
	return false;
}

//Удаляем темы 
$("#threme_clear").submit(function()
{
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'threme_clear'},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$.post("include/show_watching.php",
				function(data) {
					$('#content').empty().append(data);
				}
			);
		}
	);
	return false;
});

//Передаём личные данные
$("#credential").submit(function()
{
	var $form = $(this),
		b = $form.find('input[type=button]'),
		id = $form.find('input[name="id"]').val(),
		l = $form.find('input[name="log"]').val(),
		p = $form.find('input[name="pass"]').val();
		
	$(b).attr('disabled', 'disabled');
								
	if (l != '' && p != '')
	{
		$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
		$.post("action.php",{action: 'update_credentials', id: id, log: l, pass: p},
			function(data) {
				$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
				$(b).removeAttr('disabled');
			}
		);
	}
	else 
	{
		if (l == '')
			alert("Вы не указали логин!");
		if (p == '')
			alert("Вы не указали пароль!");
		$(b).removeAttr('disabled');
	}
	return false;
});
	
//Передаём настройки
$("#setting").submit(function()
{
	var $form = $(this),
		s = $form.find('input[type=submit]'),
		p = $form.find('input[name="path"]').val(),
		e = $form.find('input[name="email"]').val(),
		s = $form.find('input[name="send"]').attr('checked');
		s_w = $form.find('input[name="send_warning"]').attr('checked');
		a = $form.find('input[name="auth"]').attr('checked');
	
	$(s).attr('disabled', 'disabled');
	
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'update_settings', path: p, email: e, send: s, send_warning: s_w, auth: a},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$(s).removeAttr('disabled');
		}
	);
	return false;
});

//Передаём пароль
$("#change_pass").submit(function()
{
	var $form = $(this),
		s = $form.find('input[type=submit]'),
		p = $form.find('input[name="password"]').val(),
		p2 = $form.find('input[name="password2"]').val();
		
	$(s).attr('disabled', 'disabled');
	
	if (p != '')
	{
		if (p != p2) 
		{
			alert('Пароль и подтверждение должны совпадать.');
			$(s).removeAttr('disabled');
		}
		else
		{
			$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
			$.post("action.php",{action: 'change_pass', pass: p},
				function(data) {
					if (data.error)
						$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
					else
						document.location.reload();
				}, "json"
			);
		}
	}
	else
	{
		alert('Пароль не может быть пустым.');
		$(s).removeAttr('disabled');
	}
	return false;
});

//Удаляем мониторинг
function del(id)
{
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'del', id: id},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
		}
	);
	return false;
}

//Удаляем пользователя
function del_user(id)
{
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'del_user', id: id},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
		}
	);
	return false;
}

//Выводим логин/пароль в зависимости от выбранного в списке
function changefunc() 
{
	var select = document.getElementById("selectfunc");
	var a = ['kinozal.tv', 'lostfilm.tv', 'nnm-club.ru', 'novafilm.tv', 'rutracker.org', 'tapochek.net'];
	for (var i = 0; i < a.length; i++)
	{
		var e = a[i];
		if (select.value == e)
			var d = "block";    
		else
			var d = "none";
		document.getElementById(e + "_label").style.display = d;
	}
}

//Меняем checkbox на radiobutton
function changeField()
{
	var tracker = document.getElementById("tracker").value;
	if (tracker == 'lostfilm.tv')
		$('#changedField').empty().append('<span class="quality"><input type="radio" name="hd" value="0"> SD качество<br/><input type="radio" name="hd" value="1"> HD качество<br/><input type="radio" name="hd" value="2"> HD MP4</span>');
	if (tracker == 'novafilm.tv')
		$('#changedField').empty().append('<input type="checkbox" name="hd"> HD качество</label>');
}