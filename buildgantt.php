<?php
require_once('common.php');	

$filtername=FILTER_NAME;
$query=QUERY;
echo "Updating Jira data".EOL;
flushout();
$data = new Filter($filtername,$query);

//$KEY = 'MEH-2773';
//print_r($filter->tasks->$KEY);


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

$project = new Project($layout,$data);
// Save JS Gantt
$jsgantt = new JSGantt("gantt\\data",$project);
$jsgantt->Save();

// Save Gant file
$gan = new Gan(GAN_FILE);
$gan->Save($project);
//$project->SaveJSGanttXML("gantt\\data");
echo "Done";
?>