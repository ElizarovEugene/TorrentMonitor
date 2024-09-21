<?php
$dir = dirname(__FILE__).'/';

class SynologyDS
{
    public static $torrentAddress;
    public static $torrentLogin;
    public static $torrentPassword;
    public static $debug;
    public static $schema;

    private static function _login()
    {
        $response = json_decode(file_get_contents(self::$schema.'://'.self::$torrentAddress.'/webapi/auth.cgi?api=SYNO.API.Auth&version=7&method=login&account='.self::$torrentLogin.'&passwd='.self::$torrentPassword.'&session=DownloadStation&format=sid'));
        if ($response->success)
        {
            return $response->data->sid;
        }
        elseif ($response->error)
        {
            if (self::$debug)
                var_dump($response);
            return FALSE;
        }
    }

    private static function _logout()
    {
        file_get_contents(self::$schema.'://'.self::$torrentAddress.'/webapi/auth.cgi?api=SYNO.API.Auth&version=1&method=logout&session=DownloadStation');
    }

    private static function _list_downloads($sid)
    {
        return json_decode(file_get_contents(self::$schema.'://'.self::$torrentAddress.'/webapi/DownloadStation/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=list&additional=detail&_sid='.$sid));
    }

    private static function _download_already_exists($sid, $url)
    {
        $list = SynologyDS::_list_downloads($sid);
        if ($list === NULL)
            return NULL;

        foreach ($list->data->tasks as $task)
        {
            if ($task->additional->detail->uri == $url)
                return TRUE;
        }
        return FALSE;
    }

    private static function _find_id($sid)
    {
        $list = SynologyDS::_list_downloads($sid);
        if ($list === NULL)
            return NULL;

        $id = NULL;
        foreach ($list->data->tasks as $task)
        {
            $id = $task->id;
        }
        return $id;
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

        $pieces = explode(':', $torrentAddress);
        if ($pieces[1] == 5000)
            self::$schema = 'http';
        elseif ($pieces[1] == 5001)
            self::$schema = 'https';
        else
            self::$schema = 'http';

        self::$torrentAddress = $torrentAddress;
        self::$torrentLogin = $torrentLogin;
        self::$torrentPassword = $torrentPassword;
        self::$debug = $debug;

        $sid = SynologyDS::_login();
        if ($sid)
        {
    	    try
    	    {
                if ( ! empty($hash))
                {
                    if ($deleteDistribution)
                    {
                        $response = file_get_contents(self::$schema.'://'.$torrentAddress.'/webapi/DownloadStation/task.cgi?api=SYNO.DownloadStation.Task&version=3&method=delete&id='.$hash.'&_sid='.$sid);
                        if ($debug)
                            var_dump($response);
                    }
                }

                if ( ! SynologyDS::_download_already_exists($sid, $file))
                {
                    $individualPath = Database::getTorrentDownloadPath($id);
                    if ( ! empty($individualPath))
                        $pathToDownload = $individualPath;

                    $data = array(
                            'api' => 'SYNO.DownloadStation.Task',
                            'version' => '3',
                            'method' => 'create',
                            'session' => 'DownloadStation',
                            'uri' => $file,
                            'destination' => $pathToDownload,
                            '_sid' => $sid
                        );
                    $data = http_build_query($data);

                    if ($debug)
                        $param = TRUE;
                    else
                        $param = FALSE;

                    $ch = curl_init();
                    curl_setopt_array($ch, array(
                        CURLOPT_POST => 1,
                        CURLOPT_FOLLOWLOCATION => 1,
                        CURLOPT_URL => self::$schema.'://'.$torrentAddress.'/webapi/DownloadStation/task.cgi',
                        CURLOPT_POSTFIELDS => $data,
                        CURLOPT_VERBOSE => $param,
                    ));
                    $response = curl_exec($ch);
                    curl_close($ch);
                    if ($debug)
                        var_dump($response);

                    if (preg_match('/\"code\":403/i', $response))
                    {
                        $return['status'] = FALSE;
                        $return['msg'] = 'destination_does_not_exist';
                    }
                    else
                    {
                        $hashNew = SynologyDS::_find_id($sid);
                        if ($hashNew)
                        {
                            Database::updateHash($id, $hashNew);
                            Database::clearWarnings('SynologyDS');

                            $return['status'] = TRUE;
                            $return['hash'] = $id;
                        }
                        else
                        {
                            $return['status'] = FALSE;
                            $return['msg'] = 'add_fail';
                        }
                        SynologyDS::_logout();
                    }
                }
                else
                {
                    $return['status'] = FALSE;
                    $return['msg'] = 'duplicate_torrent';
                }
            }
            catch (Exception $e)
            {
                echo $e->getMessage().PHP_EOL;
            }
        }
        else
        {
            $return['status'] = FALSE;
            $return['msg'] = 'log_passwd';
        }

        return $return;
    }
}
?>