<?php
class qBittorrent
{
    #добавляем новую закачку в torrent-клиент, обновляем hash в базе
    public static function addNew($id, $file, $hash, $tracker)
    {
        #получаем настройки из базы
        $settings = Database::getAllSetting();
        foreach ($settings as $row)
        {
        	extract($row);
        }
        $individualPath = Database::getTorrentDownloadPath($id);
        if ( ! empty($individualPath))
            $pathToDownload = $individualPath;

        $data = array('username' => $torrentLogin, 'password' => $torrentPassword);

        //Авторизация
        $MainCurl = curl_init();
        curl_setopt_array($MainCurl, array(
            CURLOPT_URL => $torrentAddress."/api/v2/auth/login",
            CURLOPT_USERAGENT => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:51.0) Gecko/20100101 Firefox/51.0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => http_build_query($data)
        ));
    
        $response=curl_exec($MainCurl);
    
        preg_match_all("/SID=(.*?);/", $response, $match);
        $cookie = "SID=".$match[1][0];
        curl_setopt($MainCurl, CURLOPT_COOKIE, $cookie);
        curl_setopt($MainCurl, CURLOPT_HEADER, false);

        if ( ! empty($hash))
        {
            $data = array(
                'hashes' => $hash,
                'deleteFiles' => 'false'
            );
            curl_setopt($MainCurl, CURLOPT_URL, $torrentAddress."/api/v2/torrents/delete");
            curl_setopt($MainCurl, CURLOPT_POSTFIELDS, http_build_query($data));

            if ($tracker == 'lostfilm.tv' || $tracker == 'lostfilm-mirror' ||  $tracker == 'baibako.tv' || $tracker == 'newstudio.tv')
            {
                if ($deleteOldFiles)
                    $data['deleteFiles'] = 'true';
                #удяляем существующую закачку из torrent-клиента
                if ($deleteDistribution)
                    curl_exec($MainCurl);
            }
            else
            {
                #удяляем существующую закачку из torrent-клиента
                curl_exec($MainCurl);
            }
        }
        
        //Формируется тело запроса
        $data = array(
            'urls' => $file,
            'autoTMM' => true,
            'savepath' => $pathToDownload,
            'root_folder' => true,
        );
        
        //формируется заголовок запроса
        $request_headers = array(
            "Cookie: ".$cookie
        );
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $torrentAddress."/api/v2/torrents/add",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $request_headers,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_POSTFIELDS => $data
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        if (preg_match('/Ok/', $response)) {
            sleep(3);
            
            //получение хэша торрента
            $data = array(
                'filter' => 'all',
                'limit' => '1',
                'sort' => 'added_on',
                'reverse' => 'true'
            );
            curl_setopt($MainCurl, CURLOPT_URL, $torrentAddress."/api/v2/torrents/info");
            curl_setopt($MainCurl, CURLOPT_POSTFIELDS, http_build_query($data));
            $response = curl_exec($MainCurl);
            $rdata = json_decode($response)[0];
            $hashNew = $rdata->hash;

            #обновляем hash в базе
            Database::updateHash($id, $hashNew);

            //сбрасываем варнинг
            Database::clearWarnings('qBittorrent');
            $return['status'] = TRUE;
            $return['hash'] = $hashNew;
        } else {
            $return['status'] = FALSE;
            $return['msg'] = 'add_fail';
        }

        //выход
        curl_setopt($MainCurl, CURLOPT_URL, $torrentAddress."/api/v2/auth/logout");
        curl_exec($MainCurl);
        curl_close($MainCurl);

        return $return;
    }
}
?>
