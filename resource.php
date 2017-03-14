<?php
require_once('common.php');	

class ResourceChart {
	function __construct($project)
	{
		$resources = array();
		foreach($project->structure->tasks as $task)
		{
			foreach($task['worklogs'] as $worklog)
			{
				if(array_key_exists ( $worklog->author , $resources ))
				{   
					//$worklog->task = array();
					//$worklog->task['key'] =  $task['key'];
					//$worklog->task['summary'] =  $task['summary'];
					if(array_key_exists ( $task['key'] , $resources[$worklog->author] ))
						$resources[$worklog->author][$task['key']][] = $worklog;
					else
					{
						$resources[$worklog->author][$task['key']] = array();
						$worklog->task = $task['summary'];
						$resources[$worklog->author][$task['key']][] = $worklog;
					}
				}
				else
					$resources[$worklog->author] =  array();
			}
		}
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><project></project>', null, false);
		$xml['xmlns:xsi'] = "http://www.w3.org/2001/XMLSchema-instance";
		
		$id = 1;
		$pid = 0;
		
		foreach($resources as $resource => $tasks)
		{
			
			echo $resource."\n";
			foreach($tasks as $key=>$worklogs)
			{
				echo $key." ";
				echo $worklogs[0]->task."\n";
				$node = $xml->addChild("task");
				
				$node->addChild("pID",$id);
				$node->addChild("pID",$pid);
				$node->addChild("pStart",$worklogs[0]->started);
				
				foreach($worklogs as $worklog)
				{
					echo $worklog->started." ".$worklog->comment."\n";
				}
			}
		}
		$data = $xml->asXML();
		file_put_contents("resource.xml", $data);
	}
}
/*
$node = $xml->addChild("task");
			$node->addChild("pID",$id);
			$node->addChild("pStart",$worklog->started);
			echo $task['key']."  ".$worklog->started." ".$worklog->timespent."\n";
			if($worklog->timespent < 1)
				$end  = date('Y-m-d', strtotime($worklog->started. ' + 1 days'));
			else
				$end  = date('Y-m-d', strtotime($worklog->started. ' + '.$worklog->timespent.' days'));
			$node->addChild("pEnd",$end);
			$node->addChild("pMile",0);
			$node->addChild("pComp",50);
			$node->addChild("pCduration",2);
			$node->addChild("pGroup",0);
			$node->addChild("pParent",$pid);
			$node->addChild("pDepend","");
			$node->addChild("pNotes","");
			$node->addChild("pName",$worklog->comment);		
			$node->addChild("pLink","ddd");
			$node->addChild("pRes","me");
			$node->addChild("pCaption","hello");
			$id = $id+1;
*/
$filtername=FILTER_NAME;
$query=QUERY;

$data = new Filter($filtername,$query);

if(file_exists(GAN_FILE))
	$layout = new Gan(GAN_FILE);

$project = new Project($layout,$data);
$rc = new ResourceChart($project);

?>