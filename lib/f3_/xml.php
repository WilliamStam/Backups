<?php

class XML extends Registry {

	private static
		//! XML translation table
		$TABLE=array();

	/**
		Return XML translation table
			@return array
			@param $latin boolean
	**/
	private function table($latin=FALSE) {
		if (!isset(self::$TABLE[(int)$latin])) {
			$xl8=get_html_translation_table(HTML_ENTITIES,ENT_COMPAT);
			foreach ($xl8 as $key=>$val)
				$tab[$latin?$val:$key]='&#'.ord($key).';';
			self::$TABLE[(int)$latin]=$tab;
		}
		return self::$TABLE[(int)$latin];
	}

	/**
		Convert plain text to XML entities
			@return string
			@param $str string
			@param $latin boolean
	**/
	function encode($str,$latin=FALSE) {
		return strtr($str,self::table($latin));
	}

	/**
		Convert XML entities to plain text
			@return string
			@param $str string
			@param $latin boolean
	**/
	function decode($str,$latin=FALSE) {
		return strtr($str,array_flip(self::table($latin)));
	}

}
