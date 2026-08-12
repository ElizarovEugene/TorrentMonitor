<?php
//Класс для параллельного выполнения HTTP-запросов через curl_multi с дедупликацией одинаковых запросов
class CurlMultiFetcher
{
    private $handles = array();
    private $aliases = array();
    private $requestMeta = array();
    private $defaultOptions = array();
    private $concurrencyLimit;
    private $cfFallback;

    public function __construct($cfFallback = true)
    {
        $this->cfFallback = $cfFallback;
        $this->defaultOptions = array(
            CURLOPT_USERAGENT         => Database::getSetting('userAgent'),
            CURLOPT_TIMEOUT           => Database::getSetting('httpTimeout'),
            CURLOPT_RETURNTRANSFER    => 1,
            CURLOPT_DNS_CACHE_TIMEOUT => 0,
        );
        $limit = (int) Config::read('curl.concurrency');
        $this->concurrencyLimit = ($limit > 0) ? $limit : 10;
    }

    //регистрируем запрос; если такой же url+options уже зарегистрирован — добавляем $id как алиас существующего запроса
    public function add($id, $url, array $curlOptions = array())
    {
        $key = md5($url.serialize($curlOptions));

        if (isset($this->handles[$key]))
        {
            $this->aliases[$key][] = $id;
            return;
        }

        $options = $this->defaultOptions;
        $options[CURLOPT_URL] = $url;
        foreach ($curlOptions as $opt => $val)
            $options[$opt] = $val;

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $this->handles[$key] = $ch;
        $this->aliases[$key] = array($id);
        $this->requestMeta[$key] = array(
            'url'    => $url,
            'cookie' => isset($curlOptions[CURLOPT_COOKIE]) ? $curlOptions[CURLOPT_COOKIE] : '',
        );
    }

    //выполняем запросы параллельно с лимитом одновременных соединений (скользящее окно)
    //возвращаем $id => ['body'=>..., 'http_code'=>..., 'error'=>...]
    public function execute()
    {
        $results = array();

        if (empty($this->handles))
            return $results;

        $queue    = $this->handles;  // ключ => ch, ещё не добавленные в multi
        $chToKey  = array();         // int(ch) => key, для обратного поиска по handle
        $mh       = curl_multi_init();

        // запускаем первую порцию до лимита
        $initial = array_splice($queue, 0, $this->concurrencyLimit);
        foreach ($initial as $key => $ch)
        {
            curl_multi_add_handle($mh, $ch);
            $chToKey[(int)$ch] = $key;
        }

        $running = null;
        while (!empty($chToKey))
        {
            do
            {
                $status = curl_multi_exec($mh, $running);
                if ($running)
                    curl_multi_select($mh);
            }
            while ($running && $status == CURLM_OK);

            // обрабатываем все завершившиеся дескрипторы
            while (($info = curl_multi_info_read($mh)) !== false)
            {
                if ($info['msg'] !== CURLMSG_DONE)
                    continue;

                $ch  = $info['handle'];
                $key = $chToKey[(int)$ch];
                unset($chToKey[(int)$ch]);

                $body     = curl_multi_getcontent($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error    = curl_error($ch);

                $meta = $this->requestMeta[$key];

                // rutracker.org исключён: у него своя explicit-обработка CF-challenge
                // в rutracker.org.engine.php::parse() (сохраняет cf_clearance/UA для dl.php).
                // Если фолбэк отработает здесь, parse() получит уже решённую (или пустую)
                // страницу и никогда не увидит CF-challenge — cf_cookies/cf_userAgent не сохранятся.
                if ($this->cfFallback && ($httpCode == 403 || $httpCode == 503) && !empty($body) && Sys::isCloudflarePage($body) && strpos($meta['url'], 'rutracker.org') === false)
                {
                    $fsResult = Sys::getViaFlareSolverr($meta['url'], $meta['cookie']);
                    if ($fsResult !== null)
                    {
                        $body     = $fsResult['body'];
                        $httpCode = $fsResult['status'];
                        $error    = '';
                    }
                    else
                    {
                        // Byparr не смог обойти CF — возвращаем пустое тело,
                        // чтобы parse() не делал повторный вызов Byparr
                        $body  = '';
                        $error = 'cf_bypass_failed';
                    }
                }

                foreach ($this->aliases[$key] as $id)
                {
                    $results[$id] = array(
                        'body'      => $body,
                        'http_code' => $httpCode,
                        'error'     => $error,
                    );
                }

                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);

                // добавляем следующий из очереди, если есть
                if (!empty($queue))
                {
                    $nextKey = key($queue);
                    $nextCh  = array_shift($queue);
                    curl_multi_add_handle($mh, $nextCh);
                    $chToKey[(int)$nextCh] = $nextKey;
                }
            }
        }

        curl_multi_close($mh);

        $this->handles     = array();
        $this->aliases     = array();
        $this->requestMeta = array();

        return $results;
    }
}
?>
