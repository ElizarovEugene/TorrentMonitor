<?php
    
class Update {
    protected static $systemFail;
    protected static $databaseFail;
    protected static $isCLI;

    protected static $versionSystem;
    protected static $versionDatabase;
    protected static $updVersion;

    protected static $isUpdated;
    protected static $notNeeded;

    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.','..')); 
        foreach ($files as $file)
            (is_dir($dir.'/'.$file)) ? Update::delTree($dir.'/'.$file) : unlink($dir.'/'.$file);
        return rmdir($dir); 
    }
    
    private static function quary($query)
    {
        $error = Database::updateQuery($query);
        if (is_string($error))
        {
            Update::$databaseFail = TRUE;
            echo $error.'<br />'."\r\n";
        }
        else
            Update::$databaseFail = FALSE;
    }
    
    public static function main()
    {
        if (Sys::checkUpdate())
        {
            $page = Sys::getUrlContent(
                array(
                    'type'           => 'GET',
                    'returntransfer' => 1,
                    'url'            => 'http://korphome.ru/torrent_monitor/update.xml',
                )
            );
    
            (php_sapi_name() == 'cli') ? Update::$isCLI = TRUE : Update::$isCLI = FALSE;
    
            $xml_page = @simplexml_load_string($page);
            $ROOTPATH = str_replace('class', '', dirname(__FILE__));
            $dbType = Config::read('db.type');
            
            $count = count($xml_page->update) - 1;
            
            $version = json_decode(file_get_contents($ROOTPATH.'version.txt'));
            Update::$versionSystem = $version->system;
            Update::$versionDatabase = $version->database;
    
            echo 'Текущая версия системы: '.$version->system."\r\n".'<br />';
            echo 'Текущая версия базы данных: '.$version->database."\r\n".'<br />';
            for ($i=$count; $i>=0; $i--)
            {
                Update::$updVersion = strval($xml_page->update[$i]->version);
                $description = $xml_page->update[$i]->description;
                $files = $xml_page->update[$i]->files;
                $queryes = $xml_page->update[$i]->$dbType;
                $queryes_common = $xml_page->update[$i]->queryes;
                $deleteFolders = $xml_page->update[$i]->deleteFolders;
                $createFolders = $xml_page->update[$i]->createFolders;
    
                if (Update::$versionSystem < Update::$updVersion)
                {
                    Update::$isUpdated = FALSE;
                    echo 'Актуальная версия: ' . Update::$updVersion . "\r\n" . '<br />';
                    echo 'Changelog for ' . Update::$updVersion . ':' . "\r\n" . '<br />';
                    echo $description . "\r\n" . '<br />';
    
                    $file = Sys::getUrlContent(
                        array(
                            'type' => 'GET',
                            'returntransfer' => 1,
                            'url' => 'http://korphome.ru/torrent_monitor/tm-latest.zip',
                        )
                    );
    
                    #Обновление файлов системы
                    if (!empty($file))
                    {
                        if (file_put_contents($ROOTPATH . 'master.zip', $file))
                        {
                            $zip = new ZipArchive;
                            if ($zip->open($ROOTPATH . 'master.zip') === TRUE)
                            {
    
                                if (isset($deleteFolders->folder) && !empty($deleteFolders->folder))
                                {
                                    foreach ($deleteFolders->folder as $folder)
                                    {
                                        Update::delTree($ROOTPATH . $folder);
                                    }
                                }
                                if (isset($createFolders->create) && !empty($createFolders->create))
                                {
                                    foreach ($createFolders->create as $folder)
                                    {
                                        if (!mkdir($structure, 0777, true))
                                        {
                                            echo 'Не удалось создать директорию: ' . $file . ', обновление прервано.' . "\r\n" . '<br />';
                                            Update::$systemFail = TRUE;
                                        }
                                        else
                                        {
                                            Update::$systemFail = FALSE;
                                            Update::$isUpdated = TRUE;
                                        }
                                    }
                                }
    
                                $zip->extractTo($ROOTPATH . 'tmp');
                                $zip->close();
                                unlink($ROOTPATH . 'master.zip');
    
                                if (isset($files->file) && !empty($files->file))
                                {
                                    foreach ($files->file as $file)
                                    {
                                        if (!copy($ROOTPATH . 'tmp/TorrentMonitor-master/' . $file, $ROOTPATH . $file))
                                        {
                                            echo 'Не удалось скопировать файл: ' . $file . ', обновление прервано.' . "\r\n" . '<br />';
                                            Update::$systemFail = TRUE;
                                        }
                                        else
                                        {
                                            echo 'Файл: ' . $file . ' обновлён.' . "\r\n" . '<br />';
                                            Update::$systemFail = FALSE;
                                            Update::$isUpdated = TRUE;
                                        }
                                    }
    
                                    if (!Update::$systemFail)
                                        Update::$versionSystem = Update::$updVersion;
                                }
                                else
                                    Update::$versionSystem = Update::$updVersion;
    
                                Update::delTree($ROOTPATH . 'tmp');
                            }
                            else
                                echo 'Не могу разархивировать master.zip'."\r\n".'<br />';
                        }
                        else
                            echo 'Не могу сохранить master.zip'."\r\n".'<br />';
                    }
                    else
                        echo 'Не удалось скачать master.zip'."\r\n".'<br />';
                }
                else
                    Update::$notNeeded = TRUE;
    
                #Обновление базы данных
                if (Update::$versionDatabase < Update::$updVersion)
                {
                    if (isset($queryes->query) && ! empty($queryes->query))
                    {
                        foreach($queryes->query as $query)
                        {
                            Update::quary($query);
                        }
                    }
                    else
                        Update::$versionDatabase = Update::$updVersion;
                                
                    if (isset($queryes_common->query) && ! empty($queryes_common->query) )
                    {
                        foreach($queryes_common->query as $query)
                        {
                            Update::quary($query);
                        }
                    }
                    else
                        Update::$versionDatabase = Update::$updVersion;
    
                    if (!Update::$databaseFail)
                    {
                        Update::$versionDatabase = Update::$updVersion;
                        Update::$isUpdated = TRUE;
                    }
                }
                else
                    Update::$notNeeded = TRUE;
            }

            $version = NULL;
            $version['system'] = strval(Update::$versionSystem);
            $version['database'] = strval(Update::$versionSystem);
            file_put_contents($ROOTPATH . 'version.txt', json_encode($version));
    
            if (Update::$isUpdated)
            {
                $msg = 'Обновление до версии: ' . Update::$updVersion . ' выполнено успешно.' . "\r\n" . '<br />';
                echo $msg;
                $serverAddress = Database::getSetting('serverAddress');
                Database::clearWarnings('system');
                Database::setUpdateNotification(0);
                if (Update::$isCLI)
                    Notification::sendNotification('news', date('r'), 0, $msg, 0);
                else
                    echo 'Перейти на <a href="' . $serverAddress . '">главную страницу</a>.<br />';
            }
            elseif (Update::$notNeeded)
            {
                echo 'Текущая версия системы является актуальной.' . "\r\n" . '<br />';
            }
            else
            {
                Errors::setWarnings('system', 'update_fail');
                echo 'Обновление до версии: ' . Update::$updVersion . ' <b>не</b> выполнено.'  . "\r\n" . '<br />';
            }
        }
    }
}
?>