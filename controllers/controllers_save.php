<?php
/*
 * Date: 2011/11/16
 * Time: 11:16 AM
 */

class controllers_save {
	function __construct(){
		if (!isset($_GET['front'])){
			$user = F3::get("user");
			$userID = $user['ID'];
			if (!$userID) exit(json_encode(array("error"=> "User not logged in")));
		}
		header("Content-Type: application/json");
	}


}
