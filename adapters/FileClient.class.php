<?php
require_once 'ClientAdapter.interface.php';

class FileClient implements ClientAdapter
{
    public function store($torrent, $id, $tracker, $name, $torrent_id, $timestamp, array $context = array())
    {
        if (isset($context['filename']))
            $filename = $context['filename'];
		else
            $filename = sprintf('[%s]_%s.torrent', $tracker, $torrent_id);
        $path = Database::getSetting('path');
        file_put_contents($path . $filename, $torrent);
    }
}
?>