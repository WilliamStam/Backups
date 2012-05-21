<?php
//ob_start("ob_gzhandler");


date_default_timezone_set('Africa/Johannesburg');
setlocale(LC_MONETARY, 'en_ZA');

$GLOBALS["output"] = array();
$GLOBALS["render"] = "";
if (session_id() == "") {
	$SID = @session_start();
} else $SID = session_id();
if (!$SID) {
	session_start();
	$SID = session_id();
}

require_once('inc/class.timer.php');
$pageExecute = new timer(true);


require_once('inc/functions.php');
require_once('inc/class.timer.php');



$app = require('lib/f3/base.php');
//require_once('lib/Haanga.php');
require_once('lib/Twig/Autoloader.php');
Twig_Autoloader::register();
require_once('inc/class.msg.php');
require_once('inc/class.template.php');
require_once('inc/class.email.php');
require_once('inc/class.store.php');

$version = '0.1';

$app->set('version', $version);
$app->set('v', preg_replace("/[^0-9]/","",$version));


$app->set('AUTOLOAD', './|lib/|controllers/');
$app->set('PLUGINS', 'lib/f3/|lib/suga/');
$app->set('CACHE', TRUE);
$app->set('DEBUG', 2);

$app->set('EXTEND', TRUE);
$app->set('UI', 'ui/');
$app->set('TEMP', 'tmp/');
$cfg = array(
	"db"=>array(
		"host"=>"localhost",
		"username"=>"",
		"password"=>"",
		"database"=>""
	)
);
require_once('config.inc.php');


$app->set('DB', new DB('mysql:host=' . $cfg['db']['database'] . ';dbname=' . $cfg['db']['database'], $cfg['db']['username'], $cfg['db']['password']));

ob_start();

$ttl = 0;
$app->route('GET /min/css/@filename', 'general->css_min', 0);
$app->route('GET /min/css*', 'general->css_min', 0);
$app->route('GET /min/js/@filename', 'general->js_min', 0);
$app->route('GET /min/js*', 'general->js_min', 0);


$app->route('GET|POST /', 'controllers\tasks->page');






$app->run();
$GLOBALS["render"] = ob_get_contents();
ob_end_clean();
$totaltime = $pageExecute->stop("Page Execute");
$GLOBALS["output"]['timer'] = $GLOBALS['timer'];
$GLOBALS["output"]['page'] = array("page" => $_SERVER['REQUEST_URI'], "time" => $totaltime);

//ob_start("ob_gzhandler");
if ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || F3::get("showjson")) {
	header("Content-Type: application/json");
	exit(json_encode($GLOBALS["output"]));
} else {
	if (F3::get("__runTemplate")) {
		;
		$timersbottom = '
					<script type="text/javascript">
				       updatetimerlist(' . json_encode($GLOBALS["output"]) . ');
					</script>
				';
		echo str_replace("</body>", $timersbottom . '</body>', $GLOBALS["render"]);
	} else {
		header("Content-Type: application/json");
		echo json_encode($GLOBALS["output"]);
	}
}

?>