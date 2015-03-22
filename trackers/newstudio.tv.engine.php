<?php
class newstudiotv
{
    protected static $sess_cookie;
    protected static $exucution;
    protected static $warning;
    
    protected static $page; 
    protected static $log_page;
    protected static $xml_page;
    
    //проверяем cookie
    public static function checkCookie($sess_cookie)
    {
        $result = Sys::getUrlContent(
            array(
                'type'           => 'POST',
                'returntransfer' => 1,
                'url'            => 'http://newstudio.tv',
                'cookie'         => $sess_cookie,
                'sendHeader'     => array('Host' => 'newstudio.tv', 'Content-length' => strlen($sess_cookie)),
            )
        );
        
        if (preg_match('/login\.php\?logout=1/', $result))
            return TRUE;
        else
            return FALSE;         
    }
    
    public static function checkRule($data)
    {
        if (preg_match('/^[\.\+\s\'\`\:\;\-a-zA-Z0-9]+$/', $data))
            return TRUE;
        else
            return FALSE;
    }
    
    //функция преобразования даты из строки
    private static function dateStringToNum($data)
    {
        $data = substr($data, 5);
        $data = substr($data, 0, -6);
        
        $monthes = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $month = substr($data, 3, 3);
        $data = preg_replace('/(\d\d)-(\d\d)-(\d\d)/', '$3-$2-$1', str_replace($month, str_pad(array_search($month, $monthes)+1, 2, 0, STR_PAD_LEFT), $data));
        
        $data = preg_split('/\s/', $data);      
        $date = $data[2].'-'.$data[1].'-'.$data[0].' '.$data[3];
        return $date;
    }
    
    //функция преобразования даты в строку
    private static function dateNumToString($data)
    {
        $data = substr($data, 0, -3);
        $data = str_replace('-', ' ', $data);
        $arr = preg_split('/\s/', $data);
        
        $month = Sys::dateNumToString($arr[1]);
        $date = $arr[2].' '.$month.' '.$arr[0].' '.$arr[3];
        return $date;
    }
    
    //функция поиска id torrent-файла
    private static function findID($link)
    {
            $result = Sys::getUrlContent(
            array(
                'type'           => 'POST',
                'returntransfer' => 1,
                'url'            => $link,
                'cookie'         => newstudiotv::$sess_cookie,
                'sendHeader'     => array('Host' => 'newstudio.tv', 'Content-length' => strlen(newstudiotv::$sess_cookie)),
            )
        );
        
        if (preg_match('/download\.php\?id=(\d{2,6})/', $result, $matches))
            return $matches[1];
        else
            return FALSE;
    }
    
    //функция анализа эпизода
    private static function analysisEpisode($item)
    {
        preg_match('/Сезон (\d{1,2}), Серия (\d{1,2})/', $item->title, $matches);
        if (isset($matches[1]) && isset($matches[2]))
        {
            if (strlen($matches[1]) == 1)
                $matches[1] = '0'.$matches[1];
            if (strlen($matches[2]) == 1)
                $matches[2] = '0'.$matches[2];
            $episode = 'S'.$matches[1].'E'.$matches[2];
            $date = $item->pubDate;
            $downloadId = newstudiotv::findID((string)$item->link);
            return array('episode'=>$episode, 'date'=>$date, 'link'=>'http://newstudio.tv/download.php?id='.$downloadId);
        }
    }
    
    //функция анализа xml ленты
    private static function analysis($name, $hd, $item)
    {
        if (preg_match('/'.$name.'/i', (string)$item->title))
        {
            if ($hd == 1)
            {
                if (preg_match_all('/720p/', $item->title, $matches))
                    return newstudiotv::analysisEpisode($item);
            }
            elseif ($hd == 2)
            {
                if (preg_match_all('/1080p/', $item->title, $matches))
                    return newstudiotv::analysisEpisode($item);
            }
            else
            {
                if (preg_match_all('/WEBDLRip/', $item->title, $matches))
                    return newstudiotv::analysisEpisode($item);
            }
        }
    }
    
