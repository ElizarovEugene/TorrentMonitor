<?php

require_once(dirname(__DIR__)."/"."pclzip"."/"."pclzip.lib.php");
require_once(__DIR__."/"."DBUpgrade.class.php");


define("updateUrl", "http://dev.local/NewVersion.zip");

define("newVerSubfolder", "update");
define("uipfile", "updateinprogress.txt");
define("configfile","config.php");

define("oldVersionUrl","index.php?view=update");

define("updatedir", detectPackageRoot()."/".newVerSubfolder);
define("zipfile", detectPackageRoot()."/"."NewVersion.zip");
define("uip", detectPackageRoot()."/".uipfile);

define("newVersionUrl",newVerSubfolder."/".oldVersionUrl);
define("redirectQuery","&autostart");

function detectPackageRoot(){
    $dir = __DIR__;
    while(!(file_exists($dir."/".configfile) and basename($dir) != newVerSubfolder) and $dir != "/")
        $dir = dirname($dir);
    if(file_exists($dir."/".configfile))
        return $dir;
    else 
        return false;         
}

class Updater {
    private static $steps = NULL;
    private static $lastError;

    private static function Initialize()
    {
        if(!self::$steps) 
        {
            self::$steps =  array();
            array_push(self::$steps, new RedirectStep("1a",oldVersionUrl.redirectQuery));
            array_push(self::$steps, new MsgStep("1b","Начинаю обновление"));
            array_push(self::$steps, new MakeDirStep("1c"));
            array_push(self::$steps, new MsgStep("1d","Начинаю скачивание"));
            array_push(self::$steps, new DownloadStep("1e"));
            array_push(self::$steps, new MsgStep("1f","Начинаю распаковку"));
            array_push(self::$steps, new UnzipStep("1g"));
            array_push(self::$steps, new MsgStep("1h","Копирую файл конфигурации"));
            array_push(self::$steps, new CopyConfigStep("1h2"));
            array_push(self::$steps, new MsgStep("1h3","Переход к новой версии"));
            array_push(self::$steps, new RedirectStep("1i",newVersionUrl.redirectQuery));
            array_push(self::$steps, new MsgStep("2a","Переход осуществлен"));
            array_push(self::$steps, new DeleteOldVersionStep("2b"));
            array_push(self::$steps, new MsgStep("2с","Перемещаем новую версию"));
            array_push(self::$steps, new MoveNewVersionStep("2d"));
            array_push(self::$steps, new MsgStep("2e","Переходим к обновленной версии"));
            array_push(self::$steps, new RedirectStep("2f",oldVersionUrl.redirectQuery));
            array_push(self::$steps, new MsgStep("3a","Чистим за собой"));
            array_push(self::$steps, new DelUpdateDirStep("3b"));
            array_push(self::$steps, new MsgStep("3c","Обновляем БД"));
            array_push(self::$steps, new UpgradeDBStep("3d"));
            array_push(self::$steps, new MsgStep("3e","Проверка установки!"));
            array_push(self::$steps, new RedirectStep("3f",'index.php?view=check'));
            self::$lastError = '';
        }
    }
    
    private static function makeError(){
        $error = array();
        $error["success"] = false;
        $error["message"] = self::$lastError;
        return json_encode($error);
    }
    
    private static function updateInProgress(){
        return file_exists(uip) and strlen(file_get_contents(uip)) > 0;
    }
    
    private static function readStepID(){ 
        return file_get_contents(uip);
    }
    
    private static function writeStepID($stepid){
        $file = @fopen(uip, 'wb');
        if(!$file)
            return false;
        return fwrite($file, $stepid) and 
               fclose($file);         
        
        //$result = file_put_contents(self::uip(), $stepid) and 
        //          chmod(self::uip(), 0666);
        return $result;
    }
    

    private static function seekToStep($stepid){
        reset(self::$steps);
        while(key(self::$steps) !== null && current(self::$steps)->stepid !== $stepid)
            next(self::$steps);
        if(!current(self::$steps)){
            self::Error("Не найден шаг ".$stepid);
            return false;
        }
        return true;
    }
    
    private static function getCurrentStep(){
        $stepid = self::readStepID();
        if(!$stepid) {
            self::Error("файл ".uip." не найден.");
            return false;
        }
        if(self::seekToStep($stepid))
            $step = current(self::$steps);
        else
            $step = null;
        return $step;
    }

    private static function setAsCurrentStep($stepid){
        $result = self::writeStepID($stepid);
        if(!$result)
            self::Error("файл ".uip." недоступен для записи.");
        return $result;
    }
        
