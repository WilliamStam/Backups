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
require_once('lib/Haanga.php');
require_once('inc/class.msg.php');
require_once('inc/class.template.php');
require_once('inc/class.email.php');
require_once('inc/class.store.php');

$version = '0.1.20';

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
		"username"=>"",
		"password"=>"",
		"database"=>""
	)
);
require_once('config.inc.php');


$app->set('DB', new DB('mysql:host=localhost;dbname=' . $cfg['db']['database'], $cfg['db']['username'], $cfg['db']['password']));
$app->set("GOOGLE_ANALYTICS", $cfg['GOOGLE_ANALYTICS']);


$version = '0.0.1';
$version = date("YmdH");
$minVersion = preg_replace("/[^0-9]/", "", $version);

$app->set('version', $version);
$app->set('v', $minVersion);

ob_start();

$ttl = 0;
$app->route('GET /min/css/@filename', 'general->css_min', 0);
$app->route('GET /min/css*', 'general->css_min', 0);
$app->route('GET /min/js/@filename', 'general->js_min', 0);
$app->route('GET /min/js*', 'general->js_min', 0);


$app->route('GET|POST /', 'controllers\question->page');
$app->route('GET|POST /admin', 'controllers\admin->page');

$app->run();

$GLOBALS["render"] = ob_get_contents();
ob_end_clean();


$totaltime = $pageExecute->stop("Page Execute");
$GLOBALS["output"]['timer'] = $GLOBALS['timer'];
$GLOBALS["output"]['page'] = array(
	"page"=> $_SERVER['REQUEST_URI'],
	"time"=> $totaltime
);





$app->run();


?>