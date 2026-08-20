<?php
class qBittorrent
{
    #добавляем новую закачку в torrent-клиент, удаляем старую и обновляем hash в базе
    public static function addNew($id, $file, $hash, $tracker)
    {
        #получаем настройки из базы
        $settings = Database::getAllSetting();
        foreach ($settings as $row)
        {
        	extract($row);
        }

        $individualPath = Database::getTorrentDownloadPath($id);
        $pathToDownload = !empty($individualPath) ? $individualPath : '';

        $category = Database::getTorrentCategory($id);
        if (empty($category))
            $category = Database::getSetting('qbitCategory');

        $data = array('username' => $torrentLogin, 'password' => $torrentPassword);
        
        // Единый User-Agent для стабильности API
        $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";

        // 1. Авторизация
        $MainCurl = curl_init();
        curl_setopt_array($MainCurl, array(
            CURLOPT_URL => $torrentAddress."/api/v2/auth/login",
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => http_build_query($data)
        ));
    
        $response = curl_exec($MainCurl);
        $httpCode = curl_getinfo($MainCurl, CURLINFO_RESPONSE_CODE);

        preg_match_all("/(QBT_)?SID(_\d+)?=(.*);/", $response, $match);

        if (($httpCode != 200 && $httpCode != 204) || empty($match[0][0]))
        {
            curl_close($MainCurl);
            $return['status'] = FALSE;
            $return['msg'] = 'log_passwd';
            return $return;
        }

        $cookie = $match[0][0];
        curl_close($MainCurl); // Закрываем сессию, чтобы избежать кэширования параметров

        // 2. Удаление старой раздачи (с учетом настроек сохранения файлов)
        if ( ! empty($hash))
        {
            $deleteFiles = 'false';
            // Логика удаления файлов только для RSS-сериалов
            if (($tracker == 'lostfilm.tv' || $tracker == 'lostfilm-mirror' || $tracker == 'baibako.tv' || $tracker == 'newstudio.tv') && !empty($deleteOldFiles))
            {
                $deleteFiles = 'true';
            }

            $ch_del = curl_init();
            curl_setopt_array($ch_del, array(
                CURLOPT_URL => $torrentAddress."/api/v2/torrents/delete",
                CURLOPT_USERAGENT => $userAgent,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => array(
                    "Cookie: ".$cookie,
                    "Referer: ".$torrentAddress."/" // Защита от CSRF
                ),
                CURLOPT_POSTFIELDS => http_build_query(array(
                    'hashes'      => trim($hash),
                    'deleteFiles' => $deleteFiles
                ))
            ));
            
            // Если включено удаление старых раздач или это серийный трекер
            if (!empty($deleteDistribution) || $deleteFiles == 'true') {
                curl_exec($ch_del);
            }
            curl_close($ch_del);
            
            // Задержка для предотвращения конфликта имен (race condition)
            sleep(1);
        }
        
        // 3. Добавление новой раздачи (Прямая загрузка CURLFile для избежания статуса pending)
        $tag = explode('.', $tracker)[0];
        $filename = urldecode(basename(parse_url($file, PHP_URL_PATH)));
        $localPath = dirname(__FILE__).'/../torrents/'.$filename;

        $data = array(
            'autoTMM'     => empty($pathToDownload) ? 'true' : 'false',
            'savepath'    => $pathToDownload,
            'root_folder' => 'true',
            'tags'        => $tag
        );
        
        if (!empty($category))
            $data['category'] = $category;

        if (file_exists($localPath)) {
            $data['torrents'] = new CURLFile($localPath, 'application/x-bittorrent', $filename);
        } else {
            $data['urls'] = $file;
        }
        
        $ch_add = curl_init();
        curl_setopt_array($ch_add, array(
            CURLOPT_URL => $torrentAddress."/api/v2/torrents/add",
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                "Cookie: ".$cookie,
                "Referer: ".$torrentAddress."/"
            ),
            CURLOPT_POSTFIELDS => $data
        ));
        $add_resp = curl_exec($ch_add);
        $add_code = curl_getinfo($ch_add, CURLINFO_RESPONSE_CODE);
        curl_close($ch_add);

        // 4. Проверка результата и получение нового хэша
        $return = array('status' => FALSE, 'msg' => 'add_fail');
        $hashNew = '';

