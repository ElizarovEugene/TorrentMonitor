<?php
class Errors
{
    static $errorsArray;
    private static $instance;
    
    private function __construct()
    {
    	Errors::write('curl', 'Для работы системы необходимо включить <a href=\"http://php.net/manual/en/book.curl.php\">расширение cURL</a>.');
    	Errors::write('missing_files', 'Не хватает файлов для работы системы.');
    	Errors::write('credential_miss', 'Не указаны учётные данные для трекера.');

		Errors::write('not_available', 'Не могу получить доступ к трекеру.');
		Errors::write('credential_wrong', 'Неправильные учётные данные.');
		Errors::write('cant_get_forum_page', 'Не удалось получить страницу трекера.');
		Errors::write('cant_get_auth_page', 'Не удалось получить страницу авторизацию трекера.');
		Errors::write('cant_find_date', 'Не удалось найти время обновления торрента.');
		Errors::write('cant_find_cookie', 'Не удалось найти куки.');
		Errors::write('cant_find_dowload_link', 'Не удалось найти ссылку для скачивания.');
		Errors::write('rss_parse_false', 'Ошибка при чтении XML файла RSS ленты.');
		Errors::write('cant_find_rss', 'Не удалось получить RSS ленту трекера.');
		Errors::write('max_torrent', 'Вы использовали доступное Вам количество торрент-файлов в сутки.');
		Errors::write('torrent_file_fail', 'Не удалось получить данные torrent-файла.');
		Errors::write('save_file_fail', 'Не удалось сохранить torrent-файл в директорию.');
		Errors::write('limit', 'Превышен лимит попыток входа в профиль. Необходимо остановить ТМ на 2-3 часа.');
        Errors::write('captcha', 'При авторизации запрашивается капча. Необходимо остановить ТМ на 2-3 часа.');

		Errors::write('update', 'Невозможно проверить обновление системы.');
        Errors::write('update_fail', 'Невозможно обновить систему.');
    	Errors::write('update_news', 'Невозможно проверить новости.');

    	Errors::write('404', 'Не удалось добавить в torrent-клиент, неверная ссылка на torrent-файл.');
    	Errors::write('log_passwd', 'Не удалось подключиться к torrent-клиенту, неправильный логин или пароль.');
    	Errors::write('connect_fail', 'Не удалось подключиться к torrent-клиенту. Клиент  недоступен по указанному адресу.');
    	Errors::write('no_response', 'Не удалось добавить torrent-файл в torrent-клиент. Клиент не может получить доступ к файлу по указанному адресу. Проверьте адрес TorrentMonitor\'а в настройках.');
    	Errors::write('unauthorized', 'Не удалось добавить в torrent-клиент, не прошла авторизация в torrent-клиенте.');
		Errors::write('add_fail', 'Не удалось добавить torrent-файл в torrent-клиент.');
		Errors::write('duplicate_torrent', 'Не удалось добавить в torrent-клиент, такая закачка уже запущена.');
		Errors::write('unknown', 'Неизвестная ошибка при добавлении torrent-файла в torrent-клиент. Требуется дополнительная диагностика.');
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
    
	public static function setWarnings($tracker, $warning, $id = NULL)
    {
        $date = date('Y-m-d H:i:s');
        $debug = Database::getSetting('debug');
    	Database::setWarnings($date, $tracker, $warning, $id);
    	if ($debug)
    	    echo '<br>'."\r\n".$warning.'<br>'."\r\n";
		if ($id != NULL)
			Database::setErrorToThreme($id, 1);
    	$countErrors = Database::getWarningsCount($tracker);
    	if ($countErrors[0]['count'] == 1)
			Notification::sendNotification('warning', $date, $tracker, Errors::getWarning($warning), 0);
    }
}
?>