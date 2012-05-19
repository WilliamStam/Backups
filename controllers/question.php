<?php
/*
 * Date: 2011/10/31
 * Time: 5:06 PM
 */
namespace controllers;
use \F3 as F3;
use \timer as timer;
use \models\questions as questions;
use \models\answers as answers;
class question {
	function __construct() {


	}
	function page() {

		if (count($_POST)){
			$ID = (isset($_GET['q']))?$_GET['q']:"";
			$answered = (isset($_POST['answers']))? $_POST['answers']:"";



			$q = new questions();
			$q->answer($ID,$answered);
			$q = $q->get($ID);




			$a = answers::getAll($q['ID']);

		} else {
			$ID = (isset($_GET['q'])) ? $_GET['q'] : "";
			if ($ID){
				$q = new questions();
				$q = $q->get($ID);
			} else {
				$q = questions::getRandom();
			}

			$a = answers::getAll($q['ID']);
		}

		$result = questions::getResult();

		//test_array($result);

		//test_array($q);

		$tmpl = new \template("template.tmpl","ui/front/");
		$tmpl->page = array(
			"template"=> "page_question",
			"meta"    => array(
				"title"=> "Quizz",
			)
		);

		$tmpl->question = $q;
		$tmpl->answers = $a;
		$tmpl->result = $result;

		$tmpl->output();

	}


}