    private static function Error($msg){
        self::$lastError = $msg;        
    }
    
    private static function getNextStep($stepid){
        if(self::seekToStep($stepid))
            return next(self::$steps);
        else
            return false;
    }
    
    private static function makeTheStep(Step $step){
        if(!self::setAsCurrentStep($step->stepid))
            return self::makeError();
        
        $step->progress = intval(array_search($step, self::$steps) / count(self::$steps) * 100);
        
        if(!$step->CheckConditions() or !$step->makeStep()){
            self::Error($step->message);
            return self::makeError();
        }
        $nextstep = self::getNextStep($step->stepid);
        if($nextstep === false){
            self::finish();
            return $step->result(false);
        }
        if (!self::setAsCurrentStep($nextstep->stepid))
            return self::makeError();
        return $step->result(true);
    }
    
    private static function makeFirstStep(){
        reset(self::$steps);
        $step = current(self::$steps);
        return self::makeTheStep($step);
    }

    private static function makeCurrentStep(){
        if(!($step = self::getCurrentStep()))
            return self::makeError();
        
        return self::makeTheStep($step);
    }

    private static function reset(){
        if(file_exists(uip))
            unlink(uip);    
    }
    
    private static function finish(){
        self::reset();
    }
    
    public static function action($action){
        self::Initialize();
        header("Content-type","text/json");
        switch($action) {
        	case "update_reset":
        	    self::reset();
                $result = self::makeFirstStep();
                echo $result;
                break;
            case "update_nextstep":
                if(self::updateInProgress())
                    $result = self::makeCurrentStep();            
                else
                    $result = self::makeFirstStep();
                echo $result;
                break;
        }
    }
}

class Step {
    private static $donotdelete = array(newVerSubfolder, uipfile, configfile, 'laststart.txt');
    public $stepid;
    public $message;
    public $progress = 0;
    public function __construct($id) {
        $this->stepid = $id;
        $this->message = "&nbsp;";
    }
    public function makeStep(){
        $this->message = "Завершен шаг ".$this->stepid;
    	return true;
    }

    public function CheckConditions() {
        return true;
    }
    
    protected function CheckPhase($phase) {
        return intval(substr($this->stepid,0,1)) == $phase;
    }
    
    protected function xCopy($dir, $dest) {
        if (is_dir($dir)) {
            if(!is_dir($dest) and !mkdir($dest,0777,true)){
                $this->message = "Не могу создать $dest";
                return false;
            }
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if (!in_array($object, self::$donotdelete) and
                    !preg_match('/^\./', $object)) /* . .. .git etc */{
                    $obj = $dir."/".$object;
                    if (is_dir($obj)){
                        if(!$this->xCopy($obj, $dest."/".$object))
                            return false;
                    } else {
                        if(!rename($obj, $dest."/".$object) or //to keep original time and permissions 
                           !copy($dest."/".$object, $obj)){
                            $this->message = "Не могу скопировать $obj в $dest";
                            return false;
                        }
                    }
                }
            }
            return true;
        } 
        $this->message = "$dir - не директория!";
        return false;
    }
    
    
    protected function delTree($dir, $onlyContents = false) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ((!$onlyContents or !in_array($object, self::$donotdelete)) and 
                   !preg_match('/^\./', $object)) /* . .. .git etc */{
                    $obj = $dir."/".$object;
                    if (is_dir($obj)) {
                        if(!$this->delTree($obj, false))
                            return false; 
                    } else {
                        if(!unlink($obj)){
                            $this->message = "Не могу удалить $obj";
                            return false;
                        }
                    }
                }
            }
            reset($objects);
            if (!$onlyContents and !rmdir($dir)){
                $this->message = "Не могу удалить $dir";
                return false;
            }
            return true;
        }
    }
    
    public function makeFinished(){
        $result = array();
        $result["success"] = true;
        $result["message"] = "Успех!";
        $result["progress"] = 100;
        $result["nextstep"] = false; 
        return json_encode($result);
    }
    
    protected function makeResult($nextstep){
        $result = array();
        $result["success"] = true;
        $result["message"] = $this->message;
        $result["progress"] = $this->progress; 
        $result["nextstep"] = $nextstep; 
        return json_encode($result);
    }
    
    
    protected function makeRedirect($url){
        $result = array();
        $result["success"] = true;
        $result["message"] = "перенаправление...";
        $result["progress"] = $this->progress;
        $result["nextstep"] = false; 
        $packagerooturl = str_replace($_SERVER['DOCUMENT_ROOT'], '', detectPackageRoot());
        $result["redirect"] = $packagerooturl."/".$url;  
        return json_encode($result);
    }
    
    public function result($nextstep){
        return $this->makeResult($nextstep);
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
        $this->message = 'Перенаправление...';
        return $this->makeRedirect($this->url);
    }
}

