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
		$smtp = Database::getSetting('smtp');
		$mail = NULL;
		if  ($smtp) {
			require_once("class.phpmailer.php");
			$mail = new PHPMailer(); // create a new object
			$mail->IsSMTP(); // enable SMTP
			$mail->SMTPDebug = Database::getSetting('smtpDebug'); // debugging: 1 = errors and messages, 2 = messages only
			$mail->SMTPAuth = Database::getSetting('smtpAuth') == 1; // authentication enabled
			$mail->SMTPSecure = Database::getSetting('smtpSecure'); // secure transfer enabled REQUIRED for GMail
			$mail->Host = Database::getSetting('smtpHost');
			$mail->Port = Database::getSetting('smtpPort'); // or 587
			$mail->IsHTML(true);
			$mail->Username = Database::getSetting('smtpUser');
			$mail->Password = Database::getSetting('smtpPassword');
			$mail->FromName = "TorrentMonitor";
			$mail->From = Database::getSetting('smtpFrom');
			$mail->Subject = '=?UTF-8?B?'.base64_encode("TorrentMonitor: ".$header_message).'?=';
			$mail->AddAddress($settingEmail);
		}
		else {
        	$headers = 'From: TorrentMonitor'."\r\n";
        	$headers .= 'MIME-Version: 1.0'."\r\n";
        	$headers .= 'Content-type: text/html; charset=utf-8'."\r\n";
        }
		$msg = 'Дата: '.$date.'<br>Трекер: '.$tracker.'<br>Сообщение: '.$message."\r\n";
		if ($name != '' || $name != 0)
		{
    		if ($tracker == 'rutracker.org' || $tracker == 'nnm-club.me' || $tracker == 'tfile.me' || $tracker == 'torrents.net.ua' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
    			$msg .= "http://{$tracker}/forum/viewtopic.php?t={$name}";
    		elseif ($tracker == 'casstudio.tv' || $tracker == 'kinozal.tv'  || $tracker == 'animelayer.ru' || $tracker == 'tracker.0day.kiev.ua')
        	    $msg .= "http://{$tracker}/details.php?id={$name}";
    		elseif ($tracker == 'rutor.org')
    			$msg .= "http://alt.rutor.org/torrent/{$name}/";
    		elseif ($tracker == 'anidub.com')
                $msg .= "http://tr.anidub.com/{$name}";
        }
		if  ($smtp) {
			$mail->Body = $msg;
			$mail->AddAddress($settingEmail);
			if(!$mail->Send()){
		    	//echo "Mailer Error: " . $mail->ErrorInfo;
			}
			else{
		    	//echo "Message has been sent";
			}
		}
		else {
			mail($settingEmail, '=?UTF-8?B?'.base64_encode("TorrentMonitor: ".$header_message).'?=', $msg, $headers);
		}
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