<?php
/*
 * Date: 2011/08/18
 * Time: 4:45 PM
 */
 
class s_geo {
	public static function location($ip=""){
		$ip = ($ip)?$ip: $_SERVER['REMOTE_ADDR'];
		if ($ip=="127.0.0.1"){
			$ch = "";
		} else {
			$ch = file_get_contents("http://www.geoplugin.net/php.gp?ip=$ip");
		}

		if ($ch){
			$ch = unserialize($ch);
		}
		return $ch;
	}

}
