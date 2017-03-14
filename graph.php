<?php
require_once('common.php');	

$filtername=FILTER_NAME;
$query=QUERY;
echo "Updating Jira data".EOL;
flushout();
$data = new Filter($filtername,$query);

if(PROJECT_LAYOUT == JIRA_STRUCTURE)
{
	echo "Reading Jira structure".EOL;
	flushout();
	$layout = new Structure(PROJECT_LAYOUT);
}
else
{
	$layout = new Gan(GAN_FILE);
	echo "Reading Layout from ".GAN_FILE." ".EOL;
	
}

$project = new Project($layout,$data,"2017-02-03");

$jsgantt = new JSGantt("gantt\\data",$project);
$jsgantt->Save();


echo "Done";
?>