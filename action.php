<?php
$dir = dirname(__FILE__).'/';
include_once $dir.'config.php';
include_once $dir.'class/Database.class.php';
include_once $dir.'class/Errors.class.php';
include_once $dir.'class/Notification.class.php';
include_once $dir.'class/System.class.php';
include_once $dir."class/Url.class.php";

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
			if ($_POST['remember'] == 'true')
			    setcookie('TM', $password, time()+3600*24*31, '/');
		}
		else
		{
			$return['error'] = TRUE;
			$return['msg'] = 'Неверный пароль!';
		}
		echo json_encode($return);
	}

    if ( ! Sys::checkAuth())
        exit();

	//Добавляем тему для мониторинга
	if ($_POST['action'] == 'torrent_add')
	{
		if ($url = parse_url($_POST['url']))
		{
			$tracker = $url['host'];
			$tracker = preg_replace('/www\./', '', $tracker);

			if ($tracker == 'lostfilm.tv' || $tracker == 'lostfilm-mirror' || $tracker == 'newstudio.tv')
			{
                $return['error'] = TRUE;
                $return['msg'] = 'Это не форумный трекер. Добавьте как Сериал по его названию.';
            }
            else
            {
    			if ($tracker == 'tr.anidub.com')
    				$tracker = 'anidub.com';
                elseif ($tracker == 'baibako.tv')
    				$tracker = 'baibako.tv_forum';

    			if ($tracker == 'anidub.com' || $tracker == 'riperam.org')
    			    $threme = $url['path'];
                elseif ($tracker == 'animelayer.ru')
                {
                    $path = str_replace('/torrent', '', $url['path']);
                    preg_match('/\/(\w*)\/?/', $path, $array);
                    $threme = $array[1];
                }
                elseif ($tracker == 'casstudio.tk')
    			{
    				$query = explode('t=', $url['query']);
    				$threme = $query[1];
    			}
    			elseif ($tracker != 'rutor.is')
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

                        if ($tracker == 'tv.mekc.info')
    					    $functionClass = 'mekc';

						if ($tracker == 'baibako.tv_forum')
    					    $functionClass = 'baibako_f';

                        if ( ! empty($threme))
                        {
        					if (call_user_func(array($functionClass, 'checkRule'), $threme))
        					{
        						if (Database::checkThremExist($tracker, $threme))
        						{
        							if ( ! empty($_POST['name']))
        								$name = $_POST['name'];
        							else
        								$name = Sys::getHeader($_POST['url']);

        							$query = Database::setThreme($tracker, $name, $_POST['path'], $threme, Sys::strBoolToInt($_POST['update_header']));
        							if ($query === TRUE)
                                    {
            							$return['error'] = FALSE;
                                        $return['msg'] = 'Тема добавлена для мониторинга.';
                                    }
                                    else
                                    {
                                        $return['error'] = TRUE;
                                        $return['msg'] = 'Произошла ошибка при сохранении в БД.'.var_dump($query);
                                    }
        						}
        						else
        						{
            						$return['error'] = TRUE;
                                    $return['msg'] = 'Вы уже следите за данной темой на трекере <b>'.$tracker.'</b>.';
        						}
        					}
        					else
        					{
        					    $return['error'] = TRUE;
                                $return['msg'] = 'Неверная ссылка.';
        					}
        				}
        				else
    					{
    					    $return['error'] = TRUE;
                            $return['msg'] = 'Неверная ссылка.';
    					}
    				}
    				else
    				{
        				$return['error'] = TRUE;
                        $return['msg'] = 'Отсутствует модуль для трекера - <b>'.$tracker.'</b>.';
    				}
    			}
    			else
    			{
        			$return['error'] = TRUE;
                    $return['msg'] = 'Вы не можете следить за этим сериалом на трекере - <b>'.$tracker.'</b>, пока не введёте свои учётные данные!';
    			}
            }
		}
		else
		{
    		$return['error'] = TRUE;
            $return['msg'] = 'Не верная ссылка.';
		}
		echo json_encode($return);
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
				if (Database::checkSerialExist($tracker, $_POST['name'], $_POST['hd']))
				{
					$query = Database::setSerial($tracker, $_POST['name'], $_POST['path'], $_POST['hd']);
					if ($query === TRUE)
					{
					    $return['error'] = FALSE;
                        $return['msg'] = 'Сериал добавлен для мониторинга.';
                    }
                    else
                    {
                        $return['error'] = TRUE;
                        $return['msg'] = 'Произошла ошибка при сохранении в БД.'.var_dump($query);
                    }
				}
				else
				{
					$return['error'] = TRUE;
                    $return['msg'] = 'Вы уже следите за данным сериалом на этом трекере - <b>'.$tracker.'</b>.';
				}
			}
			else
			{
				$return['error'] = TRUE;
                $return['msg'] = 'Отсутствует модуль для трекера - <b>'.$tracker.'</b>.';
			}
		}
		else
		{
			$return['error'] = TRUE;
            $return['msg'] = 'Вы не можете следить за этим сериалом на трекере - <b>'.$tracker.'</b>, пока не введёте свои учётные данные!';
		}

		echo json_encode($return);
	}

	//Обновляем отслеживаемый item
	if ($_POST['action'] == 'update')
	{
	    $tracker = $_POST['tracker'];
	    if ($tracker == 'lostfilm.tv' || $tracker == 'lostfilm-mirror'  || $tracker == 'newstudio.tv' || $tracker == 'baibako.tv')
        {
            $engineFile = $dir.'/trackers/'.$tracker.'.engine.php';
            $functionEngine = include_once $engineFile;
			$class = explode('.', $tracker);
			$class = $class[0];
			$class = str_replace('-', '', $class);
			Database::updateSerial($_POST['id'], $_POST['name'], $_POST['path'], $_POST['hd'], Sys::strBoolToInt($_POST['reset']), $_POST['script'], Sys::strBoolToInt($_POST['pause']));
			$return['error'] = FALSE;
            $return['msg'] = 'Сериал обновлён.';
        }
        else
        {
    		if ($url = parse_url($_POST['url']))
    		{
    			$tracker = $url['host'];
    			$tracker = preg_replace('/www\./', '', $tracker);
    			if ($tracker == 'tr.anidub.com')
    				$tracker = 'anidub.com';
				elseif ($tracker == 'baibako.tv')
    				$tracker = 'baibako.tv_forum';

    			if ($tracker == 'anidub.com' || $tracker == 'riperam.org')
    			    $threme = $url['path'];
                elseif ($tracker == 'animelayer.ru')
                {
                    $path = str_replace('/torrent', '', $url['path']);
                    preg_match('/\/(.*)\/?/', $path, $array);
                    $threme = $array[1];
                }
                elseif ($tracker == 'casstudio.tk')
    			{
    				$query = explode('=', $url['query']);
    				$threme = $query[1];
    			}
    			elseif ($tracker != 'rutor.is')
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

                        if ($tracker == 'tv.mekc.info')
    					    $functionClass = 'mekc';

						if ($tracker == 'baibako.tv_forum')
    					    $functionClass = 'baibako_f';

                        if ( ! empty($threme))
                        {
        					if (call_user_func(array($functionClass, 'checkRule'), $threme))
                			{
                				Database::updateThreme($_POST['id'], $_POST['name'], $_POST['path'], $threme, Sys::strBoolToInt($_POST['update']), Sys::strBoolToInt($_POST['reset']), $_POST['script'], Sys::strBoolToInt($_POST['pause']));
                				$return['error'] = FALSE;
                                $return['msg'] = 'Тема обновлена.';
                            }
                            else
                            {
                				$return['error'] = TRUE;
                                $return['msg'] = 'Не верный ID темы.';
                            }
                        }
                    }
                }
            }
        }
        echo json_encode($return);
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
					$return['error'] = FALSE;
                    $return['msg'] = 'Пользователь добавлен для мониторинга.';
				}
				else
				{
                    $return['error'] = TRUE;
                    $return['msg'] = 'Вы уже следите за данным пользователем на этом трекере - <b>'.$tracker.'</b>.';
				}
			}
			else
			{
    			$return['error'] = TRUE;
                $return['msg'] = 'Отсутствует модуль для трекера - <b>'.$tracker.'</b>.';
			}
		}
		else
		{
    		$return['error'] = TRUE;
            $return['msg'] = 'Вы не можете следить за этим пользователем на трекере - <b>'.$tracker.'</b>, пока не введёте свои учётные данные!';
		}
		echo json_encode($return);
	}

	//Удаляем пользователя из мониторинга и все его темы
	if ($_POST['action'] == 'delete_user')
	{
    	Database::deletUser($_POST['user_id']);
    	$return['error'] = FALSE;
        $return['msg'] = 'Слежение за пользователем удалено.';
        echo json_encode($return);
	}

	//Удаляем тему из буфера
	if ($_POST['action'] == 'delete_from_buffer')
	{
    	Database::deleteFromBuffer($_POST['id']);
    	$return['error'] = FALSE;
        $return['msg'] = 'Тема удалена из буфера.';
        echo json_encode($return);
	}

	//Очищаем весь список тем
	if ($_POST['action'] == 'thremes_clear')
	{
    	Database::thremesClear($_POST['user_id']);
    	$return['error'] = FALSE;
        $return['msg'] = 'Буфер очищен.';
        echo json_encode($return);
	}

	//Перемещаем тему из буфера в мониторинг постоянный
	if ($_POST['action'] == 'transfer_from_buffer')
	{
    	Database::transferFromBuffer($_POST['id']);
    	$return['error'] = FALSE;
        $return['msg'] = 'Тема перенесена из буфера.';
        echo json_encode($return);
	}

	//Помечаем тему для скачивания
	if ($_POST['action'] == 'threme_add')
	{
		$update = Database::updateThremesToDownload($_POST['id']);
		if ($update)
		{
			$return['error'] = FALSE;
			$return['msg'] = 'Тема помечена для закачки.';
		}
		else
			$return['error'] = TRUE;
		echo json_encode($return);
	}

	//Удаляем мониторинг
	if ($_POST['action'] == 'del')
	{
		Database::deletItem($_POST['id']);
    	$return['error'] = FALSE;
        $return['msg'] = 'Удалено.';
        echo json_encode($return);
	}

	//Обновляем личные данные
	if ($_POST['action'] == 'update_credentials')
	{
    	if ( ! isset($_POST['passkey']))
    	    $_POST['passkey'] = '';
		Database::setCredentials($_POST['id'], $_POST['log'], $_POST['pass'], $_POST['passkey']);
    	$return['error'] = FALSE;
        $return['msg'] = 'Данные для трекера обновлены.';
        echo json_encode($return);
	}

    //Обновляем основные настройки
    if ($_POST['action'] == 'update_basic')
	{
        Database::updateSettings('serverAddress', Sys::checkPath($_POST['serverAddress']));
        Database::updateSettings('send', Sys::strBoolToInt($_POST['send']));
        Database::updateSettings('auth', Sys::strBoolToInt($_POST['auth']));
        Database::updateSettings('rss', Sys::strBoolToInt($_POST['rss']));
        Database::updateSettings('autoUpdate', Sys::strBoolToInt($_POST['autoUpdate']));
        Database::updateSettings('debug', Sys::strBoolToInt($_POST['debug']));

        $return['error'] = FALSE;
        $return['msg'] = 'Основные настройки сохранены.';
        echo json_encode($return);
    }

	//Обновляем расширенные настройки
	if ($_POST['action'] == 'update_extended')
	{
		$config = Config::read('ext_filename');
		if (file_put_contents($config, $_POST['settings']))
		{
			$return['error'] = FALSE;
			$return['msg'] = 'Расширенные настройки сохранены.';
		}
		else
		{
			$return['error'] = TRUE;
			$return['msg'] = 'Не удалось сохранить расширенные настройки.';
		}
		echo json_encode($return);
	}

	//Обновляем настройки уведомлений
	if ($_POST['action'] == 'update_services')
	{
        $notifications = Sys::strBoolToInt($_POST['notifySend']) ? Sys::strBoolToInt($_POST['notifySend']) : false;
        if ($notifications) {
            $setNotify = $_POST['notifyService'];
            Database::updateSettings('sendUpdate', 1);
            Database::updateSettings('sendUpdateService', $setNotify['id']);
            Database::updateAddress('notification', $setNotify['id'], $setNotify['address']);
        } else {
            Database::updateSettings('sendUpdate', 0);
        }

        $warnings = Sys::strBoolToInt($_POST['warnSend']) ? Sys::strBoolToInt($_POST['warnSend']) : false;
        if ($warnings) {
            $setWarn = $_POST['warnService'];
            Database::updateSettings('sendWarning', 1);
            Database::updateSettings('sendWarningService', $setWarn['id']);
            Database::updateAddress('warning', $setWarn['id'], $setWarn['address']);
        } else {
            Database::updateSettings('sendWarning', 0);
        }

        if ($notifications or $warnings) {
            Database::updateSettings('send', 1);
        }

        $return['error'] = FALSE;
        $return['msg'] = 'Настройки сервисов сохранены.';
        echo json_encode($return);
    }


	//Меняем пароль
	if ($_POST['action'] == 'update_auth')
	{
		$pass = md5($_POST['pass']);
		$q = Database::updateCredentials($pass);
		if ($q)
		{
			$return['error'] = FALSE;
            $return['msg'] = 'Новый пароль установлен.';
		}
		else
		{
			$return['error'] = TRUE;
			$return['msg'] = 'Не удалось сменить пароль!';
		}
		echo json_encode($return);
	}

    //Обновляем настройки прокси
    if ($_POST['action'] == 'update_proxy')
    {
        Database::updateSettings('proxy', Sys::strBoolToInt($_POST['proxy']));
		Database::updateSettings('proxyType', $_POST['proxyType']);
		Database::updateSettings('proxyAddress', $_POST['proxyAddress']);

        $return['error'] = FALSE;
        $return['msg'] = 'Настройки прокси сохранены.';
        echo json_encode($return);
    }

    //Обновляем настройки торрент-клиенты
    if ($_POST['action'] == 'update_torrent')
    {
        Database::updateSettings('useTorrent', Sys::strBoolToInt($_POST['useTorrent']));
        Database::updateSettings('torrentClient', $_POST['torrentClient']);
        Database::updateSettings('torrentAddress', $_POST['torrentAddress']);
        Database::updateSettings('torrentLogin', $_POST['torrentLogin']);
        Database::updateSettings('torrentPassword', $_POST['torrentPassword']);
        Database::updateSettings('pathToDownload', Sys::checkPath($_POST['pathToDownload']));
        Database::updateSettings('deleteDistribution', Sys::strBoolToInt($_POST['deleteDistribution']));
        Database::updateSettings('deleteOldFiles', Sys::strBoolToInt($_POST['deleteOldFiles']));

        $return['error'] = FALSE;
        $return['msg'] = 'Настройки торрент-клиента сохранены.';
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
            $return['error'] = FALSE;
            $return['msg'] = count($arr).' тем помечено для закачки.';
            echo json_encode($return);
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

	//Очистка ошибок потрекерно
	if ($_POST['action'] == 'clear_warnings')
	{
		if (Database::clearWarnings($_POST['tracker'])) {
            $return['error'] = FALSE;
            $return['msg'] = 'Ошибки трекера очищены.';
        } else {
            $return['error'] = TRUE;
            $return['msg'] = 'Не удалось очистить ошибки трекера.';
        }
        echo json_encode($return);
	}

}

if (isset($_GET['action']))
{
	//Сортировка вывода торрентов
	if ($_GET['action'] == 'order')
	{
		session_start();
        $by  = !empty($_GET['by']) ? $_GET['by'] : 'name';
        $dir = !empty($_GET['dir']) ? $_GET['dir'] : 'asc';

		if (!in_array($by, ['name', 'date'])) {
            $by = 'name';
        }
		if (!in_array($dir, ['asc', 'desc'])) {
            $by = 'asc';
        }
		setcookie('order', $by, time()+3600*24*365);
		setcookie('orderDir', $dir, time()+3600*24*365);
		//header('Location: index.php');
        echo json_encode('ok');
	}

    // Single item data
    if ($_GET['action'] == 'item_data') {
        $item = Database::getTorrent($_GET['id'])[0];
        if (!empty($item['torrent_id'])) {
            $item['url'] = Url::href($item['tracker'], $item['torrent_id']);
        }
        $item['reset'] = false;
        echo json_encode($item, JSON_NUMERIC_CHECK);
    }
    
    // Очистка таблицы Temp
    // action.php?action=temp_clear
    if ($_GET['action'] == 'temp_clear') {
        if (Database::clearTemp())
            echo 'Таблица очищена';
    }
}
?>
