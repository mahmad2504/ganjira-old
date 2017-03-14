<?php
require_once('common.php');


//echo date("Y-m-d", strtotime(date('Y')."W01"));
//echo date('Y');
if(strlen($date)==0)
	$date = date('Y-M-d');

$filtername=FILTER_NAME;
$query=QUERY;
$users=USERS_TIMESHEET;

$filter = new Filter($filtername,$query);

if(strlen($users)>0)
	$rows = $filter->GetTimeSheet($date,$users);
else
	$rows = $filter->GetTimeSheet($date);

$ndate = new DateTime($date);
$week = $ndate->format("W");

$sdate = date('Y-M-d');
$sdate = new DateTime($sdate);
$sweek = $sdate->format("W");

$reportlink = "weeklyreport.php?date=".$date;


$i = $week>$sweek?intval($week):intval($sweek);

$weeklist=array();
for($i=$i;$i>0;$i--)
{
	$str = "";
	if(strlen($i)==1)
		$str = "0".$i;
	else
		$str = $i;
	$str = "W".$str;
	$weeklist[$i] = date("Y-m-d", strtotime(date('Y').$str));

}

	
$thisfriday = date('Y-M-d',strtotime('this friday', strtotime( $date)));
if (!isset($rows[0]))
{
	echo "There is no time log for the week ending ".$thisfriday." so far\n";
	return;
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php echo "<title>Time Sheet ".strtoupper($filtername)."</title>"; ?>
<link rel="stylesheet" type="text/css" media="screen" href="css/css-table.css" />
<style type="text/css" media="screen">
@import url(css/css-report.css);
a:link, a:visited, a:active {
	color: #000;
	text-decoration: underline;
}
</style>
<script type="text/javascript" src="js/jquery-1.2.6.min.js"></script>
<script type="text/javascript" src="js/style-table.js"></script>
</head>

<body>

<table id="timelog" summary="Hello">

	<caption>Time Logs for the week <?php echo $week." Ending ".$thisfriday; ?></caption>
    
    <thead>    
    	<tr>
            <th scope="col" rowspan="2">Tasks</th>
            <th scope="col" colspan=" <?php echo count($rows[0])  ?> ">Time Spent - Week 
				<select onChange="ComboChange(this)">
				<?php
					foreach($weeklist as $wek=>$value)
					{
						
						if(intval($wek) == intval($week))
							echo '<option value="'.$value.'" selected>'.$wek.'</option>';
						else	 
							echo '<option value="'.$value.'">'.$wek.'</option>';
					}
				/*	<option value="41">4</option>
					<option value="3">3</option>
					<option value="2">2</option>
					<option value="1">1</option>*/
				?>
				</select>
			</th>
        </tr>
        
        <tr>
		<?php  
			$authors = $rows['header'];	
			foreach($authors as $author)
				echo '<th scope="col">'.$author.'</th>';
		?>
        
        </tr>        
    </thead>
    
    <tfoot>
    	<tr>
        	<th scope="row">Total Days</th>
			<?php     
			$row = $rows['footer'];
			foreach($row as $timespent)
				echo '<td>'.round($timespent,1).'</td>';
			?>
        
        </tr>
    </tfoot>
    
    <tbody>
		<?php
			for($i=0;$i<(count($rows)-2);$i++)
			{
				echo '<tr>';
				echo '<th scope="row">'.$rows[$i][0].'</th>';
				for($j=1;$j<count($rows[$i]);$j++)
				{	
					echo  '<td>'.round($rows[$i][$j],1).'</td>';
					
				}
				echo '</tr>';
			}
		?>
    	
    </tbody>

</table>

<a href="<?php echo $reportlink; ?> ">Weekly Report</a>

<script type='text/javascript'>
    function ComboChange(a)
    {
        //value = document.getElementById(a.value);
		this.document.location.href = "timesheet.php?date="+a.value;
		console.log(a.value);
		
		console.log
    }
</script>


<div style="color:#999;" id="foot">Ganjira Tools</div>
<!-- Designed by DreamTemplate. Please leave link unmodified. -->
<br><center><a  style="color:#999;" href="none" title="" target="_blank">Feedback Mumtaz_Ahmad@mentor.com</a></center>

</body>
</html>
