<?php

include_once dirname(__FILE__).'/Notifier.class.php';
include_once dirname(__FILE__).'/../class/Errors.class.php';

class EmailNotifier extends Notifier
{
    protected function localSend($address, $type, $date, $tracker, $message, $header_message, $name=0)
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

		$mail_result = mail($address, '=?UTF-8?B?'.base64_encode("TorrentMonitor: ".$header_message).'?=', $msg, $headers);
        if (!$mail_result)
            Errors::setWarnings('notifier', 'mail_fail');
        return 'Отправили уведомление на email &lt;'.$address.'&gt;<br />';
    }
}


?>
