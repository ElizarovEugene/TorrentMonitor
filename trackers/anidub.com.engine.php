<?php
class anidub
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
                'url'            => 'https://tr.anidub.com',
                'cookie'         => $sess_cookie,
                'sendHeader'     => array('Host' => 'tr.anidub.com', 'Content-length' => strlen($sess_cookie)),
            )
        );

        if (preg_match('/Добро пожаловать <a href=\"https:\/\/tr\.anidub\.com\/user\/.*\/\" target=\"_blank\">/', $result))
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
                    'url'            => 'https://tr.anidub.com',
                    'postfields'     => 'login_name='.$login.'&login_password='.$password.'&login=submit',
                )
            );

            if ( ! empty($page))
            {
                //проверяем подходят ли учётные данные
                if (preg_match('/<a href=\"https:\/\/tr\.anidub\.com\/index\.php\?do=register\">Регистрация<\/a>/', $page, $array))
                {
                    //устанавливаем варнинг
                    Errors::setWarnings($tracker, 'credential_wrong');
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    anidub::$exucution = FALSE;
                }
                //если подходят - получаем куки
                elseif (preg_match_all('/set-cookie: (.*);/Ui', $page, $array))
                {
                    anidub::$sess_cookie = $array[1][1].'; '.$array[1][2].'; '.$array[1][3].';';
                    Database::setCookie($tracker, anidub::$sess_cookie);
                    //запускам процесс выполнения, т.к. не может работать без кук
                    anidub::$exucution = TRUE;
                }
                else
                {
                    //устанавливаем варнинг
                    if (anidub::$warning == NULL)
                    {
                        anidub::$warning = TRUE;
                        Errors::setWarnings($tracker, 'cant_find_cookie');
                    }
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    anidub::$exucution = FALSE;
                }
            }
            //если вообще ничего не найдено
            else
            {
                //устанавливаем варнинг
                if (anidub::$warning == NULL)
                {
                    anidub::$warning = TRUE;
                    Errors::setWarnings($tracker, 'cant_get_auth_page');
                }
                //останавливаем процесс выполнения, т.к. не может работать без кук
                anidub::$exucution = FALSE;
            }
        }
        else
        {
            //устанавливаем варнинг
            if (anidub::$warning == NULL)
            {
                anidub::$warning = TRUE;
                Errors::setWarnings($tracker, 'credential_miss');
            }
            //останавливаем процесс выполнения, т.к. не может работать без кук
            anidub::$exucution = FALSE;
        }
    }
    
    //основная функция
    public static function main($params)
    {
        extract($params);
        $cookie = Database::getCookie($tracker);
        if (anidub::checkCookie($cookie))
        {
            anidub::$sess_cookie = $cookie;
            //запускам процесс выполнения
            anidub::$exucution = TRUE;
        }            
        else
            anidub::getCookie($tracker);

        if (anidub::$exucution)
        {
            //получаем страницу для парсинга
            $page = Sys::getUrlContent(
                array(
                    'type'           => 'POST',
                    'header'         => 0,
                    'returntransfer' => 1,
                    'url'            => 'https://tr.anidub.com'.$torrent_id,
                    'cookie'         => anidub::$sess_cookie,
                    'sendHeader'     => array('Host' => 'tr.anidub.com', 'Content-length' => strlen(anidub::$sess_cookie)),
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
                            $date = anidub::dateStringToNum($array[1]);
                            $date_str = $array[1];
                            //если даты не совпадают, перекачиваем торрент
                            if ($date != $timestamp)
                            {
                                if (preg_match('/download\.php\?id=(\d*)/', $page, $array))
                                {
                                    if ($array[1] !== FALSE)
                                    {
                                        //сохраняем торрент в файл
                                        $torrent = Sys::getUrlContent(
                                            array(
                                                'type'           => 'GET',
                                                'returntransfer' => 1,
                                                'url'            => 'https://tr.anidub.com/engine/download.php?id='.$array[1],
                                                'cookie'         => anidub::$sess_cookie,
                                                'sendHeader'     => array('Host' => 'tr.anidub.com', 'Content-length' => strlen(anidub::$sess_cookie)),
                                                'referer'        => 'https://tr.anidub.com'.$torrent_id,
                                            )
                                        );
                                        
                                        if (Sys::checkTorrentFile($torrent))
                                        {
                                            if ($auto_update)
                                            {
                                                $name = Sys::parseHeader($tracker, $page);
                                                //обновляем заголовок торрента в базе
                                                Database::setNewName($id, $name);
                                            }
    
                                            $message = $name.' обновлён.';
                                            $status = Sys::saveTorrent($tracker, $download_id, $torrent, $id, $hash, $message, $date_str, $name);
                                            
                                            //обновляем время регистрации торрента в базе
                                            Database::setNewDate($id, $date);
                                            //сбрасываем варнинг
                                            Database::clearWarnings($tracker);
                                            Database::setErrorToThreme($id, 0);
                                        }
                                        else
                                            Errors::setWarnings($tracker, 'torrent_file_fail', $id);
                                    }
                                }
                                else
                                {
                                    anidub::$warning = TRUE;
                                    Errors::setWarnings($tracker, 'cant_find_torrent_id', $id);
                                }
                                Database::setErrorToThreme($id, 0);
                            }
                        }
                        else
                        {
                            //устанавливаем варнинг
                            if (anidub::$warning == NULL)
                            {
                                anidub::$warning = TRUE;
                                Errors::setWarnings($tracker, 'cant_find_date', $id);
                            }
                            //останавливаем процесс выполнения, т.к. не может работать без кук
                            anidub::$exucution = FALSE;
                        }
                    }
                    else
                    {
                        //устанавливаем варнинг
                        if (anidub::$warning == NULL)
                        {
                            anidub::$warning = TRUE;
                            Errors::setWarnings($tracker, 'cant_find_date', $id);
                        }
                        //останавливаем процесс выполнения, т.к. не может работать без кук
                        anidub::$exucution = FALSE;
                    }
                }
                else
                {
                    //устанавливаем варнинг
                    if (anidub::$warning == NULL)
                    {
                        anidub::$warning = TRUE;
                        Errors::setWarnings($tracker, 'cant_find_date', $id);
                    }
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    anidub::$exucution = FALSE;
                }
            }            
            else
            {
                //устанавливаем варнинг
                if (anidub::$warning == NULL)
                {
                    anidub::$warning = TRUE;
                    Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
                }
                //останавливаем процесс выполнения, т.к. не может работать без кук
                anidub::$exucution = FALSE;
            }
        }
        anidub::$warning = NULL;
    }
}
?>