<?php

class UTF extends Registry {

	/*
		IMPORTANT: All string arguments of methods in this class must be
		encoded in UTF-8 to function properly
	*/

	/**
		Find position of first occurrence of a string (case-insensitive)
			@return mixed
			@param $stack string
			@param $needle string
			@param $ofs int
	**/
	function stripos($stack,$needle,$ofs=0) {
		return $this->strpos($stack,$needle,$ofs,TRUE);
	}

	/**
		Get string length
			@return int
			@param $str string
	**/
	function strlen($str) {
		preg_match_all('/./us',$str,$parts);
		return count($parts[0]);
	}

	/**
		Find position of first occurrence of a string
			@return mixed
			@param $stack string
			@param $needle string
			@param $ofs int
			@param $case bool
	**/
	function strpos($stack,$needle,$ofs=0,$case=FALSE) {
		preg_match('/^(.*?)'.preg_quote($needle,'/').'/us'.($case?'i':''),
			$this->substr($stack,$ofs),$match);
		return isset($match[1])?$this->strlen($match[1]):FALSE;
	}

	/**
		Finds position of last occurrence of a string (case-insensitive)
			@return mixed
			@param $stack string
			@param $needle string
			@param $ofs int
	**/
	function strripos($stack,$needle,$ofs=0) {
		return $this->strrpos($stack,$needle,$ofs,TRUE);
	}

	/**
		Find position of last occurrence of a string
			@return mixed
			@param $stack string
			@param $needle string
			@param $ofs int
			@param $case bool
	**/
	function strrpos($stack,$needle,$ofs=0,$case=FALSE) {
		if (!$needle)
			return FALSE;
		$len=$this->strlen($stack);
		for ($ptr=$ofs;$ptr<$len;$ptr+=$this->strlen($match[0])) {
			$sub=$this->substr($stack,$ptr);
			if (!$sub || !preg_match('/^(.*?)'.
				preg_quote($needle,'/').'/us'.($case?'i':''),$sub,$match))
				break;
			$ofs=$ptr+$this->strlen($match[1]);
		}
		return $sub?$ofs:FALSE;
	}

	/**
		Returns part of haystack string from the first occurrence of
		needle to the end of haystack (case-insensitive)
			@return mixed
			@param $stack string
			@param $needle string
			@param $before bool
	**/
	function stristr($stack,$needle,$before=FALSE) {
		return strstr($stack,$needle,$before,TRUE);
	}

	/**
		Returns part of haystack string from the first occurrence of
		needle to the end of haystack
			@return mixed
			@param $stack string
			@param $needle string
			@param $before bool
			@param $case bool
	**/
	function strstr($stack,$needle,$before=FALSE,$case=FALSE) {
		if (!$needle)
			return FALSE;
		preg_match('/^(.*?)'.preg_quote($needle,'/').'/us'.($case?'i':''),
			$stack,$match);
		return isset($match[1])?
			($before?
				$match[1]:$this->substr($stack,$this->strlen($match[1]))):
			FALSE;
	}

	/**
		Return part of a string
			@return mixed
			@param $str string
			@param $start int
			@param $len int
	**/
	function substr($str,$start,$len=0) {
		if ($start<0) {
			$len=-$start;
			$start=$this->strlen($str)+$start;
		}
		if (!$len)
			$len=$this->strlen($str)-$start;
		return preg_match('/^.{'.$start.'}(.{0,'.$len.'})/us',$str,$match)?
			$match[1]:FALSE;
	}

	/**
		Count the number of substring occurrences
			@return int
			@param $stack string
			@param $needle string
	**/
	function substr_count($stack,$needle) {
		preg_match_all('/'.preg_quote($needle,'/').'/us',$stack,
			$matches,PREG_SET_ORDER);
		return count($matches);
	}

}
