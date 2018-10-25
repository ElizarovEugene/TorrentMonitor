$( document ).ready(function() 
{   // Скользящее меню
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

    // Меню
    $(".h-menu li").click(function() {
        $(".h-menu li").stop().animate({width: "27px"}, 500);
        $(".h-menu li").removeClass("active");
        $(this).stop().animate({width: "235px"}, 500);
        $(this).addClass("active");
    });

    //Передаём пароль
    $("#enter").submit(function() {
        var $form = $(this),p = $form.find('input[name="password"]').val(),
            r_m = $form.find('input[name="remember"]').prop('checked');
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'enter', password: p, remember: r_m},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
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
            p = $(p_f).val(),
            u_h = $form.find('input[name="update_header"]').prop('checked');

        if (u == '')
        {
            ohSnap('Вы не указали ссылку на тему!', 'red');
            return false;
        }
                                    
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'torrent_add', name: n, url: u, path: p, update_header: u_h},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $(n_f).val('');
                    $(u_f).val('');
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });

    //Передаём сериал для мониторинга
    $("#serial_add").submit(function()
    {
        formError = "";
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            t = $form.find('select[name="tracker"]').val(),
            n_f = $form.find('input[name="name"]'),
            n = $(n_f).val(),
            h_f = $form.find('input[name="hd"]'),
            p_f = $form.find('input[name="path"]'),
            p = $(p_f).val();

        h = $(h_f).val();
        qualitySelected = false;
        for (var i = 0; i < h_f.length; i++)
        {
            if (h_f[i].checked)
            {
                var $form = $(this), h = h_f[i].value;
                qualitySelected = true;
            }
        }

        if (t == '')
            formError += "Вы не выбрали трекер!<br>";
        
        if (n == '')
            formError += "Вы не указали название сериала!<br>";

        if (!qualitySelected)
            formError += "Вы не выбрали качество!";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }

        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'serial_add', tracker: t, name: n, hd: h, path: p},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $(n_f).val('');
                    $(h_f).removeAttr('checked');
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });
    
    //Передаём данные для обновления
    $("#torrent_update").submit(function()
    {
        formError = "";
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
            s_f = $form.find('input[name="script"]'),
            s = $(s_f).val();
            h_f = $form.find('input[name="hd"]'),
            pause_f = $form.find('input[name="pause"]').prop('checked'),
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
            formError += "Вы не указали ссылку на тему!\n";
        
        if (n == '')
            formError += "Вы не указали название сериала!";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }

        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'update', id: id, tracker: t, name: n, url: u, update: update, path: p, script: s, hd: h, pause: pause_f, reset: r_f},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $.get("include/show_table.php",
            		    function(data) {
            			    $('#content').delay(3000).empty().append(data);
            		    }
                    );                    
                    $('.coverAll').hide();
                    $('.blok').empty();
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );

        return false;
    });    

    //Передаём пользователя для мониторинга
    $("#user_add").submit(function()
    {
        formError = "";
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            t = $form.find('select[name="tracker"]').val(),
            n_f = $form.find('input[name="name"]'),
            n = $(n_f).val();

        if (t == '')
            formError += "Вы не выбрали трекер!\n";
        
        if (n == '')
            formError += "Вы не указали имя пользователя!";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }
        
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'user_add', tracker: t, name: n},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    ohSnap(data.msg, 'green');
                    $(n_f).val('');
                }
            }, "json"
        );
        return false;
    });
    
    //Удаляем темы 
    $("#threme_clear").submit(function()
    {
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'threme_clear'},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    ohSnap(data.msg, 'green');
                    $.get("include/show_watching.php",
                        function(data) {
                            $('#content').empty().append(data);
                        }
                    );
                }
            }, "json"
        );
        return false;
    });

    //Передаём личные данные
    $("#credential").submit(function()
    {
        formError = "";
        var $form = $(this),
            b = $form.find('input[type=button]'),
            id = $form.find('input[name="id"]').val(),
            l = $form.find('input[name="log"]').val(),
            p = $form.find('input[name="pass"]').val(),
            passkey = $form.find('input[name="passkey"]').val();

        if (l == '')
            formError += "Вы не указали логин!\n";
        
        if (p == '')
            formError += "Вы не указали пароль!";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }
                                    
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'update_credentials', id: id, log: l, pass: p, passkey: passkey},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $(b).removeAttr('disabled');
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });
        
    //Передаём настройки
    $("#setting").submit(function()
    {
        formError = "";
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            serverAddress = $form.find('input[name="serverAddress"]').val();
            send = $form.find('input[name="send"]').prop('checked');
            sendUpdate = $form.find('input[name="sendUpdate"]').prop('checked');
            sendWarning = $form.find('input[name="sendWarning"]').prop('checked');
            sendUpdateService = $form.find('select[name="sendnotificationService"]').val();
            sendUpdateAddress = $form.find('input[name="sendnotificationAddress'+sendUpdateService+'"]').val();
            sendWarningService = $form.find('select[name="sendwarningService"]').val();
            sendWarningAddress = $form.find('input[name="sendwarningAddress'+sendWarningService+'"]').val();
            auth = $form.find('input[name="auth"]').prop('checked');
            proxy = $form.find('input[name="proxy"]').prop('checked');
            proxyType = $form.find('select[name="proxyType"]').val();
            proxyAddress = $form.find('input[name="proxyAddress"]').val();
            torrent = $form.find('input[name="torrent"]').prop('checked');
            torrentClient = $form.find('select[name="torrentClient"]').val();
            torrentAddress = $form.find('input[name="torrentAddress"]').val();
            torrentLogin = $form.find('input[name="torrentLogin"]').val();
            torrentPassword = $form.find('input[name="torrentPassword"]').val();
            pathToDownload = $form.find('input[name="pathToDownload"]').val();
            deleteDistribution = $form.find('input[name="deleteDistribution"]').prop('checked');
            deleteOldFiles = $form.find('input[name="deleteOldFiles"]').prop('checked');
            rss = $form.find('input[name="rss"]').prop('checked');
            autoUpdate = $form.find('input[name="autoUpdate"]').prop('checked');
            debug = $form.find('input[name="debug"]').prop('checked');

        if (serverAddress == '')
            formError += "Вы не указали адрес сервера TM.\n";
        
        if (proxy == 'checked' && proxyAddress == '')
            formError += "Вы не указали адрес proxy-сервера.\n";
        
        if (torrent == 'checked' && torrentClient == ''  && torrentAddress == '' && pathToDownload == '')
            formError += "Вы не указали настройки торрент-клиента.";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }

        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'update_settings', serverAddress: serverAddress, send: send, sendUpdate: sendUpdate, sendWarning: sendWarning, sendUpdateService: sendUpdateService, sendUpdateAddress: sendUpdateAddress, sendWarningService: sendWarningService, sendWarningAddress: sendWarningAddress, auth: auth, proxy: proxy, proxyType: proxyType, proxyAddress: proxyAddress, torrent: torrent, torrentClient: torrentClient, torrentAddress: torrentAddress, torrentLogin: torrentLogin, torrentPassword: torrentPassword, pathToDownload: pathToDownload, deleteDistribution: deleteDistribution, deleteOldFiles: deleteOldFiles, rss: rss, autoUpdate: autoUpdate, debug: debug},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });

    //Передаём пароль
    $("#change_pass").submit(function()
    {
        formError = "";
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            p = $form.find('input[name="password"]').val(),
            p2 = $form.find('input[name="password2"]').val();
            
        if (p == '')
            formError += "Пароль не может быть пустым.\n";
        
        if (p != p2) 
            formError += "Пароль и подтверждение должны совпадать.";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }
        
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'change_pass', pass: p},
            function(data) {
                if (data.error)
                {
                    ohSnap(data, 'green');
                }
                else
                    document.location.reload();
            }, "json"
        );
        return false;
    });
    
    //Передаём расширенные настройки
    $("#extended_settings").submit(function()
    {
        //console.log('extended_settings');
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            extended_settings = $form.find('textarea[name="settings"]').val();
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'update_extended_settings', settings: extended_settings},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });    
    
    //Вызов процедуры обновления
    $("#system_update").submit(function()
    {
        $.post("action.php",{action: 'system_update'},
            function(data) {
                $('#system_update').empty().html(data);
            }
        );
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
    
    if (name != 'show_watching')
        document.cookie = 'userID=undefined;';
    
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
    	ohSnap('Обрабатывается запрос...', 'yellow');
    	$.post("action.php",{action: 'delete_user', user_id: id},
    		function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $.get("include/show_watching.php",
            		    function(data) {
            			    $('#content').delay(3000).empty().append(data);
            		    }
                    );
                    ohSnap(data.msg, 'green');
                }
            }, "json"
    	);
    	return false;
    }
}

