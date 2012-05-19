<?php
/*
 * Date: 2011/10/31
 * Time: 5:06 PM
 */
namespace controllers;
use \F3 as F3;
use \Axon as axon;
use \timer as timer;
use \models\questions as questions;
use \models\answers as answers;
class admin {
	function __construct() {


	}
	function page() {
		$ID = (isset($_GET['ID'])) ? $_GET['ID'] : "";
		$aID = (isset($_GET['aID'])) ? $_GET['aID'] : "";
		$do = (isset($_GET['do'])) ? $_GET['do'] : "";

		if ($do=="delete_answer"){
			F3::get("DB")->exec("DELETE FROM quizz_answers WHERE ID = '$aID'");
			F3::reroute("?ID=$ID");
		} elseif ($do == "delete_question"){
			F3::get("DB")->exec("DELETE FROM quizz_questions WHERE ID = '$ID'");
			F3::get("DB")->exec("DELETE FROM quizz_answers WHERE qID = '$ID'");
			F3::reroute("?ID=");
		} elseif ($do == "reset"){
			F3::get("DB")->exec("UPDATE quizz_questions SET answered = NULL");
		}

		if (count($_POST)){
			if (isset($_POST['question'])){
				$Q = new Axon("quizz_questions");
				$Q->load("ID='$ID'");

				$Q->question = $_POST['question'];
				$Q->save();


				if (!$ID){
					$ID = $Q->_id;

				}
			}
			if (isset($_POST['answer'])){
				$A = new Axon("quizz_answers");
				$A->load("ID='$aID'");

				$A->qID = $ID;
				$A->answer = $_POST['answer'];
				if(isset($_POST['correct'])&& $_POST['correct']=='1') {
					F3::get("DB")->exec("UPDATE quizz_answers SET correct = NULL WHERE qID = '$ID'");
					$A->correct = '1';
				}
				$A->save();


				if (!$ID){
					$aID = $A->_id;

				}
			}

			F3::reroute("?ID=$ID");


		} else {


		}
		$details = new questions();
		$details = $details->get($ID);
		$details['answers'] = answers::getAll($ID);
		//test_array($details);

		$list = questions::getAll();
		//test_array($list);


		$tmpl = new \template("template.tmpl","ui/admin/");
		$tmpl->page = array(
			"template"=> "page_admin",
			"meta"    => array(
				"title"=> "Admin",
			)
		);

		$tmpl->list = $list;
		$tmpl->details = $details;

		$tmpl->output();

	}


}
