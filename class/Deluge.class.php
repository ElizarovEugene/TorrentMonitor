<?php
class Deluge
{
    #выполняем JSON-RPC запрос к deluge-web
    private static function request($baseUrl, $cookieFile, $method, array $params = array())
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => $baseUrl.'/json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS     => json_encode(array('method' => $method, 'params' => $params, 'id' => 1)),
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_TIMEOUT        => 30,
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === FALSE || $error !== '' || $httpCode != 200)
            return NULL;

        $data = json_decode($response, true);
        if ( ! is_array($data) || ! empty($data['error']))
            return NULL;

        return isset($data['result']) ? $data['result'] : NULL;
    }

    #добавляем новую закачку в torrent-клиент, обновляем hash в базе
    public static function addNew($id, $file, $hash, $tracker)
    {
        #получаем настройки из базы
        $settings = Database::getAllSetting();
        foreach ($settings as $row)
        {
        	extract($row);
        }
        if (!preg_match('~^https?://~i', $torrentAddress))
            $torrentAddress = 'http://'.$torrentAddress;

        $individualPath = Database::getTorrentDownloadPath($id);
        if ( ! empty($individualPath))
            $pathToDownload = $individualPath;

        $cookieFile = tempnam(sys_get_temp_dir(), 'deluge');

        try
        {
            #авторизация в deluge-web
            $loggedIn = self::request($torrentAddress, $cookieFile, 'auth.login', array($torrentPassword));
            if ($loggedIn !== TRUE)
            {
                $return['status'] = FALSE;
                $return['msg'] = 'log_passwd';
                return $return;
            }

            #проверяем подключение к демону, при необходимости подключаемся к первому доступному хосту
            $connected = self::request($torrentAddress, $cookieFile, 'web.connected');
            if ($connected !== TRUE)
            {
                $hosts = self::request($torrentAddress, $cookieFile, 'web.get_hosts');
                if (empty($hosts[0][0]))
                {
                    $return['status'] = FALSE;
                    $return['msg'] = 'connect_fail';
                    return $return;
                }

                $connectResult = self::request($torrentAddress, $cookieFile, 'web.connect', array($hosts[0][0]));
                if ($connectResult === NULL)
                {
                    $return['status'] = FALSE;
                    $return['msg'] = 'connect_fail';
                    return $return;
                }
            }

            if ( ! empty($hash))
            {
                $removeData = FALSE;
                $doRemove = TRUE;
                if ($tracker == 'lostfilm.tv' || $tracker == 'lostfilm-mirror' ||  $tracker == 'baibako.tv' || $tracker == 'newstudio.tv')
                {
                    $removeData = ! empty($deleteOldFiles);
                    $doRemove = ! empty($deleteDistribution);
                }

                if ($doRemove)
                {
                    #удаляем существующую закачку из torrent-клиента
                    self::request($torrentAddress, $cookieFile, 'core.remove_torrent', array($hash, $removeData));
                }
            }

            #добавляем торрент в torrent-клиент
            $fileContent = file_get_contents($file);
            if ($fileContent === FALSE)
            {
                $return['status'] = FALSE;
                $return['msg'] = 'add_fail';
                return $return;
            }

            $hashNew = self::request($torrentAddress, $cookieFile, 'core.add_torrent_file', array(
                basename($file),
                base64_encode($fileContent),
                array('download_location' => $pathToDownload),
            ));

            if (empty($hashNew))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'add_fail';
            }
            else
            {
                #обновляем hash в базе
                Database::updateHash($id, $hashNew);

                //сбрасываем варнинг
                Database::clearWarnings('Deluge');
                $return['status'] = TRUE;
                $return['hash'] = $hashNew;
            }
        }
        finally
        {
            @unlink($cookieFile);
        }

        return $return;
    }

    #удаляем раздачу из torrent-клиента (без добавления новой)
    public static function remove($hash)
    {
        $settings = Database::getAllSetting();
        foreach ($settings as $row)
        {
            extract($row);
        }

        if (!preg_match('~^https?://~i', $torrentAddress))
            $torrentAddress = 'http://'.$torrentAddress;

        $cookieFile = tempnam(sys_get_temp_dir(), 'deluge');
        try
        {
            $loggedIn = self::request($torrentAddress, $cookieFile, 'auth.login', array($torrentPassword));
            if ($loggedIn !== TRUE)
                return array('status' => FALSE, 'msg' => 'log_passwd');

            $connected = self::request($torrentAddress, $cookieFile, 'web.connected');
            if ($connected !== TRUE)
            {
                $hosts = self::request($torrentAddress, $cookieFile, 'web.get_hosts');
                if (empty($hosts[0][0]))
                    return array('status' => FALSE, 'msg' => 'connect_fail');

                if (self::request($torrentAddress, $cookieFile, 'web.connect', array($hosts[0][0])) === NULL)
                    return array('status' => FALSE, 'msg' => 'connect_fail');
            }

            self::request($torrentAddress, $cookieFile, 'core.remove_torrent', array($hash, ! empty($deleteOldFiles)));

            Database::clearWarnings('Deluge');
            return array('status' => TRUE);
        }
        finally
        {
            @unlink($cookieFile);
        }
    }
}
?>