//Помечаем темы пользователя как просмотренные
function thremes_clear(id)
{
    if (confirm("Пометить как просмотренные?"))
    {
    	ohSnap('Обрабатывается запрос...', 'yellow');
    	$.post("action.php",{action: 'thremes_clear', user_id: id},
    		function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $.get("include/show_watching.php",
            		    function(data) {
            			    $('#content').delay(3000).empty().append(data);
            		    }
                    );
                    ohSnap(data.msg, 'green');
                }
            }, "json"
    	);
    	return false;
    }
}

//Удаляем тему из буфера
function delete_from_buffer(id)
{
    if (confirm("Удалить?"))
    {
    	ohSnap('Обрабатывается запрос...', 'yellow');
    	$.post("action.php",{action: 'delete_from_buffer', id: id},
    		function(data) {
    			if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $.get("include/show_watching.php",
            		    function(data) {
            			    $('#content').delay(3000).empty().append(data);
            		    }
                    );
                    ohSnap(data.msg, 'green');
                }
            }, "json"
    	);
    	return false;
    }
}

//Перемещаем тему из буфера в мониторинг постоянный
function transfer_from_buffer(id)
{
	ohSnap('Обрабатывается запрос...', 'yellow');
	$.post("action.php",{action: 'transfer_from_buffer', id: id},
		function(data) {
			if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $.get("include/show_watching.php",
            		    function(data) {
            			    $('#content').delay(3000).empty().append(data);
            		    }
                    );
                    ohSnap(data.msg, 'green');
                }
            }, "json"
	);
	return false;

}

