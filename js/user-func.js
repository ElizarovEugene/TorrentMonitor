$(function()
{
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
	$.get("include/"+name+".php",
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
          	console.log(data.error)
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
		u_f = $form.find('input[name="url"]'),
		u = $(u_f).val(),
		p_f = $form.find('input[name="path"]'),
		p = $(p_f).val(); 
	
    if (u == '')
    {
    	alert("Вы не указали ссылку на тему!");
    	return false;
    }
								
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'torrent_add', name: n, url: u, path: p},
		function(data) {
			$('#notice').empty().append(data).delay(3000).fadeOut(400);
			$(n_f).val('');
			$(u_f).val('');
		}
	);
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
		h_f = $form.find('input[name="hd"]'),
		p_f = $form.find('input[name="path"]'),
		p = $(p_f).val();

	h = $(h_f).val();
	for (var i = 0; i < h_f.length; i++)
	{
		if (h_f[i].checked)
		{
			var $form = $(this), h = h_f[i].value
		}
	}

    if (t == '')
    {
    	alert("Вы не выбрали трекер!");
    	return false;
    }
    
    if (n == '')
    {
    	alert("Вы не указали название сериала!");
    	return false;
    }     

	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'serial_add', tracker: t, name: n, hd: h, path: p},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$(n_f).val('');
			$(h_f).removeAttr('checked');
		}
	);
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

    if (t == '')
    {
    	alert("Вы не выбрали трекер!");
    	return false;
    }
    
    if (n == '')
    {
    	alert("Вы не указали имя пользователя!");
    	return false;
    }    
	
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'user_add', tracker: t, name: n},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$(n_f).val('');
		}
	);
	return false;
});

//Удаляем пользователя
function delete_user(id)
{
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'delete_user', user_id: id},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$.get("include/show_watching.php",
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
			$.get("include/show_watching.php",
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
			$.get("include/show_watching.php",
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
				$.get("include/show_watching.php",
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
			$.get("include/show_watching.php",
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

	if (l == '')
	{
		alert("Вы не указали логин!");
		return false;
	}
	
	if (p == '')
	{
		alert("Вы не указали пароль!");
		return false;
	}	
								
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'update_credentials', id: id, log: l, pass: p},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$(b).removeAttr('disabled');
		}
	);
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
		pr = $form.find('input[name="proxy"]').attr('checked');
		pa = $form.find('input[name="proxyAddress"]').val();
		t = $form.find('input[name="torrent"]').attr('checked');
		tc = $form.find('select[name="torrentClient"]').val();
		ta = $form.find('input[name="torrentAddress"]').val();
		tl = $form.find('input[name="torrentLogin"]').val();
		tp = $form.find('input[name="torrentPassword"]').val();
		ptd = $form.find('input[name="pathToDownload"]').val();
		dt = $form.find('input[name="deleteTorrent"]').attr('checked');
		dof = $form.find('input[name="deleteOldFiles"]').attr('checked');
	
	if (p == '')
	{
		alert('Вы не указали путь сохранения torrent-файлов.');
		return false;
	}
	
	if (pr == 'checked' && pa == '')
	{
		alert('Вы не указали адрес proxy-сервера.');
		return false;
	}
	
	if (t == 'checked' && tc == ''  && ta == '' && ptd == '')
	{
    	alert('Вы не указали настройки торрент-клиента.');
		return false;
	}
	
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'update_settings', path: p, email: e, send: s, send_warning: s_w, auth: a, proxy: pr, proxyAddress: pa, torrent: t, torrentClient: tc, torrentAddress: ta, torrentLogin: tl, torrentPassword: tp, pathToDownload: ptd, deleteTorrent: dt, deleteOldFiles: dof},
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
		
	if (p == '')
	{
    	alert('Пароль не может быть пустым.');
		return false;
	}
	
	if (p != p2) 
	{
		alert('Пароль и подтверждение должны совпадать.');
		return false;
	}
	
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'change_pass', pass: p},
		function(data) {
			if (data.error)
				$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			else
				document.location.reload();
		}, "json"
	);
	return false;
});

//Удаляем мониторинг
function del(id)
{
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn();
	$.post("action.php",{action: 'del', id: id},
		function(data) {
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
			$.get("include/show_table.php",
        		function(data) {
        			$('#content').delay(3000).empty().append(data);
        		}
        	);
		}
	);	
	return false;
}

//Выводим логин/пароль в зависимости от выбранного в списке
function changefunc() 
{
    var select = document.getElementById("selectfunc");
    var selectedText = select.options[select.selectedIndex].text;
    var a = ['anidub.com', 'animelayer.ru', 'baibako.tv', 'casstudio.tv', 'kinozal.tv', 'lostfilm.tv', 'newstudio.tv', 'nnm-club.me', 'novafilm.tv', 'rutracker.org'];
    for (var i = 0; i < a.length; i++)
    {
        var e = a[i];
        var d;
        if (selectedText == e)
            d = "block";
        else
            d = "none";
        document.getElementById(e + "_label").style.display = d;
    }
}

//Меняем radiobutton
function changeField()
{
	var tracker = document.getElementById("tracker").value;
    if (tracker == 'baibako.tv' || tracker == 'newstudio.tv')
        $('#changedField').empty().append('<span class="quality"><input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1"> HD 720<br /><input type="radio" name="hd" value="2"> HD 1080</span>');
	if (tracker == 'lostfilm.tv')
		$('#changedField').empty().append('<span class="quality"><input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1"> Автовыбор HD 720/1080<br /><input type="radio" name="hd" value="2"> HD 720 MP4');
	if (tracker == 'novafilm.tv')
		$('#changedField').empty().append('<span class="quality"><input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1"> HD 720</span>');
}

//Показать/скрыть настройки proxy
function showProxy()
{
    var proxy = document.getElementById("proxy").checked;
	if (proxy)
	    document.getElementById("proxySettings").style.display = "block";
    else
        document.getElementById("proxySettings").style.display = "none";
}

//Показать/скрыть настройки торрент-клиента
function showTorrent()
{
    var torrent = document.getElementById("torrent").checked;
	if (torrent)
	    document.getElementById("torrentSettings").style.display = "block";
    else
        document.getElementById("torrentSettings").style.display = "none";
}