class Phase1Step extends Step {
    protected function isOldVersion() {
        if(dirname(__DIR__) != detectPackageRoot()){
            $this->message = "Необходимо перейти в старую версию! Сейчас:".dirname(__DIR__).", нужно ".detectPackageRoot();
            return false;
        } 
        return true;
    }
    
    public function CheckConditions() {
        return $this->CheckPhase(1) and $this->isOldVersion();
    }
}

class Phase2Step extends Step {
    protected function isNewVersion() {
        if(dirname(__DIR__) != detectPackageRoot()."/".newVerSubfolder){
            $this->message = "Данный шаг должен выполняться в новой версии! Сейчас:".dirname(__DIR__).", нужно ".detectPackageRoot()."/".newVerSubfolder;
            return false;
        } 
        return true;
    }
    
    function CheckConditions() {
        return $this->CheckPhase(2) and $this->isNewVersion();
    }
}

class Phase3Step extends Phase1Step {
    public function CheckConditions() {
        return $this->CheckPhase(3) and $this->isOldVersion();
    }
}

class MakeDirStep extends Phase1Step {
    public function makeStep() {
    	$result = !is_dir(updatedir);
    	if(!$result) 
    	  $result = $this->delTree(updatedir); 
        $result = $result and mkdir(updatedir);
    	$this->message = "Папка update создана";
        return $result;
    }
}

class DownloadStep extends Phase1Step {
    public function makeStep() {
        $file = fopen(zipfile, 'wb');
        if(!$file){
            $this->message = "Не могу создать zip файл";
            return false;
        }
        $ch = curl_init(updateUrl);
        $result  = curl_setopt($ch, CURLOPT_FILE , $file) and
        curl_setopt($ch, CURLOPT_TIMEOUT, 50) and
        curl_setopt($ch, CURLOPT_FAILONERROR, true) and
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true) and //does not work in safe_mode :(
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(!$result or $httpCode != 200){
            $this->message  = "Ошибка при загрузке ".updateUrl.": ".curl_error($ch);
            curl_close($ch);
            return false;
        }        
        curl_close($ch);
        fclose($file);
    	$this->message = "Загрузка новой версии завершена";
        return true;
    }
}

class UnzipStep extends Phase1Step {
    public function makeStep() {
        $archive = new PclZip(zipfile);
        $list  =  $archive->extract(PCLZIP_OPT_PATH, updatedir."/",
                                    PCLZIP_OPT_STOP_ON_ERROR);
        if( $list == 0){
            $this->message = "Ошибка при распаковке: '".$archive->errorInfo()."'";
            return false; 
        }
        $this->message = "Распаковка новой версии завершена";
        return true;
    }
}

class CopyConfigStep extends Phase1Step {
    public function makeStep() {
        if (!copy(detectPackageRoot()."/".configfile, detectPackageRoot()."/".newVerSubfolder."/".configfile)){
            $this->message = "При копировании конфигурации.\n".
            "Измените права доступа или обновляйтесь вручную.";
            return false;
        }
        $this->message = "Копирование конфигурации завершено";
        return true;
    }
}

class DeleteOldVersionStep extends Phase2Step {
    public function makeStep() {
        if (!$this->delTree(detectPackageRoot(), true)){
            $this->message = "При удалении старой версии:\n".$this->message."\n".
            "Измените права доступа или обновляйтесь вручную.";
            return false;
        }
        $this->message = "Удаление старой версии завершено";
        return true;
    }
} 

class MoveNewVersionStep extends Phase2Step {
    public function makeStep() {
        if (!$this->xCopy(updatedir, detectPackageRoot())){
            $this->message = "При копировании новой версии:\n".$this->message."\n".
            "Измените права доступа или обновляйтесь вручную.";
            return false;
        }
        $this->message = "Замещение новой версией завершено";
        return true;
    }
} 

class UpgradeDBStep extends Phase3Step {
    public function makeStep() {
        if (!DBUpgrade::Upgrade()){
            $this->message = "Ошибка при апгрейде:\n".DBUpgrade::$lasterror;
            return false;
        }
        $this->message = "Апгрейд завершен";
        return true;
    }
}

class DelUpdateDirStep extends Phase3Step {
    public function makeStep() {
    	if(is_dir(updatedir) and !$this->delTree(updatedir)) {
            $this->message = "Ошибка при удалении копии.";
            return false;
    	} 
        $this->message = "Чистка завершена";
    	return true;
    }
}

?>