        if ($add_code >= 200 && $add_code <= 204) {
            $resp_json = json_decode($add_resp, true);
            
            // Современный qBittorrent возвращает хэш прямо в ответе при загрузке CURLFile
            if (is_array($resp_json) && !empty($resp_json['added_torrent_ids'][0])) {
                $hashNew = $resp_json['added_torrent_ids'][0];
            } else {
                // Запасной план: ищем хэш по дате добавления
                sleep(2);
                $ch_info = curl_init();
                curl_setopt_array($ch_info, array(
                    CURLOPT_URL => $torrentAddress."/api/v2/torrents/info",
                    CURLOPT_USERAGENT => $userAgent,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => array(
                        "Cookie: ".$cookie,
                        "Referer: ".$torrentAddress."/"
                    ),
                    CURLOPT_POSTFIELDS => http_build_query(array(
                        'filter' => 'all',
                        'limit' => '1',
                        'sort' => 'added_on',
                        'reverse' => 'true'
                    ))
                ));
                $info_resp = curl_exec($ch_info);
                curl_close($ch_info);

                $rdata = json_decode($info_resp)[0];
                $hashNew = $rdata->hash;
            }

            if (!empty($hashNew)) {
                #обновляем hash в базе
                Database::updateHash($id, $hashNew);
                Database::clearWarnings('qBittorrent');
                
                $return['status'] = TRUE;
                $return['hash'] = $hashNew;
            }
        }

        // 5. Выход
        $ch_out = curl_init();
        curl_setopt_array($ch_out, array(
            CURLOPT_URL => $torrentAddress."/api/v2/auth/logout",
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                "Cookie: ".$cookie,
                "Referer: ".$torrentAddress."/"
            )
        ));
        curl_exec($ch_out);
        curl_close($ch_out);

        return $return;
    }

    #получаем поле comment торрента по его hash (для api-интеграции)
    public static function getTorrentComment($hash, $retries = 4, $delay = 3)
    {
        $settings = Database::getAllSetting();
        foreach ($settings as $row)
            extract($row);


        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $torrentAddress.'/api/v2/auth/login',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:51.0) Gecko/20100101 Firefox/51.0',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HEADER         => true,
            CURLOPT_POSTFIELDS     => http_build_query(['username' => $torrentLogin, 'password' => $torrentPassword]),
        ]);

        $response = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        preg_match_all('/(QBT_)?SID(_\d+)?=(.*);/', $response, $match);

        if (($httpCode != 200 && $httpCode != 204) || empty($match[0][0])) {
            curl_close($ch);
            return null;
        }

        $cookie = $match[0][0];
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        $comment = null;
        $hashLower = strtolower($hash);
        for ($i = 0; $i < $retries; $i++) {
            if ($i > 0)
                sleep($delay);

            curl_setopt($ch, CURLOPT_URL, $torrentAddress.'/api/v2/torrents/properties?hash='.urlencode($hashLower));
            $response = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            if ($httpCode === 200 && !empty($response)) {
                $data = json_decode($response, true);
                if (!empty($data['comment'])) {
                    $comment = $data['comment'];
                    break;
                }
            }
        }

        curl_setopt($ch, CURLOPT_URL, $torrentAddress.'/api/v2/auth/logout');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_exec($ch);
        curl_close($ch);

        return $comment;
    }

    #удаляем раздачу из torrent-клиента (без добавления новой)
    public static function remove($hash)
    {
        $settings = Database::getAllSetting();
        foreach ($settings as $row)
        {
            extract($row);
        }

        $data = array('username' => $torrentLogin, 'password' => $torrentPassword);

        $MainCurl = curl_init();
        curl_setopt_array($MainCurl, array(
            CURLOPT_URL => $torrentAddress."/api/v2/auth/login",
            CURLOPT_USERAGENT => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:51.0) Gecko/20100101 Firefox/51.0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => http_build_query($data)
        ));

        $response = curl_exec($MainCurl);
        $httpCode = curl_getinfo($MainCurl, CURLINFO_RESPONSE_CODE);
        preg_match_all("/(QBT_)?SID(_\d+)?=(.*);/", $response, $match);

        if (($httpCode != 200 && $httpCode != 204) || empty($match[0][0]))
        {
            curl_close($MainCurl);
            return array('status' => FALSE, 'msg' => 'log_passwd');
        }

        $cookie = $match[0][0];
        curl_setopt($MainCurl, CURLOPT_COOKIE, $cookie);
        curl_setopt($MainCurl, CURLOPT_HEADER, false);
        curl_setopt($MainCurl, CURLOPT_HTTPHEADER, array("Referer: ".$torrentAddress."/")); // Обход CSRF

        $data = array(
            'hashes' => $hash,
            'deleteFiles' => ! empty($deleteOldFiles) ? 'true' : 'false'
        );
        curl_setopt($MainCurl, CURLOPT_URL, $torrentAddress."/api/v2/torrents/delete");
        curl_setopt($MainCurl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_exec($MainCurl);

        Database::clearWarnings('qBittorrent');

        curl_setopt($MainCurl, CURLOPT_URL, $torrentAddress."/api/v2/auth/logout");
        curl_exec($MainCurl);
        curl_close($MainCurl);

        return array('status' => TRUE);
    }
}
?>
