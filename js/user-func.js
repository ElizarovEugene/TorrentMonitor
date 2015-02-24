$( document ).ready(function() 
{    // Скользящее меню
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
    
    // Раскрывающийся список торрентов пользователя за которым ведётся мониторинг
    $(".user-torrent").click(function() {
        $(this).toggleClass("active");
        $(this).next().toggle();
        var id = $(this).attr('id');
        var date = new Date(new Date().getTime() + 60*1000);
        document.cookie="id="+id+"; path=/; expires="+date.toUTCString();
    });

    // Меню
    $(".h-menu li").click(function() {
        $(".h-menu li").stop().animate({width: "27px"}, 500);
        $(".h-menu li").removeClass("active");
        $(this).stop().animate({width: "235px"}, 500);
        $(this).addClass("active");
    });

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
    
    //Передаём данные для обновления
    $("#torrent_update").submit(function()
    {
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            i_f = $form.find('input[name="id"]'),
            id = $(i_f).val(),
            t_f = $form.find('input[name="tracker"]'),
            t = $(t_f).val(),
            n_f = $form.find('input[name="name"]'),
            n = $(n_f).val(),
            u_f = $form.find('input[name="url"]'),
            u = $(u_f).val(),
            update = $form.find('input[name="update"]').prop('checked'),
            p_f = $form.find('input[name="path"]'),
            p = $(p_f).val();
            h_f = $form.find('input[name="hd"]'),
            r_f = $form.find('input[name="reset"]').prop('checked');
            
        h = $(h_f).val();
        for (var i = 0; i < h_f.length; i++)
        {
            if (h_f[i].checked)
            {
                var $form = $(this), h = h_f[i].value
            }
        }
            
        if (u == '')
        {
            alert("Вы не указали ссылку на тему!");
            return false;
        }
        
        if (n == '')
        {
            alert("Вы не указали название сериала!");
            return false;
        }                          

        $('#notice_sub').empty().append('Обрабатывается запрос...').fadeIn();
        $.post("action.php",{action: 'update', id: id, tracker: t, name: n, url: u, update: update, path: p, hd: h, reset: r_f},
            function(data) {
                $('#notice_sub').empty().append(data).delay(3000).fadeOut(400);
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
            serverAddress = $form.find('input[name="serverAddress"]').val();
            send = $form.find('input[name="send"]').prop('checked');
            sendUpdate = $form.find('input[name="sendUpdate"]').prop('checked');
            sendUpdateEmail = $form.find('input[name="sendUpdateEmail"]').val();
            sendUpdatePushover = $form.find('input[name="sendUpdatePushover"]').val();            
            sendWarning = $form.find('input[name="sendWarning"]').prop('checked');
            sendWarningEmail = $form.find('input[name="sendWarningEmail"]').val();
            sendWarningPushover = $form.find('input[name="sendWarningPushover"]').val();            
            auth = $form.find('input[name="auth"]').prop('checked');
            proxy = $form.find('input[name="proxy"]').prop('checked');
            proxyAddress = $form.find('input[name="proxyAddress"]').val();
            torrent = $form.find('input[name="torrent"]').prop('checked');
            torrentClient = $form.find('select[name="torrentClient"]').val();
            torrentAddress = $form.find('input[name="torrentAddress"]').val();
            torrentLogin = $form.find('input[name="torrentLogin"]').val();
            torrentPassword = $form.find('input[name="torrentPassword"]').val();
            pathToDownload = $form.find('input[name="pathToDownload"]').val();
            deleteDistribution = $form.find('input[name="deleteDistribution"]').prop('checked');
            deleteOldFiles = $form.find('input[name="deleteOldFiles"]').prop('checked');
        
        if (serverAddress == '')
        {
            alert('Вы не указали адрес сервера TM.');
            return false;
        }
        
        if (proxy == 'checked' && proxyAddress == '')
        {
            alert('Вы не указали адрес proxy-сервера.');
            return false;
        }
        
        if (torrent == 'checked' && torrentClient == ''  && torrentAddress == '' && pathToDownload == '')
        {
            alert('Вы не указали настройки торрент-клиента.');
            return false;
        }

        $('#notice').empty().append('Обрабатывается запрос...').fadeIn();
        $.post("action.php",{action: 'update_settings', serverAddress: serverAddress, send: send, sendUpdate: sendUpdate,
            sendUpdateEmail: sendUpdateEmail, sendUpdatePushover: sendUpdatePushover, sendWarning: sendWarning, sendWarningEmail: sendWarningEmail, sendWarningPushover: sendWarningPushover, auth: auth, proxy: proxy, proxyAddress: proxyAddress, torrent: torrent, torrentClient: torrentClient, torrentAddress: torrentAddress, torrentLogin: torrentLogin, torrentPassword: torrentPassword, pathToDownload: pathToDownload, deleteDistribution: deleteDistribution, deleteOldFiles: deleteOldFiles},
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

});

//Подгрузка страниц
function show(name)
{
    if (name == 'check' || name == 'execution' || name == 'update')
        $('#content').empty().append('<img src="img/ajax-loader.gif" class="loader">');

    $.get("include/"+name+".php",
        function(data) {
            $('#content').empty().append(data);
    });

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

//Развернуть/свернуть слой
function expand(id)
{
	var div = "#"+id;
	if ($(div).is(":hidden"))
		$(div).slideDown("slow");
	else 
		$(div).slideUp("slow");
	return false;
}

//Удаляем пользователя
function delete_user(id)
{
    if (confirm("Удалить?"))
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
}

//Удаляем тему из буфера
function delete_from_buffer(id)
{
    if (confirm("Удалить?"))
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

//Удаляем мониторинг
function del(id, name)
{
    if (confirm('Удалить '+name+'?'))
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
}

//Выводим логин/пароль в зависимости от выбранного в списке
function changefunc() 
{
    var select = document.getElementById("selectfunc");
    var selectedText = select.options[select.selectedIndex].text;
    var a = ['anidub.com', 'animelayer.ru', 'baibako.tv', 'casstudio.tv', 'kinozal.tv', 'newstudio.tv', 'nnm-club.me', 'novafilm.tv', 'pornolab.net', 'rustorka.com', 'rutracker.org', 'tracker.0day.kiev.ua'];
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

//Форма редактирования записи
function showForm(id)
{
    $.get("include/form.php", {'id': id},
        function(data) {
            $('.blok').empty().append(data);
        }
    );
    $(".coverAll").show();
}

//Помечаем новость как прочитанную
function newsRead(id)
{
    $.post("action.php", {action: 'markNews', id: id},
        function(data) {
            $('.'+id).removeClass();
        }
    );
}