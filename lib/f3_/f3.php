<?php

//! Support for legacy apps
class F3 extends Agent {

	/**
		Forward static calls
			@return mixed
			@param $func string
			@param $args array
	**/
	static function __callstatic($func,$args) {
		if (method_exists($fw=Base::instance(),$func))
			return call_user_func_array(array($fw,$func),$args);
		trigger_error(sprintf(Base::ERROR_Method,__CLASS__.'::'.$func.'()'));
		return FALSE;
	}

}