    //функция получения кук
    public static function getCookie($tracker)
    {
        //проверяем заполнены ли учётные данные
        if (Database::checkTrackersCredentialsExist($tracker))
        {
            //получаем учётные данные
            $credentials = Database::getCredentials($tracker);
            $login = iconv('utf-8', 'windows-1251', $credentials['login']);
            $password = $credentials['password'];
            
            $page = Sys::getUrlContent(
                array(
                    'type'           => 'POST',
                    'header'         => 1,
                    'returntransfer' => 1,
                    'url'            => 'http://newstudio.tv/login.php',
                    'postfields'     => 'login_username='.$login.'&login_password='.$password.'&autologin=1&login=1',
                )
            );
            
            if ( ! empty($page))
            {
                //проверяем подходят ли учётные данные
                if (preg_match_all('/Set-Cookie: (.*);/U', $page, $array))
                {
                    newstudiotv::$sess_cookie = $array[1][0];
                    Database::setCookie($tracker, newstudiotv::$sess_cookie);
                    //запускам процесс выполнения, т.к. не может работать без кук
                    newstudiotv::$exucution = TRUE;
                }
                //проверяем нет ли сообщения о неправильном логине/пароле
                elseif (preg_match('/profile\.php\?mode=sendpassword/', $page, $out))
                {
                    //устанавливаем варнинг
                    if (newstudiotv::$warning == NULL)
                    {
                        newstudiotv::$warning = TRUE;
                        Errors::setWarnings($tracker, 'credential_wrong');
                    }
                    //останавливаем выполнение цепочки
                    newstudiotv::$exucution = FALSE;
                }
                //если не удалось получить никаких данных со страницы, значит трекер не доступен
                else
                {
                    //устанавливаем варнинг
                    if (newstudiotv::$warning == NULL)
                    {
                        newstudiotv::$warning = TRUE;
                        Errors::setWarnings($tracker, 'not_available');
                    }
                    //останавливаем выполнение цепочки
                    newstudiotv::$exucution = FALSE;
                }
            }
            else
            {
                //устанавливаем варнинг
                if (newstudiotv::$warning == NULL)
                {
                    newstudiotv::$warning = TRUE;
                    Errors::setWarnings($tracker, 'not_available');
                }
                //останавливаем выполнение цепочки
                newstudiotv::$exucution = FALSE;
            }
        }
        else
        {
            //устанавливаем варнинг
            if (newstudiotv::$warning == NULL)
            {
                newstudiotv::$warning = TRUE;
                Errors::setWarnings($tracker, 'credential_miss');
            }
            //останавливаем выполнение цепочки
            newstudiotv::$exucution = FALSE;
        }
    }
    
