<?php
class Notification
{
    private static $instance;
	private static $headers;
	private static $errors;

    private function __construct()
    {
    	$this->headers = "From: TorrentMonitor\r\n";
		$this->headers .= "MIME-Version: 1.0\r\n";
		$this->headers .= "Content-type: text/html; charset=utf-8\r\n";
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

	public static function findWarning()
    {
    	$trackersArray = Database::getTrackersList();
    	foreach ($trackersArray as $tracker)
    	{
    		$warningsCount = Database::getWarningsCount($tracker);
    		if ($warningsCount == 1)
    		{
    			$warningsArray = Database::getWarnings($tracker);
    			Notification::sendNotification('warning', $warningsArray['time'], $tracker, $warningsArray['reason']);
    		}
    	}
	}
	
	public static function send($settingEmail, $date, $tracker, $message, $header_message)
	{
		$msg = "Дата: {$date}\nТрекер: {$tracker}\nСообщение: {$message}";
		mail($settingEmail, '=?UTF-8?B?'.base64_encode("TorrentMonitor 2: ".$header_message).'?=', $msg, Notification::getInstance()->headers);		
	}
	
	public static function sendNotification($type, $date, $tracker, $message)
	{
		if ($type == 'warning')
			$header_message = 'Предупреждение.';
		if ($type == 'notification')
			$header_message = 'Обновление.';

		$settingEmail = Database::getSetting('email');
		if ( ! empty($settingEmail))
		{
			if ($type == 'warning')
			{
				if (Database::getSetting('send_warning'))
					Notification::send($settingEmail, $date, $tracker, $message, $header_message);
			}
			if ($type == 'notification')
			{
				if (Database::getSetting('send'))
					Notification::send($settingEmail, $date, $tracker, $message, $header_message);
			}
		}
	}
}
?>