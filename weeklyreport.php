<?php
require_once('common.php');
require_once('project.php');

//echo date("Y-m-d", strtotime(date('Y')."W01"));
//echo date('Y');
if(strlen($date)==0)
	$date = date('Y-M-d');


$ndate = new DateTime($date);
$week = $ndate->format("W");
$thisfriday = date('Y-M-d',strtotime('this friday', strtotime( $date)));

$filtername=FILTER_NAME;
$query=QUERY;
$users=USERS_WEEKLY_REPORT;


$filter = new Filter($filtername,$query);

if(strlen($users)>0)
	$twtasks = $filter->GetWeeklyReport($date,$users);
else
	$twtasks = $filter->GetWeeklyReport($date);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<?php echo "<title>Weekly Report ".strtoupper($filtername)."</title>"; ?>
<style type="text/css" media="screen">
@import url(css/css-report.css);
</style>
<style>
.inline { 
    display: inline-block; 
    }
</style>
</head>
<body>



<div id="thebox">
  <?php 
  echo  '<h3>Project feed for the  week '.$week.' - Ending '.$thisfriday.'</h3>';
  ?>
  <div id="content">
  <?php 
	foreach($twtasks as $twtask)
	{
		//echo "<h1>".$twtask->key." ".$twtask->summary."</h1>";
		//echo '<a  href="'.' '.'" fdfd</a>';
		$jiralink=JIRA_URL."/browse/".$twtask->key;
		$linktext = '<a href="'.$jiralink.'">'.$twtask->key.'</a>';
		echo '<div class="inline"><div>'.$linktext.'</div></div>';
		echo '<div class="inline"><div><h3>'."&nbsp&nbsp;&nbsp;&nbsp".$twtask->summary.'</h3></div></div>';

		for($i=count($twtask->worklogs)-1;$i>=0;$i--)
		{
			$worklog = $twtask->worklogs[$i];
			if($worklog->thisweek == 1)
			{
				$time = explode(":",$worklog->time);
				$date = new DateTime($worklog->started);
				$date = $date->format('d M');
				echo '<p style="clear:both;">'.$worklog->comment.'</p>';
				$userlink=JIRA_URL."/secure/ViewProfile.jspa?name=".$worklog->author;
				echo '<p align="right">Posted by <a href="'.$userlink.'">'.$worklog->displayname." | ".$date.":".$time[0].':'.$time[1].'</a></p>';
			}
		}
	}
  ?>
  </div>
</div>
<br /><br />
<div id="foot">Ganjira Tools</div>
<!-- Designed by DreamTemplate. Please leave link unmodified. -->
<br><center><a href="none" title="" target="_blank">Feedback Mumtaz_Ahmad@mentor.com</a></center>
</body></html>