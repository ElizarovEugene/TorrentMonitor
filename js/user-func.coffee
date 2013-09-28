$ () ->
	$(".h-menu li").hover((() ->
		$(this).stop().animate {width: "235px"}, 500),()->
		if $(this).hasClass "active" ==false then $(this).stop().animate {width: "27px"}, 500)

	$(".user-torrent").click () ->
		$(this).toggleClass "active"
		$(this).next().toggle()
		return

show = (name)->
	$.get  "include/#{name}.php", (data)->
		$('#content').empty().append data
		return
	window.clearTimeout this.timeoutID
	if name == 'show_table'  
		this.timeoutID = window.setTimeout (()->
			show('show_table')),7000
	else if  name == 'show_warnings'
		this.timeoutID = window.setTimeout (()->
			show('show_table')
			return),7000
	else
		delete this.timeoutID
	return false

expand = (id) ->
	div = "##{id}"
	if $(div).is ":hidden" then  $(div).slideDown "slow" else $(div).slideUp "slow"
	return false

$("#enter").submit ()->
	$form = $(this).p = $form.find('input[name="password"]').val()
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn()
	$.post "action.php",{action: 'enter', password: p},((data)->
		if data.error then $('#notice').empty().attr('background', '#FF6633').append(data.msg).delay(3000).fadeOut(400) else document.location.reload()
		console.log data.error
		return), "json"
	return false

$("#torrent_add").submit ()->
	$form = $(this)
	s = $form.find 'input[type=submit]'
	n_f = $form.find 'input[name="name"]'
	n = $(n_f).val()
	u_f = $form.find 'input[name="url"]'
	u = $(u_f).val()

	$(s).attr 'disabled', 'disabled'
	if u!=''
		$('#notice').empty().append('Обрабатывается запрос...').fadeIn()
		$.post "action.php",{action: 'torrent_add', name: n, url: u},(data)->
			$('#notice').empty().append(data).delay(3000).fadeOut(400)
			$(s).removeAttr 'disabled' 
			$(n_f).val ''
			$(u_f).val ''
			return
	else
		alert "Вы не указали ссылку на тему!"
		$(s).removeAttr 'disabled'
	return false

$("#serial_add").submit () ->
	$form = $(this)
	s = $form.find('input[type=submit]')
	t = $form.find('select[name="tracker"]').val()
	n_f = $form.find('input[name="name"]')
	n = $(n_f).val()
	h_f = $form.find('input[name="hd"]')

	if t=='novafilm.tv'
		h = $(h_f).attr('checked')
		$form = $(this)
		h = if h == 'checked' then 1 else 0
	if t == 'lostfilm.tv'
		h = $(h_f).val()
		for i in [0..h_f.length]
			if h_f[i].checked
				$form = $(this)
				h=h_f[i].value
	$(s).attr('disabled', 'disabled')
	if t !=''&&n!=''
		$('#notice').empty().append('Обрабатывается запрос...').fadeIn()
		$.post "action.php",{action: 'serial_add', tracker: t, name: n, hd: h},(data)->
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400)
			$(s).removeAttr('disabled')
			$(n_f).val('')
			$(h_f).removeAttr('checked')
			return
	else
		if t==''
			alert "Вы не выбрали трекер!"
			$(s).removeAttr 'disabled'
		if n==''
			alert "Вы не указали название сериала!"
			$(s).removeAttr 'disabled'
	return false

$("#user_add").submit ()->
	$form = $(this)
	s = $form.find('input[type=submit]')
	t = $form.find('select[name="tracker"]').val()
	n_f = $form.find('input[name="name"]')
	n = $(n_f).val()

	$(s).attr('disabled', 'disabled')
	if t !=''&&n!=''
		$('#notice').empty().append('Обрабатывается запрос...').fadeIn()
		$.post "action.php",{action: 'serial_add', tracker: t, name: n, hd: h},(data)->
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400)
			$(s).removeAttr('disabled')
			$(n_f).val('')
			$(h_f).removeAttr('checked')
			return
	else
		if t==''
			alert "Вы не выбрали трекер!"
			$(s).removeAttr 'disabled'
		if n==''
			alert "Вы не указали имя пользователя!"
			$(s).removeAttr 'disabled'
	return false
_postAction = (actionStruct,callback,format=null)-> 
	$.post "action.php",actionStruct,callback, if format!=null then format
	return
