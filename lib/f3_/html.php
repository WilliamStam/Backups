<?php

//! HTML tools
class HTML extends Registry {

	/**
		Convert special characters to HTML entities using globally-
		defined character set
			@return string
			@param $str string
			@param $all bool
	**/
	function encode($str,$all=FALSE) {
		return call_user_func(
			$all?'htmlentities':'htmlspecialchars',
			$str,ENT_COMPAT,Base::instance()->get('ENCODING'),TRUE);
	}

	/**
		Convert HTML entities back to their equivalent characters
			@return string
			@param $str string
			@param $all bool
	**/
	function decode($str,$all=FALSE) {
		return $all?
			html_entity_decode($str,
				ENT_COMPAT,Base::instance()->get('ENCODING')):
			htmlspecialchars_decode($str,ENT_COMPAT);
	}

}
