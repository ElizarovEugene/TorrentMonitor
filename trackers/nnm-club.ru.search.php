<?php
include_once('nnm-club.ru.engine.php');

class nnmclubSearch extends nnmclub
{
	//получаем страницу для парсинга
	private static function getSearchPage($user, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://nnm-club.ru/forum/tracker.php");
		$header[] = "Host: nnm-club.ru\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "prev_sd=0&prev_a=0&prev_my=0&prev_n=0&prev_shc=0&prev_shf=1&prev_sha=1&prev_shs=0&prev_shr=0&prev_sht=0&f%5B%5D=-1&o=1&s=2&tm=-1&shf=1&sha=1&ta=-1&sns=-1&sds=-1&nm=&pn={$user}&submit=%CF%EE%E8%F1%EA");
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}
	
	public static function mainSearch($user_id, $tracker, $user)
	{
		$cookie = Database::getCookie($tracker);
		if (nnmclub::checkCookie($cookie))
		{
			nnmclub::$sess_cookie = $cookie;
			//запускам процесс выполнения
			nnmclub::$exucution = TRUE;
		}			
		else
    		nnmclub::getCookie($tracker);
    		
		if (nnmclub::$exucution)
		{
    		$user = iconv("utf-8", "windows-1251", $user);
    		$page = nnmclubSearch::getSearchPage($user, nnmclub::$sess_cookie);
    		
    		preg_match_all('/<a class=\"gen\" href=\"tracker\.php\?f=\d{3,9}\">(.*)<\/a>/', $page, $section);
    		preg_match_all('/<a class=\"(genmed|leechmed|seedmed) (topicpremod|topictitle)\" href=\"viewtopic\.php\?t=(\d{3,9})\"><b>(.*)<\/b><\/a>/', $page, $threme);
    		
    		for ($i=0; $i<count($threme[1]); $i++)
    			Database::addThremeToBuffer($user_id, $section[1][$i], $threme[3][$i], $threme[4][$i], $tracker);
    
    		$toDownload = Database::takeToDownload($tracker);
    		if (count($toDownload) > 0)
    		{
                for ($i=0; $i<count($toDownload); $i++)
                {
                    nnmclub::$page = nnmclub::getContent($toDownload[$i]['threme_id'], nnmclub::$sess_cookie);
			
        			if ( ! empty(nnmclub::$page))
        			{
                        //находим имя торрента для скачивания		
                        if (preg_match("/download\.php\?id=(\d{2,8})/", nnmclub::$page, $link))
                        {
                            //сохраняем торрент в файл
				$torrent_id = $link[1];

				if (Database::getSetting('download'))
				{
					$torrent = nnmclub::getTorrent($torrent_id, nnmclub::$sess_cookie);
					$client = ClientAdapterFactory::getStorage('file');
					$client->store($torrent, $toDownload[$i]['threme_id'], $tracker, $toDownload[$i]['threme'], $torrent_id, time());
				}

            				//обновляем время регистрации торрента в базе
            				Database::setDownloaded($toDownload[$i]['id']);
            				//отправляем уведомлении о новом торренте
            				$message = $toDownload[$i]['threme'].' добавлена для скачивания.';
            				$date = date('d M Y H:i');
            				Notification::sendNotification('notification', $date, $tracker, $message);
                        }
                    }            
                }
            }
        }
	}
}
