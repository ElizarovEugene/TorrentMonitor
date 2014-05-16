<?php
$dir = dirname(__FILE__).'/';
include_once $dir.'config.php';
include_once $dir.'class/System.class.php';
include_once $dir.'class/Database.class.php';
include_once $dir.'class/Errors.class.php';
include_once $dir.'class/Notification.class.php';

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
			
			if ($tracker != 'new-rutor.org')
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
		Database::setCredentials($_POST['id'], $_POST['log'], $_POST['pass']);
		?>
		Данные для трекера обновлены!
		<?php
		return TRUE;
	}
	
	//Обновляем настройки
	if ($_POST['action'] == 'update_settings')
	{
		$path = Sys::checkPath($_POST['path']);
		Database::updateSettings('path', $path);
		Database::updateSettings('email', $_POST['email']);

		if ($_POST['send'] == 'true')
			$send = 1;
		else 
			$send = 0;
		Database::updateSettings('send', $send);
		
		if ($_POST['send_warning'] == 'true')
			$send_warning = 1;
		else 
			$send_warning = 0;
		Database::updateSettings('send_warning', $send_warning);
		
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
        $pathToDownload = Sys::checkPath($_POST['pathToDownload']);
        Database::updateSettings('pathToDownload', $pathToDownload);
        
        if ($_POST['deleteTorrent'] == 'true')
			$deleteTorrent = 1;
		else 
			$deleteTorrent = 0;
        Database::updateSettings('deleteTorrent', $deleteTorrent);
        
        if ($_POST['deleteOldFiles'] == 'true')
			$deleteOldFiles = 1;
		else 
			$deleteOldFiles = 0;
        Database::updateSettings('deleteOldFiles', $deleteOldFiles);
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
}

if (isset($_GET['action']))
{
	//Сортировка вывода торрентов
	if ($_GET['action'] == 'order')
	{
		session_start();
		if ($_GET['order'] == 'date')
			$_SESSION['order'] = 'date';
		elseif ($_GET['order'] == 'dateDesc')
			$_SESSION['order'] = 'dateDesc';			
		elseif ($_GET['order'] == 'name')
			unset($_SESSION['order']);
		header('Location: index.php');
	}	
}
?>

