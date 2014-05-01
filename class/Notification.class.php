<?php
class Notification
{
	private static $errors;

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

	public static function send_pushbullet($settingPushBullet, $date, $tracker, $message, $header_message)
	{
      	$settingProxyAddress = (Database::getSetting('proxy') == 1) ? Database::getSetting('proxyAddress') : null;

		$pushbullet_api = Database::getSetting('pushbulletapi') . ":";
		echo $pushbullet_api;
		$devices = explode(",", Database::getSetting('pushbulletdevices'));
		$msg = 'Дата: ' . $date . '<br>Трекер: ' . $tracker . '<br>Сообщение: ' . $message;
		$basequery = http_build_query(array("type" => "note", "title" => "TorrentMonitor: " . $header_message, "body" => $msg));
		foreach ($devices as $device)
		{
		    $query = $basequery;
		    if (!empty($device))
		    {
		     $query = $basequery . "&" . http_build_query(array("device_iden" => $device));
		    }
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.pushbullet.com/api/pushes");
			curl_setopt($ch, CURLOPT_USERPWD, $pushbullet_api);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_NOBODY, TRUE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			curl_setopt($ch, CURLOPT_PROXY, $settingProxyAddress);
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
			curl_exec($ch);
			curl_close($ch);
		}
	}

	public static function send($settingEmail, $date, $tracker, $message, $header_message)
	{
        $headers = 'From: TorrentMonitor'."\r\n";
		$headers .= 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8'."\r\n";
		$msg = 'Дата: '.$date.'<br>Трекер: '.$tracker.'<br>Сообщение: '.$message;

		mail($settingEmail, '=?UTF-8?B?'.base64_encode("TorrentMonitor: ".$header_message).'?=', $msg, $headers);
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

		$settingPushBullet = Database::getSetting('pushbulletapi');
		if ( ! empty($settingPushBullet))
		{
			if ($type == 'warning')
			{
				if (Database::getSetting('send_warning_pushbullet'))
					Notification::send_pushbullet($settingPushBullet, $date, $tracker, $message, $header_message);
			}
			if ($type == 'notification')
			{
				if (Database::getSetting('send_pushbullet'))
					Notification::send_pushbullet($settingPushBullet, $date, $tracker, $message, $header_message);
			}
		}

	}
}
?>