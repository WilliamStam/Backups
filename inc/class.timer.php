<?php
/*
 * Date: 2011/08/04
 * Time: 8:26 AM
 */
 
class timer {
	private $startTimer;
	private $endTimer;
	private $totalTime;
	private $force;
	function __construct($force=false){
		$this->force = $force;
		if (!isset($GLOBALS["timer"])){
			$GLOBALS["timer"] = array();
		}
		$this->_start();
	}
	private function _start(){
		$mtime = microtime();
		$mtime = explode(" ", $mtime);
		$this->startTimer = $mtime[1] + $mtime[0];
	}
	function stop($msg="", $arguments = ""){
		$mtime = microtime();
		$mtime = explode(" ", $mtime);
		$mtime = $mtime[1] + $mtime[0];
		$this->endTimer = $mtime;
		$this->totalTime($msg, $arguments);
		if ($this->endTimer && $this->startTimer) {
			return $this->endTimer - $this->startTimer;
		} else return "0";
	}
	private function totalTime($msg="",$arguments=""){
		$msg = ($msg)?$msg.": ":"";
		if (($this->endTimer && $this->startTimer) && (!F3::get("nonotifications") || $this->force ) ){
			array_push($GLOBALS['timer'], array("msg"=>$msg, "arg"=> $arguments ,"tim"=> ($this->endTimer - $this->startTimer)));
		}

	}
}

