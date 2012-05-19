<?php

namespace models;
use \F3 as F3;
use \timer as timer;
use models\answers as answers;

class questions {
	function __construct() {
		$classname = get_class($this);
		$this->dbStructure = $classname::dbStructure();

	}
	public static function getResult() {
		$timer = new timer();

		$records = F3::get("DB")->exec("
			SELECT * FROM quizz_questions WHERE 1
		"
		);
		$a = array();
		$stats = array(
			"counter"=>array(
				"answered"=>0,
				"total"=>count($records),
				"wrong"=>0,
				"correct"=>0
			)
		);
		foreach ($records as $record){
			if ($record['answered']){
				$correct = answers::getCorrect($record['ID']);
				if ($record['answered'] == $correct['ID'] ){
					$record['status'] = "Correct";
					$stats['counter']['correct'] = $stats['counter']['correct'] + 1;
				} else {
					$record['status'] = "Wrong";
					$stats['counter']['wrong'] = $stats['counter']['wrong'] + 1;
				}
				$stats['counter']['answered'] = $stats['counter']['answered']+1;
			} else {
				$record['status'] = "un-answered";
			}
			$a[] = $record;
		}
		$stats['records'] = $a;

		$return = $stats;
		$timer->stop("Models - questions - getResult", func_get_args());
		return $return;
	}
	public static function getAll() {
		$timer = new timer();

		$records = F3::get("DB")->exec("
			SELECT * FROM quizz_questions WHERE 1
		"
		);
		$a = array();
		foreach ($records as $record){
			$record['answers'] = answers::getAll($record['ID']);
			$a[] = $record;
		}


		$return = $a;
		$timer->stop("Models - questions - getAll", func_get_args());
		return $return;
	}

	public static function getRandom() {
		$timer = new timer();

		$records = F3::get("DB")->exec("
			SELECT ID FROM quizz_questions WHERE answered is NULL
		");

		$return = $records;

		if (count($return)){
			$q = new questions();
			$return = $q->get($return[array_rand($records, 1)]['ID']);
		} else {
			$return = questions::dbStructure();
		}



		$timer->stop("Models - questions - getRandom", func_get_args());
		return $return;
	}


	public function get($ID){
		$timer = new timer();

		$result = F3::get("DB")->exec("


			SELECT *
			FROM quizz_questions
			WHERE ID = '$ID'
		");

		//F3::get("DB")->exec("UPDATE quizz_questions SET answered = '1' WHERE ID = '$ID'");


		if (count($result)) {
			$return = $result[0];
			$return['correct_answer']=answers::getCorrect($return['ID']);
			if ($return['answered']){
				if ($return['answered'] == $return['correct_answer']['ID']) {
					$return['status'] = "Correct";
				} else {
					$return['status'] = "Wrong";
				}
			} else {
				$return['status'] = "un-answered";
			}
		} else {
			$return = $this->dbStructure;
		}




		$timer->stop("Models - questions - get", func_get_args());
		return $return;
	}
	public function answer($ID,$answer){
		$timer = new timer();

		F3::get("DB")->exec("UPDATE quizz_questions SET answered = '$answer' WHERE ID = '$ID'");


		$return = "";



		$timer->stop("Models - questions - answer", func_get_args());
		return $return;
	}


	private static function dbStructure() {
		$table = F3::get("DB")->exec("EXPLAIN quizz_questions;");
		$result = array();
		foreach ($table as $key => $value) {
			$result[$value["Field"]] = "";
		}
		return $result;
	}


}