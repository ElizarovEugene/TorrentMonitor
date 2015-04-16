<?php
$dir = dirname(__FILE__).'/';
include_once $dir.'config.php';
include_once $dir.'class/System.class.php';
include_once $dir.'class/Database.class.php';
include_once $dir.'class/Errors.class.php';
include_once $dir.'class/Notification.class.php';
include_once $dir.'class/Update.class.php';

if (isset($_POST['action']))
{
	//Проверяем пароль
	if ($_POST['action'] == 'enter')
	{
		$password = md5($_POST['password']);
		$count = Database::countCredentials($password);
		
		if ($count == 1)
		{
			session_start();
			$_SESSION['TM'] = $password;
			$return['error'] = FALSE;
			if ($POST['remember'])
			    setcookie('hash_pass', $password, time()+3600*24*31);
		}
		else
		{
			$return['error'] = TRUE;
			$return['msg'] = 'Неверный пароль!';
		}
		echo json_encode($return);
	}

	//Добавляем тему для мониторинга
	if ($_POST['action'] == 'torrent_add')
	{
		if ($url = parse_url($_POST['url']))
		{
			$tracker = $url['host'];
			$tracker = preg_replace('/www\./', '', $tracker);
			if ($tracker == 'tr.anidub.com')
				$tracker = 'anidub.com';
				
            if ($tracker == 'new-rutor.org')
				$tracker = 'rutor.org';
			
			if ($tracker == 'anidub.com')
			    $threme = $url['path'];
            elseif ($tracker == 'casstudio.tv')
			{
				$query = explode('=', $url['query']);
				$threme = $query[1];
			}
			elseif ($tracker != 'rutor.org')
			{
				$query = explode('=', $url['query']);
				$threme = $query[1];
			}
			else
			{
				preg_match('/\d{4,8}/', $url['path'], $array);
				$threme = $array[0];
			}
			
			if (is_array(Database::getCredentials($tracker)))
			{
				$engineFile = $dir.'/trackers/'.$tracker.'.engine.php';
				if (file_exists($engineFile))
				{    
					$functionEngine = include_once $engineFile;
					$class = explode('.', $tracker);
					$class = $class[0];
					$functionClass = str_replace('-', '', $class);
					
					if ($tracker == 'tracker.0day.kiev.ua')
					    $functionClass = 'kiev';
					    
                    if ($tracker == 'torrents.net.ua')
					    $functionClass = 'torrentsnet';

					if (call_user_func(array($functionClass, 'checkRule'), $threme))
					{
						if (Database::checkThremExist($tracker, $threme))
						{
							if ( ! empty($_POST['name']))
								$name = $_POST['name'];
							else
								$name = Sys::getHeader($_POST['url']);

							Database::setThreme($tracker, $name, $_POST['path'], $threme);
							?>
							Тема добавлена для мониторинга.
							<?php
						}
						else
						{
						?>
							Вы уже следите за данной темой на трекере <b><?php echo $tracker?></b>.
						<?php
						}
					}
					else
					{
					?>
						Не верная ссылка.
					<?php
					}
				}
				else
				{
				?>
					Отсутствует модуль для трекера - <b><?php echo $tracker?></b>.
				<?php
				}
			}
			else
			{
			?>
				Вы не можете следить за этим сериалом на трекере - <b><?php echo $tracker?></b>, пока не введёте свои учётные данные!
			<?php
			}
		}
		else
		{
		?>
			Не верная ссылка.
		<?php
		}
		return TRUE;
	}
		
	//Добавляем сериал для мониторинга
	if ($_POST['action'] == 'serial_add')
	{
		$tracker = $_POST['tracker'];
		if (is_array(Database::getCredentials($tracker)))
		{
			$engineFile = $dir.'/trackers/'.$tracker.'.engine.php';
			if (file_exists($engineFile))
			{
				$functionEngine = include_once $engineFile;
				$class = explode('.', $tracker);
				$class = $class[0];
				$class = str_replace('-', '', $class);
				if (call_user_func(array($class, 'checkRule'), $_POST['name']))
				{
					if (Database::checkSerialExist($tracker, $_POST['name'], $_POST['hd']))	
					{
						Database::setSerial($tracker, $_POST['name'], $_POST['path'], $_POST['hd']);
						?>
						Сериал добавлен для мониторинга.
						<?php
					}
					else
					{
					?>
						Вы уже следите за данным сериалом на этом трекере - <b><?php echo $tracker?></b>.
					<?php
					}
				}
				else
				{
				?>
					Название содержит недопустимые символы.
				<?php
				}
			}
			else
			{
			?>
				Отсутствует модуль для трекера - <b><?php echo $tracker?></b>.
			<?php
			}
		}
		else
		{
		?>
			Вы не можете следить за этим сериалом на трекере - <b><?php echo $tracker?></b>, пока не введёте свои учётные данные!
		<?php
		}
		return TRUE;
	}
	
	//Обновляем отслеживаемый item
	if ($_POST['action'] == 'update')
	{
	    $tracker = $_POST['tracker'];
	    if ($_POST['reset'] == 'true')
	        $reset = 1;
        else
            $reset = 0;
	    if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv' || $tracker == 'baibako.tv' || $tracker == 'newstudio.tv')
        {
            $engineFile = $dir.'/trackers/'.$tracker.'.engine.php';
            $functionEngine = include_once $engineFile;
			$class = explode('.', $tracker);
			$class = $class[0];
			$class = str_replace('-', '', $class);
			if (call_user_func(array($class, 'checkRule'), $_POST['name']))	
			{
				Database::updateSerial($_POST['id'], $_POST['name'], $_POST['path'], $_POST['hd'], $reset);
				?>
				Сериал обновлён.
				<?php
			}
			else
			{
			?>
			    Название содержит недопустимые символы.
			<?php
            }
        }        
        else
        {
            $url = parse_url($_POST['url']);
            $tracker = $url['host'];
			$tracker = preg_replace('/www\./', '', $tracker);
			
			if ($tracker == 'tr.anidub.com')
				$tracker = 'anidub.com';
				
            if ($tracker == 'alt.rutor.org')
				$tracker = 'rutor.org';
				
            if ($tracker == 'new-rutor.org')
				$tracker = 'rutor.org';
			
			if ($tracker == 'anidub.com')
			    $threme = $url['path'];
			elseif ($tracker != 'rutor.org')
			{
				$query = explode('=', $url['query']);
				$threme = $query[1];
			}
			else
			{
				preg_match('/\d{4,8}/', $url['path'], $array);
				$threme = $array[0];
			}
			
            if ($_POST['update'] == 'true')
    	        $update = 1;
            else
                $update = 0;
		
			$engineFile = $dir.'/trackers/'.$tracker.'.engine.php';
            $functionEngine = include_once $engineFile;
			$class = explode('.', $tracker);
			$class = $class[0];
			$class = str_replace('-', '', $class);
			if (call_user_func(array($class, 'checkRule'), $threme))
			{
				Database::updateThreme($_POST['id'], $_POST['name'], $_POST['path'], $threme, $update, $reset);
				?>
				Тема обновлена.
				<?php
            }
            else
            {
            ?>
                Название содержит недопустимые символы.
            <?php
            }
        }
	}
	
	//Добавляем пользователя для мониторинга
	if ($_POST['action'] == 'user_add')
	{
		$tracker = $_POST['tracker'];
		if (is_array(Database::getCredentials($tracker)))
		{
			$engineFile = $dir.'/trackers/'.$tracker.'.search.php';
			if (file_exists($engineFile))
			{
				if (Database::checkUserExist($tracker, $_POST['name']))	
				{
					Database::setUser($tracker, $_POST['name']);
					?>
					Пользователь добавлен для мониторинга.
					<?php
				}
				else
				{
				?>
					Вы уже следите за данным пользователем на этом трекере - <b><?php echo $tracker?></b>.
				<?php
				}
			}
			else
			{
			?>
				Отсутствует модуль для трекера - <b><?php echo $tracker?></b>.
			<?php
			}
		}
		else
		{
		?>
			Вы не можете следить за этим пользователем на трекере - <b><?php echo $tracker?></b>, пока не введёте свои учётные данные!
		<?php
		}
		return TRUE;
	}
	
	//Удаляем пользователя из мониторинга и все его темы
	if ($_POST['action'] == 'delete_user')
	{
    	Database::deletUser($_POST['user_id']);
    	?>
		Удаляю...
		<?php
		return TRUE;
	}
	
	//Удаляем тему из буфера
	if ($_POST['action'] == 'delete_from_buffer')
	{
    	Database::deleteFromBuffer($_POST['id']);
    	?>
		Удаляю...
		<?php
		return TRUE;
	}
	
	//Очищаем весь список тем
	if ($_POST['action'] == 'threme_clear')
	{
    	$array = Database::selectAllFromBuffer();
    	for($i=0; $i<count($array); $i++)
    	{
        	Database::deleteFromBuffer($array[$i]['id']);
    	}
        return TRUE;
	}	
	
	//Перемещаем тему из буфера в мониторинг постоянный
	if ($_POST['action'] == 'transfer_from_buffer')
	{
    	Database::transferFromBuffer($_POST['id']);
    	?>
		Переношу...
		<?php
		return TRUE;
	}
	
	//Помечаем тему для скачивания
	if ($_POST['action'] == 'threme_add')
	{
		$update = Database::updateThremesToDownload($_POST['id']);
		if ($update)
		{
			$return['error'] = FALSE;
		}
		else
		{		
			$return['error'] = TRUE;
			$return['msg'] = 'Пометить тему для закачки.';
		}
		echo json_encode($return);
	}
	
	//Удаляем мониторинг
	if ($_POST['action'] == 'del')
	{
		Database::deletItem($_POST['id']);
		?>
		Удаляю...
		<?php
		return TRUE;
	}
	
	//Обновляем личные данные
	if ($_POST['action'] == 'update_credentials')
	{
    	if ( ! isset($_POST['passkey']))
    	    $_POST['passkey'] = '';
		Database::setCredentials($_POST['id'], $_POST['log'], $_POST['pass'], $_POST['passkey']);
		?>
		Данные для трекера обновлены!
		<?php
		return TRUE;
	}
	
	//Обновляем настройки
	if ($_POST['action'] == 'update_settings')
	{
		Database::updateSettings('serverAddress', Sys::checkPath($_POST['serverAddress']));
		if ($_POST['send'] == 'true')
		    $send = 1;
        else
            $send = 0;
		Database::updateSettings('send', $send);
        if ($_POST['sendUpdate'] == 'true')
		    $sendUpdate = 1;
        else
            $sendUpdate = 0;		
		Database::updateSettings('sendUpdate', $sendUpdate);
		Database::updateSettings('sendUpdateEmail', $_POST['sendUpdateEmail']);
		Database::updateSettings('sendUpdatePushover', $_POST['sendUpdatePushover']);
        if ($_POST['sendWarning'] == 'true')
		    $sendWarning = 1;
        else
            $sendWarning = 0;		
		Database::updateSettings('sendWarning', $sendWarning);		
		Database::updateSettings('sendWarningEmail', $_POST['sendWarningEmail']);
		Database::updateSettings('sendWarningPushover', $_POST['sendWarningPushover']);
        if ($_POST['auth'] == 'true')
		    $auth = 1;
        else
            $auth = 0;		
		Database::updateSettings('auth', $auth);
        if ($_POST['proxy'] == 'true')
		    $proxy = 1;
        else
            $proxy = 0;		
		Database::updateSettings('proxy', $proxy);
		Database::updateSettings('proxyAddress', $_POST['proxyAddress']);
        if ($_POST['torrent'] == 'true')
		    $torrent = 1;
        else
            $torrent = 0;		
        Database::updateSettings('useTorrent', $torrent);
        Database::updateSettings('torrentClient', $_POST['torrentClient']);
        Database::updateSettings('torrentAddress', $_POST['torrentAddress']);
        Database::updateSettings('torrentLogin', $_POST['torrentLogin']);
        Database::updateSettings('torrentPassword', $_POST['torrentPassword']);
        Database::updateSettings('pathToDownload', Sys::checkPath($_POST['pathToDownload']));
        if ($_POST['deleteDistribution'] == 'true')
		    $deleteDistribution = 1;
        else
            $deleteDistribution = 0;		
        Database::updateSettings('deleteDistribution', $deleteDistribution);
        if ($_POST['deleteOldFiles'] == 'true')
		    $deleteOldFiles = 1;
        else
            $deleteOldFiles = 0;		
        Database::updateSettings('deleteOldFiles', $deleteOldFiles);
        if ($_POST['rss'] == 'true')
		    $rss = 1;
        else
            $rss = 0;		
        Database::updateSettings('rss', $rss);
        if ($_POST['debug'] == 'true')
		    $debug = 1;
        else
            $debug = 0;		
        Database::updateSettings('debug', $debug);
		?>
		Настройки монитора обновлены.
		<?php
		return TRUE;
	}
	
	//Меняем пароль
	if ($_POST['action'] == 'change_pass')
	{
		$pass = md5($_POST['pass']);
		$q = Database::updateCredentials($pass);
		if ($q)
		{
			$return['error'] = FALSE;
		}
		else
		{
			$return['error'] = TRUE;
			$return['msg'] = 'Не удалось сменить пароль!';
		}
		echo json_encode($return);
	}
	
	//Добавляем тему на закачку
	if ($_POST['action'] == 'download_thremes')
	{
		if ( ! empty($_POST['checkbox']))
		{
			$arr = $_POST['checkbox'];
			foreach ($arr as $id => $val)
			{
				Database::updateDownloadThreme($id);
			}
			echo count($arr).' тем помечено для закачки.';
			return TRUE;
		}
		Database::updateDownloadThremeNew();
	}
	
    //Помечаем новость как прочитанную
	if ($_POST['action'] == 'markNews')
	{
		Database::markNews($_POST['id']);
		return TRUE;
	}
	
	//Выполняем обновление системы
	if ($_POST['action'] == 'system_update')
	{
		Update::runUpdate();
		return TRUE;
	}

}

if (isset($_GET['action']))
{
	//Сортировка вывода торрентов
	if ($_GET['action'] == 'order')
	{
		session_start();
		if ($_GET['order'] == 'date')
			setcookie('order', 'date', time()+3600*24*365);
		elseif ($_GET['order'] == 'dateDesc')
			setcookie('order', 'dateDesc', time()+3600*24*365);			
		elseif ($_GET['order'] == 'name')
			setcookie('order', '', time()+3600*24*365);
		header('Location: index.php');
	}	
}
?>

