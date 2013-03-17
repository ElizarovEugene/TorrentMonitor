<?php
include_once('rutracker.org.engine.php');

class rutrackerSearch extends rutracker
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
		curl_setopt($ch, CURLOPT_URL, "http://rutracker.org/forum/tracker.php");
		$header[] = "Host: rutracker.org\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "prev_my=0&prev_new=0&prev_oop=0&f%5B%5D=-1&o=1&s=2&tm=-1&pn={$user}&nm=");
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}
	
	//ищем темы пользователя	
	public static function mainSearch($user_id, $tracker, $user)
	{
		$cookie = Database::getCookie($tracker);
		if (rutracker::checkCookie($cookie))
		{
			rutracker::$sess_cookie = $cookie;
			//запускам процесс выполнения
			rutracker::$exucution = TRUE;
		}			
		else
    		rutracker::getCookie($tracker);

		if (rutracker::$exucution)
		{    		
    		$user = iconv("utf-8", "windows-1251", $user);
    		$page = rutrackerSearch::getSearchPage($user, rutracker::$sess_cookie);
    
    		preg_match_all('/<a class=\"gen f\" href=\"tracker\.php\?f=\d{1,9}\">(.*)<\/a>/', $page, $section);
    		preg_match_all('/<a data-topic_id=\"\d{3,9}\" class=\"med tLink hl-tags bold\" href=\"\.\/viewtopic.php\?t=(\d{3,9})\">(.*)<\/a>/', $page, $threme);
    
    		for ($i=0; $i<count($threme[1]); $i++)
    			Database::addThremeToBuffer($user_id, $section[1][$i], $threme[1][$i], $threme[2][$i], $tracker);
    
    		$toDownload = Database::takeToDownload($tracker);
    		if (count($toDownload) > 0)
    		{
                for ($i=0; $i<count($toDownload); $i++)
                {
                    //сохраняем торрент в файл
    				$torrent = rutracker::getTorrent($toDownload[$i]['threme_id'], rutracker::$sess_cookie);
    				$client = ClientAdapterFactory::getStorage('file');
    				$client->store($torrent, $id, $tracker, $name, $id, time());				
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