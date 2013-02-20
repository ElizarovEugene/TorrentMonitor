<?php
interface ClientAdapter 
{
    public function store($torrent, $id, $tracker, $name, $torrent_id, $timestamp, array $context = array());
}
?>