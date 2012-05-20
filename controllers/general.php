<?php
/*
 * Date: 2012/02/23
 * Time: 12:55 PM
 */

class general {
	function css_min(){
		$file = (isset($_GET['file'])) ? $_GET['file'] : "";
		header("Content-Type: text/css");
		if ($file) {

			$fileDetails = pathinfo(($file));
			$base = "." . $fileDetails['dirname'] . "/";
			$file = $fileDetails['basename'];

			//echo $base."\n".$file;
			$t = file_get_contents($base . $file);

		} else {

			$files = array(
				"/ui/_css/bootstrap.css"
			);


			$t = "";
			$base = array();
			foreach ($files as $file) {
				$fileDetails = pathinfo(($file));
				$base = "." . $fileDetails['dirname'] . "/";
				$file = $fileDetails['basename'];

				$t .= Web::minify($base, array($file), false);

			}
		}

		exit($t);



	}

	function js_min() {
		ob_start("ob_gzhandler");
		$file = (isset($_GET['file'])) ? $_GET['file'] : "";
		//$file = F3::get('PARAMS.filename');
		header("Content-Type: application/x-javascript");
		$t = "";
		if ($file) {
			$fileDetails = pathinfo(($file));
			$base = "." . $fileDetails['dirname'] . "/";
			$file = $fileDetails['basename'];
			$t = file_get_contents($base . $file);
		} else {
			$files = array(
				"_js/libs/jquery-ui.js",
				"_js/libs/bootstrap.min.js",
				"_js/plugins/bootstrap-datepicker.js",
				"_js/plugins/date.js",
				"_js/plugins/jquery.daterangepicker.js",
				"_js/plugins/jquery.mousewheel.js",
				"_js/plugins/mwheelIntent.js",
				"_js/plugins/jquery.jscrollpane.js",
				"_js/plugins/jquery.jqote2.js",
				"_js/plugins/jquery.ba-bbq.js",
				"_js/plugins/jquery.cookie.js",
				"_js/plugins/jquery.scrollto.min.js", // ------ //




			);



			$t = "";
			foreach ($files as $file){
				$base  = F3::get("UI");
					$t.= file_get_contents($base . $file);

			}





		}
		exit($t);
	}


}
