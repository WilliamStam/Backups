<?php
/*
 * Date: 2011/10/31
 * Time: 5:06 PM
 */
namespace controllers;
use \F3 as F3;
use \timer as timer;
class tasks {
	function __construct() {


	}
	function page() {


		$tmpl = new \template("template.tmpl","ui/front/");
		$tmpl->page = array(
			"section"=>"jobs",
			"template"=> "page_tasks",
			"meta"    => array(
				"title"=> "Backup Tasks",
			)
		);



		$tmpl->output();

	}


}
