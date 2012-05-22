<?php
/*
 * Date: 2012/05/22
 * Time: 7:27 PM
 */

$source = '"D:\\New folder\\folder 1"';
$destination = '"D:\\New folder\\folder 2"';

// Robocopybackup.exe $f_source $f_destination /E /ZB /PURGE /NP /R:10 /log:$dirname\LogFiles\$logfilename

delTree($destination);

$cmd = "robocopy $source $destination /E /ZB /PURGE";
$cmd = "robocopy $source $destination /E /PURGE /FP /NP ";
echo $cmd;
echo "<hr>";
psexec($cmd);

//echo code($output);
echo "<hr>";


//$str = str_replace("\n","<br>",$str);
//echo $str;

function psexec($cmd){
	flush();
	system($cmd, $output);

	//echo print_r($output);
	if(count($output) > 0){
		for ($r = 0; $r < count($output); $r++) {
			echo $output [$r] . '<br> ';
			//return $str;
		}
	}
//	return false;
}


function delTree($dir) {
	$files = glob($dir . '*', GLOB_MARK);
	foreach ($files as $file) {
		if (substr($file, -1) == '/') delTree($file); else
			unlink($file);
	}
//	rmdir($dir);
}
function code($str){
	switch ($str) {
		case 0:
			$retValue = "NO CHANGE";
			break;
		case 1:
			$retValue = "COPY";
			break;
		case 2:
			$retValue = "EXTRA";
			break;
		case 3:
			$retValue = "EXTRA COPY";
			break;
		case 4:
			$retValue = "MISMATCH";
			break;
		case 5:
			$retValue = "MISMATCH COPY";
			break;
		case 6:
			$retValue = "MISMATCH EXTRA";
			break;
		case 7:
			$retValue = "MISMATCH EXTRA COPY";
			break;
		case 8:
			$retValue = "FAIL";
			break;
		case 9:
			$retValue = "FAIL COPY";
			break;
		case 10:
			$retValue = "FAIL EXTRA";
			break;
		case 11:
			$retValue = "FAIL EXTRA COPY";
			break;
		case 12:
			$retValue = "FAIL MISMATCH";
			break;
		case 13:
			$retValue = "FAIL MISMATCH COPY";
			break;
		case 14:
			$retValue = "FAIL MISMATCH EXTRA";
			break;
		case 15:
			$retValue = "FAIL MISMATCH EXTRA COPY";
			break;
		case 16:
			$retValue = "FATAL ERROR";
			break;
		default:
			$retValue = "UNKNOWN";
			break;
	}
	return $retValue;
}

?>
