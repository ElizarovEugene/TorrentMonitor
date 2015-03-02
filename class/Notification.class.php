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
	
	public static function sendMail($settingEmail, $date, $tracker, $message, $header_message, $name=0)
	{
        $headers = 'From: TorrentMonitor'."\r\n";
		$headers .= 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8'."\r\n";
		$msg = 'Дата: '.$date.'<br>Трекер: '.$tracker.'<br>Сообщение: '.$message."\r\n";
		if ($name != '' || $name != 0)
		{
    		if ($tracker == 'rutracker.org' || $tracker == 'nnm-club.me' || $tracker == 'tfile.me' || $tracker == 'torrents.net.ua' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
    			$msg .= "http://{$tracker}/forum/viewtopic.php?t={$name}";
    		elseif ($tracker == 'kinozal.tv'  || $tracker == 'animelayer.ru' || $tracker == 'tracker.0day.kiev.ua')
        	    $msg .= "http://{$tracker}/details.php?id={$name}";
    		elseif ($tracker == 'rutor.org')
    			$msg .= "http://alt.rutor.org/torrent/{$name}/";
    		elseif ($tracker == 'anidub.com')
                $msg .= "http://tr.anidub.com/{$name}";
            elseif ($tracker == 'casstudio.tv')
    		    $msg .= "http://casstudio.tv/viewtopic.php?t={$name}";
        }

		mail($settingEmail, '=?UTF-8?B?'.base64_encode("TorrentMonitor: ".$header_message).'?=', $msg, $headers);
	}
	
	public static function sendPushover($sendUpdatePushover, $date, $tracker, $message)
	{
	    $msg = 'Дата: '.$date."\r\n".'Трекер: '.$tracker."\r\n".'Сообщение: '.$message."\r\n";
        $postfields = 'token=a9784KuYUoUdT4z47BassBLxWQGqFV&user='.$sendUpdatePushover.'&message='.$msg;
        $forumPage = Sys::getUrlContent(
        	array(
        		'type'           => 'POST',
        		'header'         => 1,
        		'returntransfer' => 1,
        		'url'            => 'https://api.pushover.net/1/messages.json',
                'postfields'     => $postfields,
        	)
        );
	}

	public static function sendNotification($type, $date, $tracker, $message, $name=0)
	{
		if ($type == 'warning')
			$header_message = 'Предупреждение.';
		if ($type == 'notification')
			$header_message = 'Обновление.';
			
        $send = Database::getSetting('send');
        if ($send)
        {
            if ($type == 'warning')
            {
                $sendWarning = Database::getSetting('sendWarning');
                if ($sendWarning)
                {
                    $sendWarningEmail = Database::getSetting('sendWarningEmail');
                    if ( ! empty($sendWarningEmail))
                        Notification::sendMail($sendWarningEmail, $date, $tracker, $message, $header_message);
                        
                    $sendWarningPushover = Database::getSetting('sendWarningPushover');
                    if ( ! empty($sendWarningPushover))
                        Notification::sendPushover($sendWarningPushover, $date, $tracker, $message, $header_message);
                }
            }

            if ($type == 'notification')
            {
                $sendUpdate = Database::getSetting('sendUpdate');
                if ($sendUpdate)
                {
                    $sendUpdateEmail = Database::getSetting('sendUpdateEmail');
                    if ( ! empty($sendUpdateEmail))
                        Notification::sendMail($sendUpdateEmail, $date, $tracker, $message, $header_message, $name);
                        
                    $sendUpdatePushover = Database::getSetting('sendUpdatePushover');
                    if ( ! empty($sendUpdatePushover))
                        Notification::sendPushover($sendUpdatePushover, $date, $tracker, $message, $header_message);
                }
            }
		}
	}
}
?>