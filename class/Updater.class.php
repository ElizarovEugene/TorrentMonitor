<?php

require_once(dirname(dirname(__FILE__)).'/pclzip/pclzip.lib.php');



define("updatefolder", "update");
define("uipfile", "updateinprogress.txt");
define("configfile","config.php");

define("oldVersionUrl","class/Updater.class.php");

define("updatedir", detectPackageRoot()."/".updatefolder);
define("zipfile", detectPackageRoot()."/NewVersion.zip");
define("uip", detectPackageRoot()."/".uipfile);

define("newVersionUrl",updatefolder."/".oldVersionUrl);
define("redirectQuery","?autostart");

$donotdelete = array(updatefolder, uipfile, configfile);

function detectPackageRoot(){
    $dir = dirname(__FILE__);
    while(!file_exists($dir."/".configfile) and $dir != '/')
        $dir = dirname($dir);
    if(file_exists($dir."/".configfile))
        return $dir;
    else 
        return false;        
}

function delTree($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir("$dir/$object")) 
                    delTree("$dir/$object"); 
                else 
                    unlink("$dir/$object");
            }
        }
        reset($objects);
        return rmdir($dir);
    }
}

class Updater {
    private $steps;
    private $lastError;
    
    public function __construct()
    {
        $this->steps =  array();
        array_push($this->steps, new RedirectStep("1a",oldVersionUrl.redirectQuery));
        array_push($this->steps, new MsgStep("1b","Начинаю обновление"));
        array_push($this->steps, new MakeDirStep("1c"));
        array_push($this->steps, new MsgStep("1d","Начинаю скачивание"));
        array_push($this->steps, new DownloadStep("1e"));
        array_push($this->steps, new MsgStep("1f","Начинаю распаковку"));
        array_push($this->steps, new UnzipStep("1g"));
        array_push($this->steps, new MsgStep("1h","Переход к новой версии"));
        array_push($this->steps, new RedirectStep("1i",newVersionUrl.redirectQuery));
        array_push($this->steps, new MsgStep("2a","Переход осуществлен"));
        array_push($this->steps, new DeleteOldVersionStep("2b"));
        $this->lastError = '';
    }
    
    function makeError(){
        $error = array();
        $error["success"] = false;
        $error["message"] = $this->lastError;
        return json_encode($error);
    }
    
    function updateInProgress(){
        return file_exists(uip) and strlen(file_get_contents(uip)) > 0;
    }
    
    private function readStepID(){ 
        return file_get_contents(uip);
    }
    
    private function writeStepID($stepid){
        $file = @fopen(uip, 'wb');
        if(!$file)
            return false;
        return fwrite($file, $stepid) and 
               fclose($file);         
        
        //$result = file_put_contents(self::uip(), $stepid) and 
        //          chmod(self::uip(), 0666);
        return $result;
    }
    

    function seekToStep($stepid){
        reset($this->steps);
        while(key($this->steps) !== null && current($this->steps)->stepid !== $stepid)
            next($this->steps);
        if(!current($this->steps)){
            $this->Error("Не найден шаг ".$stepid);
            return false;
        }
        return true;
    }
    
    function getCurrentStep(){
        $stepid = $this->readStepID();
        if(!$stepid) {
            $this->Error("файл ".uip." не найден.");
            return false;
        }
        if($this->seekToStep($stepid))
            $step = current($this->steps);
        else
            $step = null;
        return $step;
    }

    function setAsCurrentStep($stepid){
        $result = $this->writeStepID($stepid);
        if(!$result)
            $this->Error("файл ".uip." недоступен для записи.");
        return $result;
    }
        
    function Error($msg){
        $this->lastError = $msg;        
    }
    
    function getNextStep($stepid){
        if($this->seekToStep($stepid))
            return next($this->steps);
        else
            return false;
    }
    
    function makeTheStep(Step $step){
        if(!$this->setAsCurrentStep($step->stepid))
            return $this->makeError();
        if(!$step->CheckConditions() or !$step->makeStep()){
            $this->Error($step->message);
            return $this->makeError();
        }
        $nextstep = $this->getNextStep($step->stepid);
        if($nextstep === false){
            $this->finish();
            return $step->makeFinished();
        }
        if (!$this->setAsCurrentStep($nextstep->stepid))
            return $this->makeError();
        return $step->result();
    }
    
    function makeFirstStep(){
        reset($this->steps);
        $step = current($this->steps);
        return $this->makeTheStep($step);
    }

    function makeCurrentStep(){
        if(!($step = $this->getCurrentStep()))
            return $this->makeError();
        return $this->makeTheStep($step);
    }

    function reset(){
       unlink(uip);    
    }
    
    function finish(){
        $this->reset();
    }
    
    function action(){
            header("Content-type","text/json");
            if($_REQUEST['action'] == "reset") {
                $this->reset();
                $result = $this->makeFirstStep();
                echo $result;
            }
            if($_REQUEST['action'] == "nextstep") {
                if($this->updateInProgress())
                    $result = $this->makeCurrentStep();            
                else
                    $result = $this->makeFirstStep();
                echo $result;
            }
    }
}

class Step {
    public $stepid;
    public $message;
    public function __construct($id) {
        $this->stepid = $id;
        $this->message = "";
    }
    public function makeStep(){
        $this->message = "Завершен шаг ".$this->stepid;
    	return true;
    }

    public function CheckConditions() {
        return true;
    }
    
    public function CheckPhase($phase) {
        return intval(substr($this->stepid,0,1)) == $phase;
    }
    
