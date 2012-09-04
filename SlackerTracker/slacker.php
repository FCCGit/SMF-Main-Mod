<link rel="stylesheet" type="text/css" href="js/jqPlot/jquery.jqplot.css" />
<style type="text/css">
#window_div{
	height:1%; 
	width:750px;
	background-color:#eeeeee;
	overflow:hidden;
}
#info_div{ 
	float:left; 
	padding-right:20px;
	padding-left:10px;
}
#table_div{
	float:left;
	padding-left:20px;
	text-align:center;
	width:375px;
}
#data_div{
	width:375px;
	height: 300px;
	overflow:auto;
}
#killGraph_div{
	float:left;
	width:100%;
	height:250px;
	padding:7px 7px 15px 7px;
}
#killHeaders{
	width:350px;
}
#killTable {
	font-size:13px;
	color:#333333;
	border-width: 1px;
	border-color: #999999;
	border-collapse: collapse;
	width:350px;
}
#tblDate{
	font-weight:bold;
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
.overacheiver{
	background-color:#65b059;
}
.slacker{
	background-color:#bf4030;
}
.almostSlacker{
	background-color:#bf8830;
}
.notSlacker{
	background-color:#d4e3e5;
}
.selected{
	border-width: 2px;
	border-color: #1AEB71;
	border-style: solid;
}
#killTable td {
	border-width: 1px;
	padding: 5px;
	border-style: solid;
	border-color: #a9c6c9;
}
</style>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jqPlot/jquery.jqplot.js"></script>
<script type="text/javascript" src="js/jqPlot/plugins/jqplot.dateAxisRenderer.min.js"></script>
<script type="text/javascript" src="js/jqPlot/plugins/jqplot.canvasTextRenderer.min.js"></script>
<script type="text/javascript" src="js/jqPlot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
<script type="text/javascript" src="js/jqPlot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
<script type="text/javascript" src="js/jqPlot/plugins/jqplot.barRenderer.min.js"></script>
<script type="text/javascript" src="js/jqPlot/plugins/jqplot.highlighter.js"></script>

<script src="js/sorttable.js"></script>
<script src="js/date.js"></script>
<script type="text/javascript">
<?php 
echo "var date = Date.parse('".date("Y").",".date("m").",01"."');";
?>

$(document).ready(function(){
	fillTable();
	generatePlots('-1');
});

function colorRows(){
	var table = document.getElementById("killTable");
	var thresh = parseInt(document.getElementById("threshold").value);
	for (var i = 1, row; row = table.rows[i]; i++) {
		var kills = parseInt(row.cells[2].innerHTML);
		if(kills < thresh){
			row.className = "slacker";
		} else if(kills >= thresh && kills < parseInt(thresh)+5) {
			row.className = "almostSlacker";
		} else if(kills > thresh+20){
			row.className = "overacheiver";
		} else {
			row.className = "notSlacker";
		}
	}	
}
function doFilter(){
	var table = document.getElementById("killTable");
	var all = document.getElementById("filter_all");
	var pOnly = document.getElementById("filter_pOnly");
	var noM = document.getElementById("filter_noM");

	for (var i = 1, row; row = table.rows[i]; i++) {
		var title = row.cells[1].innerHTML;
		if(all.checked){
			row.style.display = '';
		}else if(pOnly.checked){
			if(title == 'P')
				row.style.display = '';
			else
				row.style.display = 'none';
		} else if(noM.checked){
			if(title != 'M')
				row.style.display = '';
			else
				row.style.display = 'none';	
		}
	}
}

function fillTable(){
	$.ajax({
		url: 'slacker_data.php?month='+date.toString('yyyy-MM-dd'),
		dataType:'json',
		error: function(jqXHR, textStatus, errorThrown){
			alert(errorThrown);
		},
		success: function(data){
			$('#tblDate').html(date.toString('MMMM, yyyy'));
			$('#killTable > tbody').children().remove();
			$(data).each(function(index, obj){
				$('#killTable').children('tbody').append('<tr charid="'+obj.charid+'" onclick="generatePlots(\''+obj.charid+'\')"><td>'+obj.name+'</td><td>'+obj.title+'</td><td>'+obj.kills+'</td><td>'+obj.losses+'</td></tr>');
			});
			if($(data).size() == 0){
				$('#killTable').children('tbody').append('<tr><td>No data available for '+date.toString('MMMM, yyyy')+'</td></tr>');
			} else {
				colorRows();
			}
		}
	});
}

function next(){
	date = date.add(1).months();
	fillTable();
}

function prev(){
	date = date.add(-1).months();
	fillTable();
}

