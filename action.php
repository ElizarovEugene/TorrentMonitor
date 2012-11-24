<?php
$dir = __DIR__."/";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/Errors.class.php";
include_once $dir."class/Notification.class.php";

if (isset($_POST["action"]))
{
	//Проверяем пароль
	if ($_POST["action"] == "enter")
	{
		$password = md5($_POST["password"]);
		$count = Database::countCredentials($password);
		
		if ($count == 1)
		{
			session_start();
			$_SESSION["TM"] = $password;
			$return["error"] = FALSE;
		}
		else
		{
			$return["error"] = TRUE;
			$return["msg"] = 'Неверный пароль!';
		}
		echo json_encode($return);
	}

	//Добавляем тему для мониторинга
	if ($_POST["action"] == "torrent_add")
	{
		if ($url = parse_url($_POST["url"]))
		{
			$tracker = $url["host"];
			if (strpos($tracker, 'www\.'))
				$tracker = substr($tracker, 4);
			
			if ($tracker != 'rutor.org')
			{
				$query = explode("=", $url["query"]);
				$threme = $query[1];
			}
			else
			{
				preg_match('/\d{4,8}/', $url["path"], $array);
				$threme = $array[0];
			}
			
			if (is_array(Database::getCredentials($tracker)))
			{
				$engineFile = $dir."/trackers/{$tracker}.engine.php";
				if (file_exists($engineFile))
				{	
					$functionEngine = include_once $engineFile;
					$class = explode('.', $tracker);
					$class = $class[0];
					$class = str_replace('-', '', $class);

					if ($class::checkRule($threme))
					{
						if (Database::checkThremExist($tracker, $threme))
						{
						  
							if ( ! empty($_POST["name"]))
								$name = $_POST["name"];
							else
								$name = Sys::getHeader($_POST["url"]);

							Database::setThreme($tracker, $name, $threme);
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
	if ($_POST["action"] == "serial_add")
	{
		$tracker = $_POST["tracker"];
		if (is_array(Database::getCredentials($tracker)))
		{
			$engineFile = $dir."/trackers/{$tracker}.engine.php";
			if (file_exists($engineFile))
			{
				$functionEngine = include_once $engineFile;
				$class = explode('.', $tracker);
				$class = $class[0];
				$class = str_replace('-', '', $class);
				if ($class::checkRule($_POST["name"]))
				{
					if ( ! empty($_POST["hd"]))
						$hd = 1;
					else 
						$hd = 0;
					if (Database::checkSerialExist($tracker, $_POST["name"], $hd))	
					{
						Database::setSerial($tracker, $_POST["name"], $hd);
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
	if ($_POST["action"] == "user_add")
	{
		$tracker = $_POST["tracker"];
		if (is_array(Database::getCredentials($tracker)))
		{
			$engineFile = $dir."/trackers/{$tracker}.search.php";
			if (file_exists($engineFile))
			{
				if (Database::checkUserExist($tracker, $_POST["name"]))	
				{
					Database::setUser($tracker, $_POST["name"]);
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
			$return["error"] = FALSE;
		}
		else
		{		
			$return["error"] = TRUE;
			$return["msg"] = 'Пометить тему для закачки.';
		}
		echo json_encode($return);
	}
	
	//Удаляем мониторинг
	if ($_POST["action"] == "del")
	{
		Database::deletItem($_POST["id"]);
		?>
		Удаляю...
		<?php
		return TRUE;
	}
	
	//Удаляем пользователя
	if ($_POST["action"] == "del_user")
	{
		Database::deletUser($_POST["id"]);
		?>
		Удаляю...
		<?php
		return TRUE;
	}
	
	//Обновляем личные данные
	if ($_POST["action"] == "update_credentials")
	{
		Database::setCredentials($_POST["id"], $_POST["log"], $_POST["pass"]);
		?>
		Данные для трекера обновлены!
		<?php
		return TRUE;
	}
	
	//Обновляем настройки
	if ($_POST["action"] == "update_settings")
	{
		Database::updateSettings('path', $_POST["path"]);
		Database::updateSettings('email', $_POST["email"]);
		
		if ( ! empty($_POST["send"]))
			$send = 1;
		else 
			$send = 0;
		Database::updateSettings('send', $send);
		
		if ( ! empty($_POST["send_warning"]))
			$send_warning = 1;
		else 
			$send_warning = 0;
		Database::updateSettings('send_warning', $send_warning);
		
		if ( ! empty($_POST["auth"]))
			$auth = 1;
		else 
			$auth = 0;
		Database::updateSettings('auth', $auth);
		?>
		Настройки монитора обновлены.
		<?php
		return TRUE;
	}
	
	//Меняем пароль
	if ($_POST["action"] == "change_pass")
	{
		$pass = md5($_POST["pass"]);
		$q = Database::updateCredentials($pass);
		if ($q)
		{
			$return["error"] = FALSE;
		}
		else
		{
			$return["error"] = TRUE;
			$return["msg"] = 'Не удалось сменить пароль!';
		}
		echo json_encode($return);
	}
	
	//Добавляем тему на закачку
	if ($_POST["action"] == "download_thremes")
	{
		if ( ! empty($_POST['checkbox']))
		{
			$arr = $_POST['checkbox'];
			foreach ($arr as $id => $val)
			{
				Database::updateDownloadThreme($id);
			}
			echo count($arr)." тем помечено для закачки.";
			return TRUE;
		}
		Database::updateDownloadThremeNew();
	}	
}

if (isset($_GET["action"]))
{
	//Сортировка вывода торрентов
	if ($_GET["action"] == "order")
	{
		session_start();
		if ($_GET["order"] == "date")
			$_SESSION["order"] = "date";
		if ($_GET["order"] == "name")
			unset($_SESSION["order"]);
		header("Location: index.php");
	}	
}
?>

