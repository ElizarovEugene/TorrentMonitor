<?php
include_once('tfile.me.engine.php');

class tfileSearch extends tfile
{
	//получаем страницу для парсинга
	private static function getSearchPage($user)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://tfile.me/forum/ssearch.php?q=&c=0&g=&ql=&a={$user}&d=&o=&size_min=0&size_max=0");
		$header[] = "Host: tfile.me\r\n";
		$header[] = "Referer: http://tfile.me/forum/ssearch.php?q={$user}\r\n";
		$header[] = "Content-length: 100\r\n\r\n";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_COOKIE, 'mse="K3JWQTNOV045NVBoR1hiKzNWSWNha2swZlBhY3Q4dEEzbnp0TzYydXJXMUtISjJmM1pMY0RTYmlNSTdjZHpZQngzNWp1ajNlT0FTbw0KVGVwWXIrTXRZZGJWMmJYOTNDZmtCaEVRYWpNREpycllxdWt4NlU0WDNNcXF5NzJ1c3FaTUpUaEUreHIybHByZjR3U0U3N05ucXBJbg0KTk1BL04zWUZYdzVma3IxTGlxMD0NCg=="; jid=cjaz7mo9xmq81a7i21dh7gg7n');
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}

	//ищем темы пользователя	
	public static function mainSearch($user_id, $tracker, $user)
	{
		$user = iconv("utf-8", "windows-1251", $user);
		$page = tfileSearch::getSearchPage($user);
		
		if (preg_match_all('/<td class=\"f\">\n\t\t\t\t\n\t\t\t\t\t(.*)\n\t\t\t\t<\/td>/', $page, $section))
		{
			for ($i=0; $i<count($section[1]); $i++)
			{
				preg_match_all('/<a href=\"viewforum\.php\?f=\d{1,9}\">(.*)<\/a>/U', $section[1][$i], $sections);
				$sectionStr = '';
				for ($x=0; $x<count($sections[1]); $x++)
				{
					$sectionStr .= $sections[1][$x].', ';
				}
				$sectionStr = substr($sectionStr, 0, -2);
				$sectionArr[] = $sectionStr;
			}
		}

		preg_match_all('/<a href=\"viewtopic\.php\?t=(\d{1,9})\">(.*)<\/a>/U', $page, $threme);

		if ( ! empty($threme))
		{
			for ($i=0; $i<count($threme[1]); $i++)
			{
				Database::addThremeToBuffer($user_id, $sectionArr[$i], $threme[1][$i], $threme[2][$i], $tracker);
			}
		}

		$toDownload = Database::takeToDownload($tracker);
		if(count($toDownload) > 0)
		{
            for ($i=0; $i<count($toDownload); $i++)
            {
            	//получаем страницу для парсинга
            	$page = tfile::getContent($toDownload[$i]['threme_id']);
                //сохраняем торрент в файл
				$torrent_id = tfile::findId($page);
				if (is_string($torrent_id))
				{
					if (Database::getSetting('download'))
					{
						$torrent = tfile::getTorrent($torrent_id);
						$client = ClientAdapterFactory::getStorage('file');
						$client->store($torrent, $toDownload[$i]['id'], $tracker, $toDownload[$i]['threme'], $toDownload[$i]['threme_id'], time());
					}

					//обновляем время регистрации торрента в базе
					Database::setDownloaded($toDownload[$i]['id']);
					//отправляем уведомлении о новом торренте
					$message = $toDownload[$i]['threme'].' добавлена для скачивания.';
					$date = date('d M Y H:i');
					Notification::sendNotification('notification', $date, $tracker, $message);
				}
				else
				{
					//устанавливаем варнинг
					if (tfile::$warning == NULL)
					{
						tfile::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					tfile::$exucution = FALSE;
				}
            }
        }
	}
}
