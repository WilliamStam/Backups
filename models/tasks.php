<?php

namespace models;
use \F3 as F3;
use \timer as timer;

class tasks {
	function __construct() {
		$classname = get_class($this);
		$this->dbStructure = $classname::dbStructure();

	}

	public static function getAll() {
		$timer = new timer();

		$records = F3::get("DB")->exec("
			SELECT * FROM backups_tasks WHERE 1
		"
		);
		$a = array();
		foreach ($records as $record){
			$a[] = $record;
		}


		$return = $a;
		$timer->stop("Models - tasks - getAll", func_get_args());
		return $return;
	}



	public function get($ID){
		$timer = new timer();

		$result = F3::get("DB")->exec("


			SELECT *
			FROM backups_tasks
			WHERE ID = '$ID'
		");

		//F3::get("DB")->exec("UPDATE quizz_questions SET answered = '1' WHERE ID = '$ID'");


		if (count($result)) {
			$return = $result[0];
		} else {
			$return = $this->dbStructure;
		}




		$timer->stop("Models - tasks - get", func_get_args());
		return $return;
	}



	private static function dbStructure() {
		$table = F3::get("DB")->exec("EXPLAIN backups_tasks;");
		$result = array();
		foreach ($table as $key => $value) {
			$result[$value["Field"]] = "";
		}
		return $result;
	}


}