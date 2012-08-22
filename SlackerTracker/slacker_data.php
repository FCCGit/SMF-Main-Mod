<?php
//include SMF settings for db info
require_once("./Settings.php");

//connect
$connection = mysql_connect($db_server,$db_user,$db_passwd);
if (!$connection){
	die("Database connection is all sorts of fucked up, fix that shit.");
}
mysql_select_db($db_name, $connection);

//JOIN smf_members memb ON upper(trim(memb.member_name)) = upper(trim(track.name))
$query = "SELECT DISTINCT track.charid, track.name, track.title, track.kills, track.losses, track.month FROM smf_slacker_tracker track ";
$clauses = array();
if(!is_null($_GET['charid'])){
	array_push($clauses, "track.charid = ".$_GET['charid']);
}
if(!is_null($_GET['month'])) {
	array_push($clauses, "month = '".$_GET['month']."'");
}

if(count($clauses) > 0){
	$query .= " WHERE ";
}

$clause = "";
foreach ($clauses as $c){
	$clause .= " AND ".$c;
}
$clause = substr($clause, 5);
$query .= $clause." ORDER BY track.name, track.month ASC";

$result = mysql_query($query);
$response = '';
while($row = mysql_fetch_array($result)){
	$response .= ',{"charid":"'.$row['charid'].'","name":"'.$row['name'].'","title":"'.$row['title'].'","kills":"'.$row['kills'].'","losses":"'.$row['losses'].'","month":"'.$row['month'].'"}';
}
mysql_close();
$response = '['.substr($response, 1).']';

echo $response;
?>
