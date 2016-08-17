<?php
require_once('../connect.php');

$res = sendQuery('SELECT locdivID,width,height,border FROM locdivs WHERE locdivID>1');
$locdivs = array();
while ($locdiv=$res->fetch_row()) {
    $locdivs[$locdiv[0]] = array_slice($locdiv,1);
    $locdivs[$locdiv[0]][] = array();
}
//var_dump($locdivs);

$res = sendQuery('SELECT * FROM locs WHERE locID>1');
while ($loc=$res->fetch_row()) {
    $locdivs[$loc[1]][3][] = array($loc[0],array_slice($loc,2));
}
echo json_encode($locdivs);
//var_dump($locdivs[2][3][0]);