var charKills;
function generatePlots(id){
	$('tr[charid!="'+id+'"]').children('td').css('border','');
	$('tr[charid="'+id+'"]').children('td').css('border','3px solid #1AEB71');

	$.ajax({
		url: 'slacker_data.php?charid='+id,
		dataType:'json',
		error: function(jqXHR, textStatus, errorThrown){
			alert(errorThrown);
		},
		success: function(data){
			$('#killGraph_div').children().remove();
			$('#lossGraph_div').children().remove();
			var kills = [];
			var losses = [];

			var name;
			var maxKills = 0; 
			var maxLosses = 0;
			$(data).each(function(index, obj){
				var dt = Date.parse(obj.month);
				kills.push([dt.toString('MMMM, yyyy'),parseInt(obj.kills)]);
				losses.push([dt.toString('MMMM, yyyy'),parseInt(obj.losses)]);
				if(parseInt(obj.kills) > maxKills){
					maxKills = parseInt(obj.kills);
				}
				if(parseInt(obj.losses) > maxLosses){
					maxLosses = parseInt(obj.losses);
				}
				name = obj.name;
			});
			
			var ticks = '25';
			if(maxLosses < 50 && maxKills < 50){
				ticks = '10';
			}

			charKills = $.jqplot ('killGraph_div', [kills,losses], {
				title: name,
				seriesDefault: {
					pointLabels: { show:false }, lineWidth:2
				},
				series:[ 
					{ showMarker:false,label:'Kills' },
					{ showMarker:false,label:'Losses' }
				],
				legend:{ show:true,xoffset:32 },
				axesDefaults: {
					tickRenderer:$.jqplot.CanvasAxisTickRenderer,
					tickOptions: { fontSize: '8pt' }
				},
				axes: {
					xaxis: { renderer: $.jqplot.CategoryAxisRenderer },
					yaxis: { tickInterval:ticks,tickOptions:{ formatString:'%d' },min:0 }
				},
				highlighter: { tooltipAxes:'y',yvalues:1,formatString:'%d' }
			});
		}
	});
}
</script>

<div id="window_div" class="windowbg">
<h3 class="catbg">Track those slackers!</h3>

<div id="info_div">
<?php
//include SMF settings for db info
global $boarddir;
require_once($boarddir."/Settings.php");
global $db_server,$db_user,$db_passwd;

//connect
$connection = mysql_connect($db_server,$db_user,$db_passwd);
if (!$connection){
	die("Database connection is all sorts of fucked up, fix that shit.");
}
mysql_select_db($db_name, $connection);
$result = mysql_query("SELECT DATE_FORMAT(MAX(updated_date),'%m/%d/%Y %k:%i:%s') AS REFRESH_DATE FROM smf_slacker_tracker");
$row = mysql_fetch_array($result);
mysql_close();
echo "<span style=\"font-size:10px;\">Last refreshed: ".$row['REFRESH_DATE']."</span><br/><br/>";
?>

Minimum Kills:<input id="threshold" type="text" size="1" onchange="colorRows()" value="20"/><br/>
<table>
<tr>
	<td><input type="radio" id="filter_all" name="filter" onclick="doFilter()"/ checked="true">All</td>
	<td><input type="radio" id="filter_pOnly" name="filter" onclick="doFilter()"/>P only</td>
	<td><input type="radio" id="filter_noM" name="filter" onclick="doFilter()"/>Not M</td>
</tr>
</table>

<table>
<tr><td class="overacheiver">&nbsp;&nbsp;&nbsp;</td><td style="background-color:#ffffff;">Superstar (more than 20 over minimum)</td><tr>
<tr><td class="notSlacker">&nbsp;&nbsp;&nbsp;</td><td style="background-color:#ffffff;">Not a slacker</td><tr>
<tr><td class="almostSlacker">&nbsp;&nbsp;&nbsp;</td><td style="background-color:#ffffff;">Lazy (less than 5 kills from being a slacker)</td><tr>
<tr><td class="slacker">&nbsp;&nbsp;&nbsp;</td><td style="background-color:#ffffff;">Total slacker</td><tr>
</table>
</div>

<br/>
<div id="table_div">
<button onclick="prev()">Previous Month</button>
<span id="tblDate"></span>
<button onclick="next()">Next Month</button>
<div id="header_div">
<table id="killHeaders">
<colgroup>
	<col width="121px"/>
	<col width="122px"/>
	<col width="40px"/>
	<col width="57px"/>
</colgroup>
<thead>
	<th onclick="document.getElementById('kill_pilot').click()">Pilot</th>
	<th onclick="document.getElementById('kill_title').click()">Title</th>
	<th onclick="document.getElementById('kill_kills').click()">Kills</th>
	<th onclick="document.getElementById('kill_losses').click()">Losses</th>
</thead>
<tbody>
</tbody>
</table>
</div>
<div id="data_div">
<table id="killTable" class="sortable">
<colgroup>
	<col width="121px"/>
	<col width="122px"/>
	<col width="40px"/>
	<col width="57px"/>
</colgroup>
<thead style="display:none;">
	<th id="kill_pilot">Pilot</th>
	<th id="kill_title">Title</th>
	<th id="kill_kills">Kills</th>
	<th id="kill_losses">Losses</th>
</thead>
<tbody>
</tbody>
</table>
</div>
</div>
<div style="float:left;width:100%;height:25px;">&nbsp;</div>
<div id="killGraph_div"></div>
</div>