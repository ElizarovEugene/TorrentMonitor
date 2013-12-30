<?php

class Updater {
    private $steps;
    private $lastError;
    
    public function __construct()
    {
        $this->steps =  array();
        array_push($this->steps, new Step("1a"));
        array_push($this->steps, new Step("1b"));
        array_push($this->steps, new Step("1c"));
        array_push($this->steps, new Step("1d"));
        array_push($this->steps, new Step("1e"));
        $this->lastError = '';
    }
    
    private static function uip() {
        return dirname(__FILE__)."/updateinprogress.txt";
    }
    
    function updateInProgress(){
        return file_get_contents(self::uip());
    }
    
    private function readStepID(){ 
        return file_get_contents(self::uip());
    }
    
    private function writeStepID($stepid){
        $file = @fopen(self::uip(), 'wb');
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
            $this->Error("файл ".self::uip()." не найден.");
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
            $this->Error("файл ".self::uip()." недоступен для записи.");
        return $result;
    }
        
    function Error($msg){
        $this->lastError = $msg;        
    }
    
    function makeError(){
        $error = array();
        $error["success"] = false;
        $error["message"] = $this->lastError;
        return json_encode($error);
    }
    
    public function makeResult($step){
        $result = array();
        $result["success"] = true;
        $result["message"] = $step->message;
        $result["progress"] = "Шаг ".$step->stepid." завершен."; 
        $result["nextstep"] = true; 
        return json_encode($result);
    }
    
    function makeFinished(){
        $result = array();
        $result["success"] = true;
        $result["message"] = "Успех!";
        $result["progress"] = "Конец.";
        $result["nextstep"] = false; 
        return json_encode($result);
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
        if(!($step->makeStep())){
            $this->Error($step->message);
            return $this->makeError();
        }
        $nextstep = $this->getNextStep($step->stepid);
        if($nextstep === false){
            $this->finish();
            return $this->makeFinished();
        }
        if (!$this->setAsCurrentStep($nextstep->stepid))
            return $this->makeError();
        return $this->makeResult($step);
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

    function finish(){
       unlink(self::uip());    
    }
    
    function action(){
            header("Content-type","text/json");
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
}

class Step1 extends Step {
    public function makeStep() {
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
<script type="text/javascript" src="jquery.js"></script>
</head>
<script type="text/javascript">

function NextStep(aStep){
	$.post("index.php",{action: "nextstep", step: aStep},
		function(data) {
			if (data.success){
				$('#message').html(data.message);
				$('#progress').html(data.progress);
				if(data.nextstep)
				    setTimeout('NextStep('+data.nextstep+')', 100);
            } 
			else {
				$('#error').html(data.message);
                if (confirm("Что-то не получилось. Попробуем еще раз?"))
                    setTimeout('NextStep('+aStep+')', 100);
                else 
				$('#message').html("Автоматическое обновление прервано. Попробуйте перегрузить страницу. если не получится - придется обновляться вручную.");
                    
            }
		}, "json"
	);
}
</script>
<body>
<div id="message"></div>
<div id="progress"></div>
<div id="error"></div>
</body>
<button onclick="NextStep('');">Начать!</button>
</html>
<?php
} 
?>