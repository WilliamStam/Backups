<?php

//! Tools for unit testing
class Test extends Agent {

	private
		$PROPS;

	/**
		Evaluate condition and save test result
			@return void
			@param $cond bool
			@param $text string
	**/
	function expect($cond,$text='') {
		$fw=Base::instance();
		$out=FALSE;
		try { $out=(bool)$cond; }
		catch (Exception $obj) {}
		$trace=debug_backtrace();
		$from=$trace[0];
		$this->PROPS['RESULTS'][]=array(
			'status'=>$out,
			'text'=>$text,
			'source'=>isset($from['file'])?
				($fw->fixslashes($from['file']).':'.$from['line']):NULL
		);
	}

	/**
		Return property value
			@return scalar|array
			@param $key string
	**/
	function get($key) {
		return $this->PROPS[$key];
	}

	/**
		Instantiate class
			@return void
			@param $title string
	**/
	function __construct($title=NULL) {
		$this->PROPS['TITLE']=$title;
	}

}
