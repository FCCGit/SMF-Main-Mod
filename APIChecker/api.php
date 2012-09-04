<style type="text/css">
#window_div{
	height:1%; 
	width:750px;
	background-color:#eeeeee;
	overflow:hidden;
}
#table_div{
	float:left;
	padding-left:20px;
	text-align:center;
	height: 400px;
	width: 700px;
	overflow: auto;
}
#apiTable td {
	border-width: 1px;
	padding: 5px;
	border-style: solid;
	border-color: #a9c6c9;
}
#apiTable {
	font-size:13px;
	color:#333333;
	border-width: 1px;
	border-color: #999999;
	border-collapse: collapse;
	width:680px;
}
th {
	background-color:#99AEB0;
	border-width: 1px;
	padding: 5px;
	border-style: solid;
	border-color: #a9c6c9;
}
tr {
	background-color:#d4e3e5;
}

#apiHeader {
         background-color:#949FA0;
         font-weight: bold;

}
.apiwin{
	background-color:#66C266;
}
.apifail{
	background-color:#FF4D4D;
}
.apimissing {
        background-color:#FFFF99;
}
</style>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
<script src="js/sorttable.js"></script>
<script type="text/javascript">

$(document).ready(function(){
	colorRows();
});    

function colorRows(){
	var table = document.getElementById("apiTable");
	for (var i = 1, row; row = table.rows[i]; i++) {
		var status = row.cells[1].innerHTML;
		if(status == 'API Error'){
			row.className = "apifail";
                } else if (status == ''){
                        row.className = "apimissing";
		} else {
			row.className = "apiwin";
		}
	}	
}
</script>

<div id="window_div" class="windowbg">
    <h3 class="catbg"> API Tracker</h3>
    <div id="table_div">
<?php
	//include SMF settings for db info
	global $boarddir;
	require_once($boarddir."/Settings.php");
	global $db_server,$db_user,$db_passwd,$db_name;
      
    //connect
    $connection = mysql_connect($db_server,$db_user,$db_passwd);
    if (!$connection){
        die("Database Connection Face Rape Alert");
    }
    if (!mysql_select_db($db_name,$connection)){
        die("Database Connection Face Rape Alert");
    }
         

    $query = "SELECT
            member_name,
            status,
            errorid,
            error
            FROM smf_members
            left join smf_tea_api ON smf_members.id_member = smf_tea_api.id_member";
        
    $result = mysql_query($query);
        
    if (!$result){
        die("Couldn't execute query");
    }
        
    echo "<table id='apiTable' class='sortable'>";
    echo "<tr id='apiHeader'><td>Member Name</td><td>API Status</td><td>Error Code</td><td>Error Message</td></tr>";
    while($rows = mysql_fetch_row($result)){
        echo "<tr>";
        foreach($rows as $key=>$value){
            echo '<td>',$value,'</td>';
        }
        echo "</tr>";
    }
        
    echo "</table>";
    mysql_free_result($result);
	mysql_close();
?>
    </div>
</div>