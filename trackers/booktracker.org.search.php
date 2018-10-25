<?php
include_once('booktracker.org.engine.php');

class booktrackerSearch extends booktracker
{
	//ищем темы пользователя
	public static function mainSearch($params)
	{
    	extract($params);
		$cookie = Database::getCookie($tracker);
		if (booktracker::checkCookie($cookie))
		{
			booktracker::$sess_cookie = $cookie;
			//запускам процесс выполнения
			booktracker::$exucution = TRUE;
		}
		else
    		booktracker::getCookie($tracker);

		if (booktracker::$exucution)
		{
    		$page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 1,
            		'returntransfer' => 1,
            		'url'            => 'https://booktracker.org/search.php',
            		'cookie'         => booktracker::$sess_cookie,
            		'postfields'     => 'nm=&to=1&allw=1&pn='.$name.'&f%5B%5D=0&tm=0&dm=0&s=0&o=1&submit=%C2%A0%C2%A0%D0%9F%D0%BE%D0%B8%D1%81%D0%BA%C2%A0%C2%A0',
            	)
	        );
	        
	        if ( ! empty($page))
	        {
	        	//сбрасываем варнинг
				Database::clearWarnings($tracker);

	    		preg_match_all('/<a href=\"\.\/viewforum\.php\?f=\d{2,6}\" class=\"gen\">(.*)<\/a>/', $page, $section);
	    		preg_match_all('/<a href=\"\.\/viewtopic\.php\?t=(.*)\" class=\"topictitle\"><span id=\"tid_.*\">(.*)<\/span><\/a>/', $page, $threme);
	    		preg_match_all('/<td class=\".*\" style=\".*\">\n\t\t<p>(.*)<\/p>/', $page, $dates);

                if (count($section[1]) == count($threme[1]) && count($threme[1]) == count($dates[1]))
                {
	    		    for ($i=0; $i<count($threme[1]); $i++)
	    		    {
    	    		    $arr1 = preg_split('/\s/', $dates[1][$i]);
    	    		    $arr = preg_split('/-/', $arr1[0]);
    	    		    $date = $arr[0].'-'.$arr[1].'-'.$arr[2];
	    			    Database::addThremeToBuffer($id, $section[1][$i], $threme[1][$i], $threme[2][$i], $date, $tracker);
	    			}
                }
	    	}

    		$toDownload = Database::takeToDownload($tracker);
    		if ($toDownload != NULL)
    		{
        		if (count($toDownload) > 0)
        		{
                    for ($i=0; $i<count($toDownload); $i++)
                    {
                    	//получаем страницу для парсинга
                        $page = Sys::getUrlContent(
                        	array(
                        		'type'           => 'POST',
                        		'header'         => 0,
                        		'returntransfer' => 1,
                        		'encoding'       => 1,
                        		'url'            => 'https://booktracker.org/viewtopic.php?t='.$torrent_id,
                        		'cookie'         => booktracker::$sess_cookie,
                        		'sendHeader'     => array('Host' => 'booktracker.org', 'Content-length' => strlen(booktracker::$sess_cookie)),
                        	)
                        );
    
            			if ( ! empty($page))
            			{
                            //находим имя торрента для скачивания
                            if (preg_match('/href=\"download.php\?id=(\d+)\"/', $page, $link))
                            {
                            	//сбрасываем варнинг
    							Database::clearWarnings($tracker);
                                //сохраняем торрент в файл
                                $download_id = $link[1];
    
                                $torrent = Sys::getUrlContent(
                                	array(
                                		'type'           => 'GET',
                                		'returntransfer' => 1,
                                		'url'            => 'https://booktracker.org/download.php?id='.$download_id,
                                		'cookie'         => booktracker::$sess_cookie,
                                		'sendHeader'     => array('Host' => 'booktracker.org', 'Content-length' => strlen(booktracker::$sess_cookie)),
                                		'referer'        => 'https://booktracker.org/viewtopic.php?t='.$torrent_id,
                                	)
                                );
                                
                                if (Sys::checkTorrentFile($torrent))
                                {
                                    $message = $toDownload[$i]['threme'].' добавлена для скачивания.';
                                    $status = Sys::saveTorrent($tracker, $toDownload[$i]['threme_id'], $torrent, $toDownload[$i]['threme_id'], 0, $message, date('d M Y H:i'), $name);
        								
                    				//обновляем время регистрации торрента в базе
                    				Database::setDownloaded($toDownload[$i]['id']);
                                }
                                else
                                    Errors::setWarnings($tracker, 'torrent_file_fail');
                            }
                        }
                    }
                }
            }
        }
	}
}
?>