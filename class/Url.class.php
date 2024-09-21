<?php
class Url
{
    private static $strackerslf = ['lostfilm.tv', 'lostfilm-mirror'];
    private static $strackers = ['baibako.tv', 'hamsterstudio.org', 'newstudio.tv'];

    public static function create($tracker, $name, $torrent_id = false, $hd = 0)
    {
        $data = [
            'tracker' => $tracker,
            'name'    => $name,
            'id'      => $torrent_id,
            'url'     => '',
            'quality' => false,
        ];

        if ($torrent_id) {
            $data['url'] = '<a href="' . self::href($tracker, $torrent_id) . '" target="_blank">' . $name . '</a>';
        }
        elseif (in_array($tracker, self::$strackerslf))
        {
            $data['url'] = '<a href="https://www.lostfilmtv5.site/search?q=' . urlencode($name) . '" target="_blank">' . $name . '</a>';
            if ($hd == 1) {
                $data['quality'] = '1080';
            } elseif ($hd == 2) {
                $data['quality'] = '720';
            } else {
                $data['quality'] = 'sd';
            }
        }
        elseif (in_array($tracker, self::$strackers))
        {
            $data['url'] = $name;
            if ($hd == 1) {
                $data['quality'] = '720';
            } elseif ($hd == 2) {
                $data['quality'] = '1080';
            } else {
                $data['quality'] = 'sd';
            }
        }

        return $data;
    }

    public static function href($tracker, $torrent_id)
    {
        $url = '';
        if ($tracker == 'rutracker.org' || $tracker == 'nnmclub.to' || $tracker == 'tfile.cc' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
        {
            $url = 'http' . (($tracker == 'nnmclub.to' or $tracker == 'rutracker.org') ? 's' : '') .'://' . $tracker . '/forum/viewtopic.php?t=' . $torrent_id;
        }
        elseif ($tracker == 'casstudio.tk' || $tracker == 'booktracker.org')
        {
            $url = 'http://' . $tracker . '/viewtopic.php?t=' . $torrent_id;
        }
        elseif ($tracker == 'kinozal.me' || $tracker == 'kinozal.tv' || $tracker == 'kinozal.guru')
        {
            $url = 'http://' . $tracker . '/details.php?id=' . $torrent_id;
        }
        elseif ($tracker == 'animelayer.ru' || $tracker == 'rutor.is')
        {
            $url = 'http://' . $tracker . '/torrent/' . $torrent_id;
        }
        elseif ($tracker == 'anidub.com')
        {
            $url = 'https://tr.anidub.com/' . $torrent_id;
        }
        elseif ($tracker == 'riperam.org')
        {
            $url = 'http://riperam.org' . $torrent_id;
        }
        elseif ($tracker == 'baibako.tv_forum')
        {
            $url = 'http://baibako.tv/details.php?id=' . $torrent_id;
        }
        

        return $url;
    }
}
