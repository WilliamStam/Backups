<?php
/*
 * Date: 2011/04/17
 * Time: 3:17 PM
 */

class DBlayer extends Base {
	private $db;
	public static function DBlayer($key=""){
		echo("aaaaaaaaaaaaa");
	}
	static function sql($cmd, $args = NULL, $options = array(), $db = NULL) {

		exit("wtf");
		if (!$db){
			if (is_object($args)){
				$db = $args;
			} else if($options) {
				$db = $options;
			}
		}

		if (!$db) {
			if (!isset(self::$vars['DB'])){
				self::$db = self::$vars['DB'];
			}
		} else {
			self::$db = $db;
		}
		self::$db->sql($cmd);
	}
	
}