_internalAction = (actionStruct)->
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn()
	_postAction actionStruct, (data)->
		$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400);
		$.get "include/show_watching.php", (data)->
			$('#content').delay(3000).empty().append data
			return
	return false


delete_user = (id) -> 
	_internalAction  {action: 'delete_user', user_id: id}
	return

delete_from_buffer = (id)->
	_internalAction {action: 'delete_from_buffer', id: id}
	return

transfer_from_buffer = (id)->
	_internalAction {action: 'transfer_from_buffer', id: id}
	return

theme_add = (id, user_id) ->
	_postAction {action: 'threme_add', id: id, user_id: user_id},(data)->
		if data.error
			$('#notice').empty().attr('background', '#FF6633').append('Ошибка передачи данных<br/>Попробуйте ещё раз.').delay(3000).fadeOut(400)
			return
		else
			$.get "include/show_watching.php", ((data)->
				$('#content').empty().append data
				return false),"json"
			return
	return false

$("#theme_clear").submit ()-> 
	_internalAction {action: 'threme_clear'}
	return

$("#credential").submit ()->
	$form = $(this)
	b = $form.find('input[type=button]')
	id = $form.find('input[name="id"]').val()
	l = $form.find('input[name="log"]').val()
	p = $form.find('input[name="pass"]').val()

	$(b).attr 'disabled', 'disabled'
	if l!=''&& p!=''
		$('#notice').empty().append('Обрабатывается запрос...').fadeIn()
		_postAction {action: 'update_credentials', id: id, log: l, pass: p},(data)->
			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400)
			$(b).removeAttr 'disabled'
			return
	else
		if l==''
			alert "Вы не указали логин!"
		if p==''
			alert "Вы не указали пароль!"
		$(b).removeAttr 'disabled'
		return
	return false

$("#setting").submit ()->
	$form = $(this)
	s = $form.find('input[type=submit]')
	p = $form.find('input[name="path"]').val()
	e = $form.find('input[name="email"]').val()
	s = $form.find('input[name="send"]').attr('checked')
	s_w = $form.find('input[name="send_warning"]').attr('checked')
	a = $form.find('input[name="auth"]').attr('checked')

	$(b).attr 'disabled', 'disabled'
	$('#notice').empty().append('Обрабатывается запрос...').fadeIn()
	_postAction {action: 'update_settings', path: p, email: e, send: s, send_warning: s_w, auth: a},(data)->
		$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400)
		$(s).removeAttr 'disabled'
		return
	return false

$("#change_pass").submit ()->
	$form = $(this)
	s = $form.find('input[type=submit]')
	p = $form.find('input[name="password"]').val()
	p2 = $form.find('input[name="password2"]').val()

	$(b).attr 'disabled', 'disabled'
	if p!=''
		if p!=p2
		 	alert 'Пароль и подтверждение должны совпадать.'
		 	$(s).removeAttr 'disabled'
		else
		 	$('#notice').empty().append('Обрабатывается запрос...').fadeIn()
		 	_postAction {action: 'change_pass', pass: p}((data)->
		 		if data.error 
		 			$('#notice').empty().attr('background', '#FF6633').append(data).delay(3000).fadeOut(400) 
		 			return 
		 		else 
		 			document.location.reload()
		 			return),"json"
	else
		alert 'Пароль не может быть пустым.'
		$(s).removeAttr 'disabled'
	return false

del = (id) -> 
	_internalAction {action: 'del', id: id}
	return

changefunc = () ->
	select = document.getElementById "selectfunc"
	selectedText = select.options[select.selectedIndex].text
	for tracker in ['kinozal.tv', 'lostfilm.tv', 'nnm-club.me', 'novafilm.tv', 'rutracker.org']
		d = if selectedText == tracker then "block" else "none"
		document.getElementById("#{tracker}_label").style.display = d
	return

changeField = () ->
	tracker = document.getElementById("tracker").value
	if tracker == 'lostfilm.tv' 
		$('#changedField').empty().append '<span class="quality"><input type="radio" name="hd" value="0"> SD качество<br/><input type="radio" name="hd" value="1"> HD качество<br/><input type="radio" name="hd" value="2"> HD MP4</span>' 
	if tracker =  'novafilm.tv'
		$('#changedField').empty().append '<input type="checkbox" name="hd"> HD качество</label>'
	return
