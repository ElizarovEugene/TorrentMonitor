<?php
class anidubcom
{
    protected static $sess_cookie;
    protected static $exucution;
    protected static $warning;
    
    //проверяем cookie
    public static function checkCookie($sess_cookie)
    {
        $result = Sys::getUrlContent(
            array(
                'type'           => 'POST',
                'returntransfer' => 1,
                'url'            => 'http://tr.anidub.com',
                'cookie'         => $sess_cookie,
                'sendHeader'     => array('Host' => 'tr.anidub.com', 'Content-length' => strlen($sess_cookie)),
            )
        );
        
        if (preg_match('/Добро пожаловать <a href=\"http:\/\/tr\.anidub\.com\/user\/.*\/\" target=\"_blank\">/', $result))
            return TRUE;
        else
            return FALSE;
    }
    
    //функция проверки введёного URL`а
    public static function checkRule($data)
    {
        if (preg_match('/\D\d\/+/', $data))
            return FALSE;
        else
            return TRUE;
    }
    
    //функция преобразования даты
    private static function dateStringToNum($data)
    {
        if (strstr($data, 'Сегодня') || strstr($data, 'Вчера'))
        {
            $pieces = explode(', ', $data);
            if ($pieces[0] == 'Вчера')
                $timestamp = strtotime('-1 day');
            else         
                $timestamp = strtotime('now');
            $date = date('Y-m-d', $timestamp);
            $time = $pieces[1].':00';
            $dateTime = $date.' '.$time;
            
            return $dateTime;
        }
        else
        {
            $pieces = explode(', ', $data);
            $pieces2 = explode('-', $pieces[0]);
            if (strlen($pieces2[0]) == 1)
                $pieces2[0] = '0'.$pieces2[0];
            $dateTime = $pieces2[2].'-'.$pieces2[1].'-'.$pieces2[0].' '.$pieces[1].':00';
            
            return $dateTime;
        }
    }
    
    //функция поиска нужной ссылки
    protected static function findLynk($page)
    {
        if (preg_match('/<div id=\".*1080\"><div id=\'torrent_.*_info\'>\s{20}<div class=\"torrent_h\">\n\s{24}<a href=\"\/engine\/download\.php\?id=(.*)\" class=\" \">/U', $page, $array))
             return $array[1];
        elseif (preg_match('/<div id=\".*720\"><div id=\'torrent_.*_info\'>\s{20}<div class=\"torrent_h\">\n\s{24}<a href=\"\/engine\/download\.php\?id=(.*)\" class=\" \">/U', $page, $array))
            return $array[1];
        elseif (preg_match('/<div id=\'torrent_.*_info\'>\s{20}<div class=\"torrent_h\">\n\s{24}<a href=\"\/engine\/download\.php\?id=(.*)\" class=\" \">/U', $page, $array))
            return $array[1];
        else
            return FALSE;
    }
    
    //функция получения кук
    protected static function getCookie($tracker)
    {
        //проверяем заполнены ли учётные данные
        if (Database::checkTrackersCredentialsExist($tracker))
        {
            //получаем учётные данные
            $credentials = Database::getCredentials($tracker);
            $login = iconv("utf-8", "windows-1251", $credentials['login']);
            $password = $credentials['password'];
            
            //авторизовываемся на трекере
            $page = Sys::getUrlContent(
                array(
                    'type'           => 'POST',
                    'header'         => 1,
                    'returntransfer' => 1,
                    'url'            => 'http://tr.anidub.com',
                    'postfields'     => 'login_name='.$login.'&login_password='.$password.'&login=submit',
                )
            );
            
            if ( ! empty($page))
            {
                //проверяем подходят ли учётные данные
                if (preg_match('/<a href=\"http:\/\/tr\.anidub\.com\/index\.php\?do=register\">Регистрация<\/a>/', $page, $array))
                {
                    //устанавливаем варнинг
                    Errors::setWarnings($tracker, 'credential_wrong');
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    anidubcom::$exucution = FALSE;
                }
                //если подходят - получаем куки
                elseif (preg_match_all('/Set-Cookie: (.*);/U', $page, $array))
                {
                    anidubcom::$sess_cookie = $array[1][1].'; '.$array[1][2].'; '.$array[1][3].';';
                    Database::setCookie($tracker, anidubcom::$sess_cookie);
                    //запускам процесс выполнения, т.к. не может работать без кук
                    anidubcom::$exucution = TRUE;
                }
                else
                {
                    //устанавливаем варнинг
                    if (anidubcom::$warning == NULL)
                    {
                        anidubcom::$warning = TRUE;
                        Errors::setWarnings($tracker, 'not_available');
                    }
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    anidubcom::$exucution = FALSE;
                }
            }
            //если вообще ничего не найдено
            else
            {
                //устанавливаем варнинг
                if (anidubcom::$warning == NULL)
                {
                    anidubcom::$warning = TRUE;
                    Errors::setWarnings($tracker, 'not_available');
                }
                //останавливаем процесс выполнения, т.к. не может работать без кук
                anidubcom::$exucution = FALSE;
            }
        }
        else
        {
            //устанавливаем варнинг
            if (anidubcom::$warning == NULL)
            {
                anidubcom::$warning = TRUE;
                Errors::setWarnings($tracker, 'credential_miss');
            }
            //останавливаем процесс выполнения, т.к. не может работать без кук
            anidubcom::$exucution = FALSE;
        }
    }
    