    function makeFinished(){
        $result = array();
        $result["success"] = true;
        $result["message"] = "Успех!";
        $result["progress"] = "Конец.";
        $result["nextstep"] = false; 
        return json_encode($result);
    }
    
    public function makeResult(){
        $result = array();
        $result["success"] = true;
        $result["message"] = $this->message;
        $result["progress"] = "Шаг ".$this->stepid." завершен."; 
        $result["nextstep"] = true; 
        return json_encode($result);
    }
    
    
    function makeRedirect($url){
        $result = array();
        $result["success"] = true;
        $result["message"] = "перенаправление...";
        $result["progress"] = "";
        $result["nextstep"] = false; 
        //$myurl = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_SPECIAL_CHARS);
        $packagerooturl = str_replace($_SERVER['DOCUMENT_ROOT'], '', detectPackageRoot());
        $result["redirect"] = $packagerooturl."/".$url;  
        return json_encode($result);
    }
    
    function result(){
        return $this->makeResult();
    }
    
}

class MsgStep extends Step {
    public function __construct($id, $msg) {
        parent::__construct($id);        
        $this->message = $msg;
    }
    public function makeStep(){
    	return true;
    }
}

class RedirectStep extends Step {
    private $url = '';
    public function __construct($id, $url) {
        parent::__construct($id);        
        $this->url = $url;
    }
    
    public function result(){
    	return $this->makeRedirect($this->url);
    }
}

class Phase1Step extends Step {
    protected function isOldVersion() {
        $this->message = "Проверка, что мы в старой версии";
        if(__FILE__ != detectPackageRoot()."/".oldVersionUrl){
            $this->message = "Необходимо перейти в старую версию!:".__FILE__."!=".detectPackageRoot()."/".oldVersionUrl;
            return false;
        } 
        return true;
    }
    
    public function CheckConditions() {
        return $this->CheckPhase(1) and $this->isOldVersion();
    }
}

class MakeDirStep extends Phase1Step {
    public function makeStep() {
    	$this->message = "Создание папки update";
    	$result = !is_dir(updatedir);
    	if(!$result) 
    	  $result = delTree(updatedir); 
        $result = $result and mkdir(updatedir);
        return $result;
    }
}

class DownloadStep extends Phase1Step {
    private static $url = "http://dev.local/NewVersion.zip";
    public function makeStep() {
    	$this->message = "Загрузка новой версии";
        $file = fopen(zipfile, 'wb');
        if(!$file){
            $this->message = "Не могу создать zip файл";
            return false;
        }
        $ch = curl_init(self::$url);
        $result  = curl_setopt($ch, CURLOPT_FILE , $file) and
        curl_setopt($ch, CURLOPT_TIMEOUT, 50) and
        curl_setopt($ch, CURLOPT_FAILONERROR, true) and
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true) and //does not work in safe_mode :(
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(!$result or $httpCode != 200){
            $this->message  = "Ошибка при загрузке ".self::$url.": ".curl_error($ch);
            return false;
        }        
        curl_close($ch);
        fclose($file);
        return $result;
    }
}

class UnzipStep extends Phase1Step {
    public function makeStep() {
        $this->message = "Распаковка новой версии";
        $archive = new PclZip(zipfile);
        $list  =  $archive->extract(PCLZIP_OPT_PATH, updatedir."/",
                                    PCLZIP_OPT_STOP_ON_ERROR);
        if( $list == 0){
            $this->message = "Ошибка при распаковке: '".$archive->errorInfo()."'";
            return false; 
        }
        return true;
    }
}

class Phase2Step extends Step {
    protected function isNewVersion() {
        $this->message = "Проверка, что мы в новой версии";
        if(__FILE__ != detectPackageRoot()."/".newVersionUrl){
            $this->message = "Старая версия не найдена: ".__FILE__."!=".detectPackageRoot()."/".newVersionUrl;
            return false;
        } 
        return true;
    }
    
    function CheckConditions() {
        return $this->CheckPhase(2) and $this->isNewVersion();
    }
}

class DeleteOldVersionStep extends Phase2Step {
    public function makeStep() {
        $this->message = "Удаление старой версии";
        return true;
    }
} 

if(array_key_exists("action",$_REQUEST)){
    $U = new Updater();
    $U->action();
} else {
?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<script type="text/javascript" src="../js/jquery-1.8.2.min.js"></script>
</head>
<script type="text/javascript">

function MakeNextStep(){
	setTimeout('TakeAction("nextstep")', 100);
}

function TakeAction(action){
	$.post("#",{"action": action},
		function(data) {
			if (data.success){
				$('#message').html(data.message);
				$('#progress').html(data.progress);
				if(data.nextstep)
				    ;//MakeNextStep();
				else if(data.redirect)
					window.location = data.redirect; 
            } 
			else {
				$('#error').html(data.message);
                if (confirm("Что-то не получилось. Попробуем еще раз?"))
                	MakeNextStep();
                else 
                    $('#message').html("Автоматическое обновление прервано. Попробуйте еще раз. если не получится - придется обновляться вручную.");
                    
            }
		}, "json"
	);
}

$(document).ready(function() {

	if(window.location.search.substr(1) == 'autostart')
		MakeNextStep();
});
	

</script>
<body>
<div id="message"></div>
<div id="progress"></div>
<div id="error"></div>
</body>
<button onclick="TakeAction('nextstep');">next</button>
<button onclick="TakeAction('reset');">Начать!</button>
</html>
<?php
} 
?>