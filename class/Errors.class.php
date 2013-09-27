<?php
class Errors
{
    static $errorsArray;
    private static $instance;
    
    private function __construct()
    {
    	Errors::write('curl', 'Для работы системы необходимо включить <a href=\"http://php.net/manual/en/book.curl.php\">расширение cURL</a>.');
    	Errors::write('missing_files', 'Не хватает файлов для работы системы.');
    	Errors::write('not_available', 'Не могу получить доступ к трекеру.');
    	Errors::write('credential_miss', 'Не указаны учётные данные для данного трекера.');
    	Errors::write('credential_wrong', 'Неправильные учётные данные.');
    	Errors::write('rss_parse_false', 'Ошибка при чтении XML файла RSS ленты.');
    	Errors::write('max_torrent', 'Вы использовали доступное Вам количество торрент-файлов в сутки.');
    	Errors::write('captcha', 'При логине запрашивается капча, следует отключить мониторинг на некоторое время.');
    	Errors::write('update', 'Невозможно проверить обновление системы.');
	}
	
	public static function getInstance()
    {
        if ( ! isset(self::$instance))
        {
            $object = __CLASS__;
            self::$instance = new $object;
        }
        return self::$instance;
    }
	
    public static function read($name)
    {
        return self::$errorsArray[$name];
    }

    public static function write($name, $value)
    {
        self::$errorsArray[$name] = $value;
    }
    
    public static function getWarning($warning)
    {
	 	return Errors::getInstance()->read($warning);
    }
    
	public static function setWarnings($tracker, $warning)
    {
        $date = date('Y-m-d H:i:s');
    	Database::setWarnings($date, $tracker, $warning);
    	$countErrors = Database::getWarningsCount($tracker);
    	if ($countErrors[0]['count'] == 1)
			Notification::sendNotification('warning', $date, $tracker, Errors::getWarning($warning));
    }
}
?>