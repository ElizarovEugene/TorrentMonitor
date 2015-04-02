<?php
class tracker0daykievua
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
                'url'            => 'http://tracker.0day.kiev.ua/',
                'cookie'         => $sess_cookie,
                'sendHeader'     => array('Host' => 'tracker.0day.kiev.ua', 'Content-length' => strlen($sess_cookie)),
                'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            )
        );
        
        if (preg_match('/<a href=\"userdetails\.php\?id=\d+\">/U', $result))
            return TRUE;
        else
            return FALSE;
    }
    
    //функция проверки введёного URL`а
    public static function checkRule($data)
    {
        if (preg_match('/\D+/', $data))
            return FALSE;
        else
            return TRUE;
    }
    
    //функция преобразования даты
    private static function dateNumToString($data)
    {
        $pieces = explode(' ', $data);
        $dates = explode('-', $pieces[0]);
        $m = Sys::dateNumToString($dates[1]);
        $date = $dates[2].' '.$m.' '.$dates[0];
        $time = substr($pieces[1], 0, -3);
        $dateTime = $date.' '.$time;
        
        return $dateTime;
    }
    
    //функция получения кук
    protected static function getCookie($tracker)
    {
        //проверяем заполнены ли учётные данные
        if (Database::checkTrackersCredentialsExist($tracker))
        {
            //получаем учётные данные
            $credentials = Database::getCredentials($tracker);
            $login = iconv('utf-8', 'windows-1251', $credentials['login']);
            $password = $credentials['password'];
            
            //авторизовываемся на трекере
            $page = Sys::getUrlContent(
                array(
                    'type'           => 'POST',
                    'header'         => 1,
                    'returntransfer' => 1,
                    'url'            => 'http://tracker.0day.kiev.ua/takelogin.php',
                    'postfields'     => 'username='.$login.'&password='.$password,
                    'convert'        => array('windows-1251', 'utf-8//IGNORE'),
                )
            );
            
            if ( ! empty($page))
            {
                //проверяем подходят ли учётные данные
                if (preg_match('/Имя пользователя или пароль неверны/', $page, $array))
                {
                    //устанавливаем варнинг
                    Errors::setWarnings($tracker, 'credential_wrong');
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    tracker0daykievua::$exucution = FALSE;
                }
                //если подходят - получаем куки
                elseif (preg_match_all('/Set-Cookie: (.*);/U', $page, $array))
                {
                    tracker0daykievua::$sess_cookie = $array[1][1].'; '.$array[1][2].';';
                    Database::setCookie($tracker, tracker0daykievua::$sess_cookie);
                    //запускам процесс выполнения, т.к. не может работать без кук
                    tracker0daykievua::$exucution = TRUE;
                }
                else
                {
                    //устанавливаем варнинг
                    if (tracker0daykievua::$warning == NULL)
                    {
                        tracker0daykievua::$warning = TRUE;
                        Errors::setWarnings($tracker, 'not_available');
                    }
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    tracker0daykievua::$exucution = FALSE;
                }
            }
            //если вообще ничего не найдено
            else
            {
                //устанавливаем варнинг
                if (tracker0daykievua::$warning == NULL)
                {
                    tracker0daykievua::$warning = TRUE;
                    Errors::setWarnings($tracker, 'not_available');
                }
                //останавливаем процесс выполнения, т.к. не может работать без кук
                tracker0daykievua::$exucution = FALSE;
            }
        }
        else
        {
            //устанавливаем варнинг
            if (tracker0daykievua::$warning == NULL)
            {
                tracker0daykievua::$warning = TRUE;
                Errors::setWarnings($tracker, 'credential_miss');
            }
            //останавливаем процесс выполнения, т.к. не может работать без кук
            tracker0daykievua::$exucution = FALSE;
        }
    }
    
    //основная функция
    public static function main($torrentInfo)
    {
        extract($torrentInfo);
        
        $cookie = Database::getCookie($tracker);
        if (tracker0daykievua::checkCookie($cookie))
        {
            tracker0daykievua::$sess_cookie = $cookie;
            //запускам процесс выполнения
            tracker0daykievua::$exucution = TRUE;
        }
        else
            tracker0daykievua::getCookie($tracker);
        
        if (tracker0daykievua::$exucution)
        {
            //получаем страницу для парсинга
            $page = Sys::getUrlContent(
                array(
                    'type'           => 'POST',
                    'header'         => 0,
                    'returntransfer' => 1,
                    'url'            => 'http://tracker.0day.kiev.ua/details.php?id='.$torrent_id,
                    'cookie'         => tracker0daykievua::$sess_cookie,
                    'sendHeader'     => array('Host' => 'tracker.0day.kiev.ua', 'Content-length' => strlen(tracker0daykievua::$sess_cookie)),
                    'convert'        => array('windows-1251', 'utf-8//IGNORE'),
                )
            );
            
            if ( ! empty($page))
            {
                //ищем на странице дату регистрации торрента
                if (preg_match('/Добавлен<\/td><td class=\'row2\' valign=\"top\" align=\"left\">(.*)<\/td>/', $page, $array))
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
                            $date = $array[1];
                            $date_str = tracker0daykievua::dateNumToString($array[1]);
                            //если даты не совпадают, перекачиваем торрент
                            if ($date != $timestamp)
                            {
                                //ищем ссылку на скачивание torrent-файла
                                if (preg_match('/download\.php\?id='.$torrent_id.'\&amp\;name=.*\.torrent/', $page, $array))
                                {
                                    $link = str_replace('&amp;', '&', 'http://tracker.0day.kiev.ua/'.$array[0]);
                                    //сохраняем торрент в файл
                                    $torrent = Sys::getUrlContent(
                                        array(
                                            'type'           => 'GET',
                                            'returntransfer' => 1,
                                            'url'            => $link,
                                            'cookie'         => tracker0daykievua::$sess_cookie,
                                            'sendHeader'     => array('Host' => 'tracker.0day.kiev.ua', 'Content-length' => strlen(tracker0daykievua::$sess_cookie)),
                                        )
                                    );
                                    
                                    if ($auto_update)
                                    {
                                        $name = Sys::getHeader('http://tracker.0day.kiev.ua/details.php?id='.$torrent_id);
                                        //обновляем заголовок торрента в базе
                                        Database::setNewName($id, $name);
                                    }
                                    
                                    $message = $name.' обновлён.';
                                    $status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str);
                                
                                    //обновляем время регистрации торрента в базе
                                    Database::setNewDate($id, $date);
                                }
                            }
                        }
                        else
                        {
                            //устанавливаем варнинг
                            if (tracker0daykievua::$warning == NULL)
                            {
                                tracker0daykievua::$warning = TRUE;
                                Errors::setWarnings($tracker, 'not_available');
                            }
                            //останавливаем процесс выполнения, т.к. не может работать без кук
                            tracker0daykievua::$exucution = FALSE;
                        }
                    }
                    else
                    {
                        //устанавливаем варнинг
                        if (tracker0daykievua::$warning == NULL)
                        {
                            tracker0daykievua::$warning = TRUE;
                            Errors::setWarnings($tracker, 'not_available');
                        }
                        //останавливаем процесс выполнения, т.к. не может работать без кук
                        tracker0daykievua::$exucution = FALSE;
                    }
                }
                else
                {
                    //устанавливаем варнинг
                    if (tracker0daykievua::$warning == NULL)
                    {
                        tracker0daykievua::$warning = TRUE;
                        Errors::setWarnings($tracker, 'not_available');
                    }
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    tracker0daykievua::$exucution = FALSE;
                }
            }
            else
            {
                //устанавливаем варнинг
                if (tracker0daykievua::$warning == NULL)
                {
                    tracker0daykievua::$warning = TRUE;
                    Errors::setWarnings($tracker, 'not_available');
                }
                //останавливаем процесс выполнения, т.к. не может работать без кук
                tracker0daykievua::$exucution = FALSE;
            }
        }
    }
    
    // функция генерирует url ссылку на раздачу
    public static function generateURL($tracker, $torrent_id) {
        return 'http://'.$tracker.'/details.php?id='.$torrent_id;
    }
}
?>