    //основная функция
    public static function main($torrentInfo)
    {
        extract($torrentInfo);
        
        $cookie = Database::getCookie($tracker);
        if (anidubcom::checkCookie($cookie))
        {
            anidubcom::$sess_cookie = $cookie;
            //запускам процесс выполнения
            anidubcom::$exucution = TRUE;
        }           
        else
            anidubcom::getCookie($tracker);
        
        if (anidubcom::$exucution)
        {
            //получаем страницу для парсинга
            $page = Sys::getUrlContent(
                array(
                    'type'           => 'POST',
                    'header'         => 0,
                    'returntransfer' => 1,
                    'url'            => 'http://tr.anidub.com'.$torrent_id,
                    'cookie'         => anidubcom::$sess_cookie,
                    'sendHeader'     => array('Host' => 'tr.anidub.com', 'Content-length' => strlen(anidubcom::$sess_cookie)),
                )
            );
            
            if ( ! empty($page))
            {
                //ищем на странице дату регистрации торрента
                if (preg_match('/<li><b>Дата:<\/b> (.*)<\/li>/', $page, $array))
                {
                    //проверяем удалось ли получить дату со страницы
                    if (isset($array[1]))
                    {
                        //если дата не равна ничему
                        if ( ! empty($array[1]))
                        {
                            //сбрасываем варнинг
                            Database::clearWarnings($tracker);
                            //приводим дату к общему виду
                            $date = anidubcom::dateStringToNum($array[1]);
                            $date_str = $array[1];
                            //если даты не совпадают, перекачиваем торрент
                            if ($date != $timestamp)
                            {
                                $download_id = anidubcom::findLynk($page);
                                if ($download_id !== FALSE)
                                {
                                    //сохраняем торрент в файл
                                    $torrent = Sys::getUrlContent(
                                        array(
                                            'type'           => 'GET',
                                            'returntransfer' => 1,
                                            'url'            => 'http://tr.anidub.com/engine/download.php?id='.$download_id,
                                            'cookie'         => anidubcom::$sess_cookie,
                                            'sendHeader'     => array('Host' => 'tr.anidub.com', 'Content-length' => strlen(anidubcom::$sess_cookie)),
                                            'referer'        => 'http://tr.anidub.com'.$torrent_id,
                                        )
                                    );
                                    
                                    if (Sys::checkTorrentFile($torrent))
                                    {
                                        if ($auto_update)
                                        {
                                            $name = Sys::getHeader('http://tr.anidub.com'.$torrent_id);
                                            //обновляем заголовок торрента в базе
                                            Database::setNewName($id, $name);
                                        }
                                        
                                        $message = $name.' обновлён.';
                                        $status = Sys::saveTorrent($tracker, $download_id, $torrent, $id, $hash, $message, $date_str);
                                        
                                        //обновляем время регистрации торрента в базе
                                        Database::setNewDate($id, $date);
                                    }
                                }
                            }
                        }
                        else
                        {
                            //устанавливаем варнинг
                            if (anidubcom::$warning == NULL)
                            {
                                anidubcom::$warning = TRUE;
                                Errors::setWarnings($tracker, 'not_available');
                            }
                            //останавливаем процесс выполнения, т.к. не может работать без кук
                            anidubcom::$exucution = FALSE;
                        }
                    }
                    else
                    {
                        //устанавливаем варнинг
                        if (anidubcom::$warning == NULL)
                        {
                            anidubcom::$warning = TRUE;
                            Errors::setWarnings($tracker, 'not_available');
                        }
                        //останавливаем процесс выполнения, т.к. не может работать без кук
                        anidubcom::$exucution = FALSE;
                    }
                }
                else
                {
                    //устанавливаем варнинг
                    if (anidubcom::$warning == NULL)
                    {
                        anidubcom::$warning = TRUE;
                        Errors::setWarnings($tracker, 'not_available');
                    }
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    anidubcom::$exucution = FALSE;
                }
            }           
            else
            {
                //устанавливаем варнинг
                if (anidubcom::$warning == NULL)
                {
                    anidubcom::$warning = TRUE;
                    Errors::setWarnings($tracker, 'not_available');
                }
                //останавливаем процесс выполнения, т.к. не может работать без кук
                anidubcom::$exucution = FALSE;
            }
        }
    }
    
    // функция генерирует url ссылку на раздачу
    public static function generateURL($tracker, $torrent_id) {
        return 'http://tr.anidub.com/'.$torrent_id;
    }
    
    // функция возвращает перечень имен, которые могут использоваться в ссылках на текущий треккер
    public static function getAssociations() {
        return array('tr.anidub.com');
    }
    
    // функция возвращает идентификатор раздачи
    public static function getThreme($url) {
        $url = parse_url($url);
        return $url['path'];
    }
}
?>