//Передаём темы для скачивания
function threme_add(id, user_id)
{
    ohSnap('Обрабатывается запрос...', 'yellow');
	$.post("action.php",{action: 'threme_add', id: id, user_id: user_id},
		function(data) {
			if (data.error)
			{
                ohSnap('Ошибка передачи данных<br/>Попробуйте ещё раз.', 'red');
			}
			else
			{
				$.get("include/show_watching.php",
					function(data) {
						$('#content').empty().append(data);
					}
				);
                ohSnap(data.msg, 'green');
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
    	ohSnap('Обрабатывается запрос...', 'yellow');
    	$.post("action.php",{action: 'del', id: id},
    		function(data) {
        		if (data.error)
    			{
                    ohSnap(data.msg, 'red');
    			}
    			else
    			{
    				$.get("include/show_table.php",
    					function(data) {
    						$('#content').empty().append(data);
    					}
    				);
                    ohSnap(data.msg, 'green');
    			}
    		}, "json"
    	);
    	return false;
    }
}

//Меняем radiobutton
function changeField()
{
	var tracker = document.getElementById("tracker").value;
    if (tracker == 'baibako.tv' || tracker == 'hamsterstudio.org' || tracker == 'newstudio.tv')
        $('#changedField').empty().append('<span class="quality"><input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1"> HD 720<br /><input type="radio" name="hd" value="2"> HD 1080</span>');
	if (tracker == 'lostfilm.tv' || tracker == 'lostfilm-mirror')
		$('#changedField').empty().append('<span class="quality"><input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="2"> HD 720 MP4<br /><input type="radio" name="hd" value="1"> HD 1080');
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