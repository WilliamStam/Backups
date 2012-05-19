<?php

namespace models;
use \F3 as F3;
use \timer as timer;

class answers {
	function __construct() {
		$classname = get_class($this);
		$this->dbStructure = $classname::dbStructure();

	}

	public static function getAll($ID) {
		$timer = new timer();

		$records = F3::get("DB")->exec("
			SELECT * FROM quizz_answers WHERE qID = '$ID'
		");


		$return = $records;


		$timer->stop("Models - answers - getAll", func_get_args());
		return $return;
	}
	public static function getCorrect($ID) {
		$timer = new timer();

		$records = F3::get("DB")->exec("
			SELECT * FROM quizz_answers WHERE qID = '$ID' AND correct ='1'
		");


		if (count($records)){
			$return = $records[0];
		} else {
			$return = answers::dbStructure();
		}


		$timer->stop("Models - answers - getAll", func_get_args());
		return $return;
	}




	private static function dbStructure() {
		$table = F3::get("DB")->exec("EXPLAIN quizz_answers;");
		$result = array();
		foreach ($table as $key => $value) {
			$result[$value["Field"]] = "";
		}
		return $result;
	}


}