    //основная функция
    public static function main($torrentInfo)
    {
        extract($torrentInfo);
        
        //проверяем небыло ли до этого уже ошибок
        if (empty(newstudiotv::$exucution) || (newstudiotv::$exucution))
        {
            //проверяем получена ли уже кука
            if (empty(newstudiotv::$sess_cookie))
            {
                $cookie = Database::getCookie($tracker);
                if (newstudiotv::checkCookie($cookie))
                {
                    newstudiotv::$sess_cookie = $cookie;
                    //запускам процесс выполнения
                    newstudiotv::$exucution = TRUE;
                }
                else
                    newstudiotv::getCookie($tracker);
            }
            
            //проверяем получена ли уже RSS лента
            if ( ! newstudiotv::$log_page)
            {
                if (newstudiotv::$exucution)
                {
                    //получаем страницу
                    newstudiotv::$page = Sys::getUrlContent(
                        array(
                            'type'           => 'GET',
                            'returntransfer' => 1,
                            'url'            => 'http://newstudio.tv/rss.php',
                        )
                    );
                    
                    if ( ! empty(newstudiotv::$page))
                    {
                        //читаем xml
                        newstudiotv::$xml_page = @simplexml_load_string(newstudiotv::$page);
                        //если XML пришёл с ошибками - останавливаем выполнение, иначе - ставим флажок, что получаем страницу
                        if ( ! newstudiotv::$xml_page)
                        {
                            //устанавливаем варнинг
                            if (newstudiotv::$warning == NULL)
                            {
                                newstudiotv::$warning = TRUE;
                                Errors::setWarnings($tracker, 'rss_parse_false');
                            }
                            //останавливаем выполнение цепочки
                            newstudiotv::$exucution = FALSE;
                        }
                        else
                            newstudiotv::$log_page = TRUE;
                    }
                    else
                    {
                        //устанавливаем варнинг
                        if (newstudiotv::$warning == NULL)
                        {
                            newstudiotv::$warning = TRUE;
                            Errors::setWarnings($tracker, 'not_available');
                        }
                        //останавливаем выполнение цепочки
                        newstudiotv::$exucution = FALSE;
                    }
                }
            }
        }
        
        //если выполнение цепочки не остановлено
        if (newstudiotv::$exucution)
        {
            if ( ! empty(newstudiotv::$xml_page))
            {
                //сбрасываем варнинг
                Database::clearWarnings($tracker);
                $nodes = array();
                foreach (newstudiotv::$xml_page->channel->item AS $item)
                {
                    array_unshift($nodes, $item);
                }
                
                foreach ($nodes as $item)
                {
                    $serial = newstudiotv::analysis($name, $hd, $item);
                    
                    if ( ! empty($serial))
                    {
                        $episode = substr($serial['episode'], 4, 2);
                        $season = substr($serial['episode'], 1, 2);
                        $date_str = newstudiotv::dateNumToString(newstudiotv::dateStringToNum($serial['date']));
                        
                        if ( ! empty($ep))
                        {
                            if ($season == substr($ep, 1, 2) && $episode > substr($ep, 4, 2))
                                $download = TRUE;
                            elseif ($season > substr($ep, 1, 2) && $episode < substr($ep, 4, 2))
                                $download = TRUE;
                            else
                                $download = FALSE;
                        }
                        elseif ($ep == NULL)
                            $download = TRUE;
                        else
                            $download = FALSE;
                        
                        if ($download)
                        {
                            $amp = ($hd) ? 'HD' : NULL;
                            //сохраняем торрент в файл
                            $torrent = Sys::getUrlContent(
                                array(
                                    'type'           => 'POST',
                                    'returntransfer' => 1,
                                    'url'            => $serial['link'],
                                    'cookie'         => newstudiotv::$sess_cookie,
                                    'sendHeader'     => array('Host' => 'newstudio.tv', 'Content-length' => strlen(newstudiotv::$sess_cookie)),
                                )
                            );
                            
                            $file = str_replace(' ', '.', $name).'.S'.$season.'E'.$episode.'.'.$amp;
                            $episode = (substr($episode, 0, 1) == 0) ? substr($episode, 1, 1) : $episode;
                            $season = (substr($season, 0, 1) == 0) ? substr($season, 1, 1) : $season;
                            $message = $name.' '.$amp.' обновлён до '.$episode.' серии, '.$season.' сезона.';
                            $status = Sys::saveTorrent($tracker, $file, $torrent, $id, $hash, $message, $date_str);
                            
                            if ($status == 'add_fail' || $status == 'connect_fail' || $status == 'credential_wrong')
                            {
                                $torrentClient = Database::getSetting('torrentClient');
                                Errors::setWarnings($torrentClient, $status);
                            }
                            
                            //обновляем время регистрации торрента в базе
                            Database::setNewDate($id, newstudiotv::dateStringToNum($serial['date']));
                            //обновляем сведения о последнем эпизоде
                            Database::setNewEpisode($id, $serial['episode']);
                        }
                    }
                }
            }
        }
    }
    
    // функция возвращает тип раздач, которые обрабатывает треккер
    public static function getTrackerType() {
        return 'series';
    }
}
?>