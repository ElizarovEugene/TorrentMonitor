<?php

include_once dirname(__FILE__).'/../class/Notifier.class.php';
include_once dirname(__FILE__).'/../class/Errors.class.php';

class PushbulletNotifier extends Notifier
{
    public function VerboseName()
    {
        return "PushBullet";
    }

    public function Description()
    {
        return "Сервис уведомлений <a href='https://www.pushbullet.com/'>Pushbullet</a>";
    }

    protected function localSend($type, $date, $tracker, $message, $header_message, $name=0)
    {
        $msg = 'Дата: '.$date."\r\n".'Трекер: '.$tracker."\r\n".'Сообщение: '.$message."\r\n";
        $postData = array('type' => 'note',
                          'title' => $header_message,
                          'body' => $msg );

        $ch = curl_init('https://api.pushbullet.com/v2/pushes');
        curl_setopt_array($ch, array(CURLOPT_POST => TRUE,
                                     CURLOPT_RETURNTRANSFER => TRUE,
                                     CURLOPT_HTTPHEADER => array('Authorization: Bearer '.$this->SendAddress(),
                                                                 'Content-Type: application/json'),
                                     CURLOPT_POSTFIELDS => json_encode($postData) ));
        $response = curl_exec($ch);
        curl_close($ch);
        return array('success' => preg_match('/\"created\"/', $response), 'response' => $response);
    }
}


?>
