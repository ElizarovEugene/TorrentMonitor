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
    		if ($warningsCount != NULL)
    		{
        		if ($warningsCount == 1)
        		{
        			$warningsArray = Database::getWarnings($tracker);
        			Notification::sendNotification('warning', $warningsArray['time'], $tracker, $warningsArray['reason']);
        		}
            }
    	}
	}
	
	public static function sendMail($email, $date, $tracker, $message, $header_message, $name)
	{
        $headers = 'From: TorrentMonitor'."\r\n";
		$headers .= 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8'."\r\n";
		if (is_string($tracker))
		    $msg = 'Дата: '.$date.'<br>Трекер: '.$tracker.'<br>Сообщение: '.$message."\r\n";
        else
            $msg = $message;

		if ($name != '' || $name != 0)
		{
    		$msg .= '<br />Ссылка на тему: ';
    		if ($tracker == 'rutracker.org' || $tracker == 'nnmclub.to' || $tracker == 'tfile.cc' || $tracker == 'torrents.net.ua' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
    			$msg .= "http://{$tracker}/forum/viewtopic.php?t={$name}";
    		elseif ($tracker == 'kinozal.tv'  || $tracker == 'animelayer.ru' || $tracker == 'tracker.0day.kiev.ua')
        	    $msg .= "http://{$tracker}/details.php?id={$name}";
    		elseif ($tracker == 'rutor.org')
    			$msg .= "http://rutor.info/torrent/{$name}/";
    		elseif ($tracker == 'anidub.com')
                $msg .= "http://tr.anidub.com{$name}";
            elseif ($tracker == 'casstudio.tv' || $tracker == 'booktracker.org')
    		    $msg .= "http://{$tracker}/viewtopic.php?t={$name}";
        }

		mail($email, '=?UTF-8?B?'.base64_encode("TorrentMonitor: ".$header_message).'?=', $msg, $headers);
	}
	
	public static function sendPushover($pushover, $date, $tracker, $message, $header_message, $name)
	{
	    if (is_string($tracker))
		    $msg = 'Дата: '.$date."\r\n".'Трекер: '.$tracker."\r\n".'Сообщение: '.$message."\r\n";
        else
            $msg = $message;

        $pieces = explode(';', $pushover);
        $postfields = 'token='.$pieces[1].'&user='.$pieces[0].'&message='.$msg;
        $forumPage = Sys::getUrlContent(
        	array(
        		'type'           => 'POST',
        		'header'         => 1,
        		'returntransfer' => 1,
        		'url'            => 'https://api.pushover.net/1/messages.json',
        		'ssl_false'      => 1,
                'postfields'     => $postfields,
        	)
        );
	}
	
	public static function sendProwl($prowl, $date, $tracker, $message, $header_message, $name)
	{
    	if (is_string($tracker))
		    $msg = 'Дата: '.$date."\r\n".'Трекер: '.$tracker."\r\n".'Сообщение: '.$message."\r\n";
        else
            $msg = $message;

        $postfields = 'apikey='.$prowl.'&application=TorrentMonitor&event=Notification&description='.$msg;
        $forumPage = Sys::getUrlContent(
            array(
                'type'           => 'POST',
                'header'         => 1,
                'returntransfer' => 1,
                'url'            => 'https://api.prowlapp.com/publicapi/add',
                'ssl_false'      => 1,
                'postfields'     => $postfields,
            )
        );    	
	}
	
	public static function sendPushbullet($pushbullet, $date, $tracker, $message, $header_message, $name)
	{
    	if (is_string($tracker))
		    $msg = 'Дата: '.$date."\r\n".'Трекер: '.$tracker."\r\n".'Сообщение: '.$message."\r\n";
        else
            $msg = $message;

        $postfields = array('type' => 'note', 'title' => $header_message, 'body' => $msg);
        $forumPage = Sys::getUrlContent(
            array(
                'type'           => 'POST',
                'returntransfer' => 1,
                'url'            => 'https://api.pushbullet.com/v2/pushes',
                'ssl_false'      => 1,
                'userpwd'        => $pushbullet,
                'postfields'     => $postfields,
            )
        );        
	}
	
	public static function sendPushall($pushall, $date, $tracker, $message, $header_message, $name)
	{
    	if (is_string($tracker))
		    $msg = 'Дата: '.$date."\r\n".'Трекер: '.$tracker."\r\n".'Сообщение: '.$message."\r\n";
        else
            $msg = $message;
        
        $pieces = explode(';', $pushall);
        $postfields = array('type' => 'self', 'id' => $pieces[0], 'key' => $pieces[1], 'title' => $header_message, 'text' => $msg);
        $forumPage = Sys::getUrlContent(
            array(
                'type'           => 'POST',
                'returntransfer' => 1,
                'url'            => 'https://pushall.ru/api.php',
                'ssl_false'      => 1,
                'postfields'     => $postfields,
            )
        );
	}
	
    public static function sendTelegram($telegram, $date, $tracker, $message, $header_message, $name)
    {
        if (is_string($tracker))
            $msg = "<b>".$header_message."</b>\r\n".'<i>Дата:</i>  '.$date."\r\n".'<i>Трекер:</i>  '.$tracker."\r\n".'<i>Сообщение:</i>  '.$message."\r\n";
        else
            $msg = $message;
     
        $pieces = explode(';', $telegram);
        $url = "https://api.telegram.org/bot" . $pieces[0] . "/sendMessage";
        for ($i = 1; array_key_exists($i, $pieces); $i++)
        {
            $postfields = array('chat_id' => $pieces[$i], 'text' => $msg, 'disable_web_page_preview' => 1, 'parse_mode' => 'HTML');
            $forumPage = Sys::getUrlContent(
                array(
                    'type'           => 'POST',
                    'header'         => 1,
                    'returntransfer' => 1,
                    'url'            => $url,
                    'ssl_false'      => 1,
                    'postfields'     => $postfields,
                )
            );
        }
    }

	public static function sendNotification($type, $date, $tracker, $message, $name=0, $id=0)
	{    	
		if ($type == 'warning')
			$header_message = 'Предупреждение.';
		if ($type == 'notification')
			$header_message = 'Обновление.';
        if ($type == 'news')
			$header_message = 'Новость.';
			
        $send = Database::getSetting('send');
        if ($send)
        {
            if ($type == 'warning')
            {
                $sendWarning = Database::getSetting('sendWarning');
                if ($sendWarning)
                {
                    $service = Database::getService('sendWarningService');
                    if ($service['service'] == 'E-mail')
                    {
                        $service['service'] = 'Mail';
                        $name = $id;
                    }
                    if ( ! empty($service['address']))
                        call_user_func('Notification::send'.$service['service'], $service['address'], $date, $tracker, $message, $header_message, $name);
                }
            }

            if ($type == 'notification' || $type == 'news')
            {
                $sendUpdate = Database::getSetting('sendUpdate');
                if ($sendUpdate)
                {
                    $service = Database::getService('sendUpdateService');
                    if ($service['service'] == 'E-mail')
                    {
                        $service['service'] = 'Mail';
                        $name = $id;
                    }
                    if ($type == 'news')
                    {
                        $message = str_replace('<br>', "\r\n", $message);
                        $message = strip_tags($message);
                    }
                    if ( ! empty($service['address']))
                        call_user_func('Notification::send'.$service['service'], $service['address'], $date, $tracker, $message, $header_message, $name);
                }
            }
		}
	}
}
?>