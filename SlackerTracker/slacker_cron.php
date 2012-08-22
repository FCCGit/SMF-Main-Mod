<?php
//include SMF settings for db info
require_once("./Settings.php");

//number of previous months to get data for
$PREV_MONTHS = 6;

//hardcoding corp API for now
$corpApiId = "1235134";
$corpApiKey = "h6KM50FEl44WkmhYM8RWctc3u0V8RPbvpClIOONwoVu6hQZuh31pbpp9zBpMuCSc";

//get kills from the first of the month (at midnight GMT)
function getKills($charid, $startdate){
	$kills = 0;
	$losses = 0;
	$lastID = "";
	while(!is_null($lastID)){
		$url = "http://eve-kill.net/?a=idfeed&pilot=".$charid."&allkills=1&startdate=".$startdate;
		if($lastID != ""){
			$url .= "&lastintID=".$lastID;
		}

		$eveapi = NULL;
		$attempts = 0;
		while(is_null($eveapi) && ++$attempts < 3){
			try {
				$value = file_get_contents($url);
				if(!is_null($value) && $value != ""){
					$eveapi = new SimpleXMLElement($value);
				} else {
					echo "\tBlank response for ".$charid." on attempt ".$attempts;
				}
			}catch(Exception $e) {
				echo "\tCaught exception ".$charid." on attempt ".$attempts;
			}
		}

		$kills += count($eveapi->xpath("/eveapi/result/rowset/row/victim[@characterID!='".$charid."']"));
		$losses += count($eveapi->xpath("/eveapi/result/rowset/row/victim[@characterID='".$charid."']"));
		$lastRow = $eveapi->xpath("/eveapi/result/rowset/row[last()]");

		//if we're on the next page, the lastrow is included again, so we have remove it
		if(count($lastRow) == 0){
			$lastID = NULL;
		} else {
			$attr = $lastRow[0]->victim->attributes();
			if($lastID != "" && $attr['characterID'] == $charid){
				$losses--;
			} else if($lastID != "") {
				$kills--;
			}
	
			//check to see if we need to get another page
			if(count($lastRow) == 0){
				$lastID = NULL;
			} else if(count($lastRow) == 1 && strval($lastID) == strval($lastRow[0]['killInternalID'])){
				$lastID = NULL;
			} else {
				$lastID = $lastRow[0]['killInternalID'];
			}
		}
	}

	return array("kills" => $kills, "losses" => $losses);
}

function getCorpData(){
	global $corpApiId; global $corpApiKey; 
	$corpData = array();
	$value = file_get_contents("https://api.eveonline.com/corp/MemberTracking.xml.aspx?keyID=".$corpApiId."&vCode=".$corpApiKey."&extended=1");
	$eveapi = new SimpleXMLElement($value);
	$rows = $eveapi->xpath("/eveapi/result/rowset/row");
	foreach($rows as $row) {
		$corpData[strval($row->attributes()->characterID)] = array(
			"name" => strval($row->attributes()->name),
			"title" => strval($row->attributes()->title)
		);
	}
	
	return $corpData;
}

//connect to db
$connection = mysql_connect($db_server,$db_user,$db_passwd);
if (!$connection){
	die("Database connection is all sorts of fucked up, fix that shit.");
}
mysql_select_db($db_name, $connection);
//mysql_query("drop table smf_slacker_tracker");

//create the table to store kill info if it doesnt exist already
$tbl = <<<STR
CREATE TABLE IF NOT EXISTS `smf_slacker_tracker` (
  `charid` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `title` varchar(32) NOT NULL,
  `kills` int(5) NOT NULL,
  `losses` int(5) NOT NULL,
  `month` date NOT NULL,
  `updated_date` datetime NOT NULL,
  UNIQUE KEY `smf_slacker_tracker_pk` (`charid`, `month`)
) 
STR;
mysql_query($tbl);

//clear table
mysql_query("TRUNCATE TABLE smf_slacker_tracker");

//repopulate table
$corpData = getCorpData();
$counter = 0;

echo "<pre>\n";
foreach($corpData as $key => $row){
	$startdateGMT = strtotime(date("Y")."-".date("m")."-01 00:00:00-0000");
	$startdate = strtotime(date("Y")."-".date("m")."-01");
	$startdateGMT = strtotime("+1 month", $startdateGMT);
	$startdate = strtotime("+1 month", $startdate);

	for($i=0; $i<$PREV_MONTHS; $i++){
		$startdateGMT = strtotime("-1 month", $startdateGMT);
		$startdate = strtotime("-1 month", $startdate);

		try{
			$killInfo = getKills($key, $startdateGMT);
			mysql_query("INSERT INTO smf_slacker_tracker (charid,name,title,kills,losses,month,updated_date) VALUES(".$key.",'".$row['name']."','".$row['title']."',".$killInfo['kills'].",".$killInfo['losses'].",'".date("Y-m-d", $startdate)."',SYSDATE())");
		}catch(Exception $e) {
			echo "\tDatabase exception ".$row['name']." on attempt ".$attempts;
		}
		echo $row['name']."(".$row['title'].") has ".$killInfo['kills']." kills and ".$killInfo['losses']." losses for ".date("Y-m-d", $startdate)."\n";	
	}
	$counter++;
}
echo "Processed ".$counter." pilots";
echo "</pre>";
mysql_close($connection);
?>
