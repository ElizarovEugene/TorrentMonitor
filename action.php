<?php
$dir = dirname(__FILE__).'/';
include_once $dir.'config.php';
include_once $dir.'class/System.class.php';
include_once $dir.'class/Database.class.php';
include_once $dir.'class/Errors.class.php';
include_once $dir.'class/Notification.class.php';
include_once $dir.'class/Updater.class.php';
include_once $dir.'class/DBUpgrade.class.php';

//Проверяем пароль
function OnAction_enter(){
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
function OnAction_torrent_add(){
    global $dir;
	if ($url = parse_url($_POST['url']))
	{
		$tracker = $url['host'];
		$tracker = preg_replace('/www\./', '', $tracker);
		if ($tracker == 'tr.anidub.com')
			$tracker = 'anidub.com';
		
		if ($tracker != 'rutor.org')
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
				$class = str_replace('-', '', $class);

				if (call_user_func(array($class, 'checkRule'), $threme))
				{
					if (Database::checkThremExist($tracker, $threme))
					{
						if ( ! empty($_POST['name']))
							$name = $_POST['name'];
						else
							$name = Sys::getHeader($_POST['url']);

						Database::setThreme($tracker, $name, $_POST['path'], $threme);
						echo "Тема добавлена для мониторинга.";
					}
					else
					    echo "Вы уже следите за данной темой на трекере <b>$tracker?></b>.";
				}
				else
				    echo "Неверная ссылка.";
			}
			else
			    echo "Отсутствует модуль для трекера - <b>$tracker</b>.";
		}
		else
		    echo "Вы не можете следить за этим сериалом на трекере - <b>$tracker</b>, пока не введёте свои учётные данные!";
	}
	else
	    echo "Неверная ссылка.";
	return TRUE;
}
		
	//Добавляем сериал для мониторинга
function OnAction_serial_add(){
    global $dir;
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
					echo "Сериал добавлен для мониторинга.";
				}
				else
				    echo "Вы уже следите за данным сериалом на этом трекере - <b>$tracker</b>.";
			}
			else
                echo "Название содержит недопустимые символы.";
		}
		else 
		    echo "Отсутствует модуль ''$engineFile'' для трекера - <b>$tracker</b>.";
	}
	else
	    echo "Вы не можете следить за этим сериалом на трекере - <b>$tracker</b>, пока не введёте свои учётные данные!";
	return TRUE;
}
	
	//Добавляем пользователя для мониторинга
function OnAction_user_add(){
    global $dir;
	$tracker = $_POST['tracker'];
	if (is_array(Database::getCredentials($tracker)))
	{
		$engineFile = $dir.'/trackers/'.$tracker.'.search.php';
		if (file_exists($engineFile))
		{
			if (Database::checkUserExist($tracker, $_POST['name']))	
			{
				Database::setUser($tracker, $_POST['name']);
				echo "Пользователь добавлен для мониторинга.";
			}
			else
			    echo "Вы уже следите за данным пользователем на этом трекере - <b>$tracker</b>.";
		}
		else
		    echo "Отсутствует модуль для трекера - <b>$tracker</b>.";
	}
	else
	    echo "Вы не можете следить за этим пользователем на трекере - <b>$tracker</b>, пока не введёте свои учётные данные!";
	return TRUE;
}

//Удаляем пользователя из мониторинга и все его темы
function OnAction_delete_user(){
	Database::deletUser($_POST['user_id']);
	echo "Удаляю...";
	return TRUE;
}
	
//Удаляем тему из буфера
function OnAction_delete_from_buffer(){
	Database::deleteFromBuffer($_POST['id']);
	echo "Удаляю...";
	return TRUE;
}	

//Очищаем весь список тем
function OnAction_threme_clear(){
	$array = Database::selectAllFromBuffer();
	for($i=0; $i<count($array); $i++)
	{
    	Database::deleteFromBuffer($array[$i]['id']);
	}
}
	
//Перемещаем тему из буфера в мониторинг постоянный
function OnAction_transfer_from_buffer(){
	Database::transferFromBuffer($_POST['id']);
	echo "Переношу...";
	return TRUE;
}

//Помечаем тему для скачивания
function OnAction_threme_add(){
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
function OnAction_del(){
	Database::deletItem($_POST['id']);
	echo "Удаляю...";
	return TRUE;
}

//Обновляем личные данные
function OnAction_update_credentials(){
	Database::setCredentials($_POST['id'], $_POST['log'], $_POST['pass']);
	echo "Данные для трекера обновлены!";
	return TRUE;
}

function notEmptyPost($key){
    if(!empty($_POST[$key]))
        return 1;
    else 
        return 0;
}

//Обновляем настройки
function OnAction_update_settings(){
	$path = Sys::checkPath($_POST['path']);
	Database::updateSettings('path', $path);
	Database::updateSettings('email', $_POST['email']);
	
	Database::updateSettings('send', notEmptyPost('send'));
	Database::updateSettings('send_warning', notEmptyPost('send_warning'));
	Database::updateSettings('auth', notEmptyPost('auth'));
	
	Database::updateSettings('proxy', notEmptyPost('proxy'));

	Database::updateSettings('proxyAddress', $_POST['proxyAddress']);

    Database::updateSettings('useTorrent', notEmptyPost('torrent'));
    Database::updateSettings('torrentClient', $_POST['torrentClient']);
    Database::updateSettings('torrentAddress', $_POST['torrentAddress']);
    Database::updateSettings('torrentLogin', $_POST['torrentLogin']);
    Database::updateSettings('torrentPassword', $_POST['torrentPassword']);
    $pathToDownload = Sys::checkPath($_POST['pathToDownload']);
    Database::updateSettings('pathToDownload', $pathToDownload);
    Database::updateSettings('deleteTorrent', notEmptyPost('deleteTorrent'));
    Database::updateSettings('deleteOldFiles', notEmptyPost('deleteOldFiles'));
	echo "Настройки монитора обновлены.";
	return TRUE;
}

//Меняем пароль
function OnAction_change_pass(){
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
function OnAction_download_thremes(){
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
	
	
function OnAction_update_reset(){
    Updater::action('update_reset');
}

function OnAction_update_nextstep(){
    Updater::action('update_nextstep');
}

function OnAction_upgrade_db(){
    DBUpgrade::Upgrade();
}

if (isset($_POST['action']))
{
    return call_user_func("OnAction_".preg_replace("/[^0-9a-zA-Z_]+/", "", $_POST['action']));
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

