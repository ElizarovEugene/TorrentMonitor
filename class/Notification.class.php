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
	
	public static function generateMessage($date, $tracker, $message, $header_message, $name)
	{
        if (is_string($tracker))
            $msg = "<b>".$header_message."</b>\r\n".'<i>Дата:</i>  '.$date."\r\n".'<i>Трекер:</i>  '.$tracker."\r\n".'<i>Сообщение:</i>  '.$message."\r\n";
        else
            $msg = $message;
            
		if ($name != 0)
		{
    		if ($tracker !== 'baibako.tv' && $tracker !== 'hamsterstudio.org' && $tracker !== 'lostfilm.tv' && $tracker !== 'lostfilm-mirror' && $tracker !== 'newstudio.tv')
    		{
        		$msg .= 'Ссылка на тему: ';
        		if ($tracker == 'rutracker.org' || $tracker == 'nnmclub.to' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
        			$msg .= "http://{$tracker}/forum/viewtopic.php?t={$name}";
        		elseif ($tracker == 'kinozal.tv' || $tracker == 'kinozal.me'  || $tracker == 'kinozal.guru' || $tracker == 'animelayer.ru')
            	    $msg .= "http://{$tracker}/details.php?id={$name}";
        		elseif ($tracker == 'rutor.is')
        			$msg .= "http://rutor.is/torrent/{$name}/";
        		elseif ($tracker == 'anidub.com')
                    $msg .= "http://tr.anidub.com{$name}";
                elseif ($tracker == 'casstudio.tk' || $tracker == 'booktracker.org')
        		    $msg .= "https://{$tracker}/viewtopic.php?t={$name}";
            }
        }           
    	return $msg;
	}
	
	public static function sendMail($email, $date, $tracker, $message, $header_message, $name)
	{
        $headers = 'From: TorrentMonitor'."\r\n";
		$headers .= 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8'."\r\n";

        $msg = Notification::generateMessage($date, $tracker, $message, $header_message, $name);

		mail($email, '=?UTF-8?B?'.base64_encode("TorrentMonitor: ".$header_message).'?=', $msg, $headers);
	}
	
	public static function sendPushover($pushover, $date, $tracker, $message, $header_message, $name)
	{
	    $msg = Notification::generateMessage($date, $tracker, $message, $header_message, $name);
        $msg = strip_tags($msg);

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
    	$msg = Notification::generateMessage($date, $tracker, $message, $header_message, $name);

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
    	$msg = Notification::generateMessage($date, $tracker, $message, $header_message, $name);

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
    	$msg = Notification::generateMessage($date, $tracker, $message, $header_message, $name);
        
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
        $msg = Notification::generateMessage($date, $tracker, $message, $header_message, $name);

        $pieces = explode(';', $telegram);
        $url = "https://api.telegram.org/bot" . $pieces[0] . "/sendMessage";
        
        // Modified to support message_thread_id
        for ($i = 1; array_key_exists($i, $pieces); $i++)
        {
            // Check if the piece contains thread ID (format: "chat_id:thread_id")
            $chatDetails = explode(':', $pieces[$i]);
            $chatId = $chatDetails[0];
            
            $postfields = array(
                'chat_id' => $chatId,
                'text' => $msg,
                'disable_web_page_preview' => 1,
                'parse_mode' => 'HTML'
            );
            
            // Add message_thread_id if provided
            if (isset($chatDetails[1])) {
                $postfields['message_thread_id'] = $chatDetails[1];
            }
            
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
                    if ($service['service'] == 'Telegram')
                    {
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
                    if ($service['service'] == 'Telegram')
                    {
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
