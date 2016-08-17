<?php
function sendQuery($query) {
	$q_result = mysqli_query($GLOBALS['con'],$query);
	if(!$q_result){echo 'Internal server error'; var_dump($query); exit();}
	else {
		if ($q_result!==true and $q_result->num_rows == 0) {return false;}
		else {return $q_result;}
	}
}

$user = 'root';
$pass = 'Kosio1234';
$db = 'libCat';
$host = 'localhost';

$GLOBALS['con'] = mysqli_connect($host,$user,$pass,$db);

if (mysqli_connect_errno()) {
	echo "<h1>Connection to the database failed (for some inexplicable reason)</h1><p>Please <u style='cursor:hand' onclick='window.location.reload()'>try again.</u> or contact the network administrator.</p>";
	exit();
}

sendQuery('SET NAMES utf8');
?>