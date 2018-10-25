<?php
include_once('tfile.cc.engine.php');

class tfileSearch extends tfile
{
	//ищем темы пользователя	
	public static function mainSearch($params)
	{
    	extract($params);
		$user = str_replace(' ', '+', iconv('utf-8', 'windows-1251', $name));
        //получаем страницу для парсинга
    	$page = Sys::getUrlContent(
        	array(
        		'type'           => 'GET',
        		'header'         => 0,
        		'returntransfer' => 1,
        		'url'            => 'http://search.tfile.cc/?q=&c=0&g=&act=&y=&ql=&a='.$user.'&d=&o=&size_min=0&size_max=0',
        		'convert'        => array('windows-1251', 'utf-8')
        	)
        );
        
        if ( ! empty($page))
		{
			//сбрасываем варнинг
			Database::clearWarnings($tracker);
			preg_match_all('/<td class=\"f\">\n\t\t\t\t\n\t\t\t\t\t(.*)\n\t\t\t\t<\/td>/', $page, $section);
			for ($i=0; $i<count($section[1]); $i++)
			{
				if (preg_match_all('/<a href=\"\/forum\/viewforum\.php\?f=\d{1,9}\">(.*)<\/a>/U', $section[1][$i], $sections))
				{
    				$sectionStr = '';
    				for ($x=0; $x<count($sections[1]); $x++)
    					$sectionStr .= $sections[1][$x].', ';

    				$sectionStr = substr($sectionStr, 0, -2);
    				$sectionArr[] = $sectionStr;
                }
			}

			preg_match_all('/<a href=\"http:\/\/tfile\.cc\/forum\/viewtopic\.php\?t=(\d{1,9})\">(.*)<\/a>/U', $page, $threme);
			preg_match_all('/<td class=\"ms\">\n\t\t\t\t(.*)\s.*<br\/>\n\t\t\t<\/td>/', $page, $dates);

            if (count($sectionArr) == count($threme[1]) && count($threme[1]) == count($dates[1]))
            {
				for ($i=0; $i<count($threme[1]); $i++)    				
					Database::addThremeToBuffer($id, $sectionArr[$i], $threme[1][$i], $threme[2][$i], $dates[1][$i], $tracker);
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
    	            		'type'           => 'GET',
    	            		'header'         => 0,
    	            		'returntransfer' => 1,
    	            		'url'            => 'http://tfile.cc/forum/viewtopic.php?t='.$toDownload[$i]['threme_id']
    	            	)
    	            );
                    
    				//находим имя торрента для скачивания		
    				if (preg_match('/download\.php\?id=(\d+)&uk=(\d+)/', $page, $link))
    				{
    					//сбрасываем варнинг
    					Database::clearWarnings($tracker);
    					//ищем на странице id торрента
    					$download_id = $link[1];
    					$ak_id = $link[2];
    					//сохраняем торрент в файл
    					$torrent = Sys::getUrlContent(
                        	array(
                        		'type'           => 'GET',
                        		'returntransfer' => 1,
                        		'url'            => 'http://tfile.cc/forum/download.php?id='.$download_id.'&ak='.$ak_id,
                        		'sendHeader'     => array('Host' => 'tfile.cc'),
                        		'referer'        => 'http://tfile.cc/forum/viewtopic.php?t='.$torrent_id,
                        	)
                        );
                        
                        if (Sys::checkTorrentFile($torrent))
                        {
        					$message = $toDownload[$i]['threme'].' добавлена для скачивания.';
        					$status = Sys::saveTorrent($toDownload[$i]['tracker'], $toDownload[$i]['threme_id'], $torrent, $toDownload[$i]['threme_id'], 0, $message, date('d M Y H:i'), $name);
        								
        					//обновляем время регистрации торрента в базе
        					Database::setDownloaded($toDownload[$i]['id']);
        				}
        				else
                            Errors::setWarnings($tracker, 'torrent_file_fail');
    				}
    				else
    				{
    					//устанавливаем варнинг
    					if (tfile::$warning == NULL)
    					{
    						tfile::$warning = TRUE;
    						Errors::setWarnings($tracker, 'cant_find_dowload_link');
    					}
    					//останавливаем процесс выполнения, т.к. не может работать без кук
    					tfile::$exucution = FALSE;
    				}
                }
            }
        }
	}
}