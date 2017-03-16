<?php
require_once('common.php');	

class JSGantt {
	private $filename;
	private $project;
	function __construct($filename,$project)
	{
		$this->filename = $filename;
		$this->project = $project;
	}
	function  GetColor($status)
	{
		if( strtoupper($status) == "IN PROGRESS")
		{
			return "green";
		}
		else if((strtoupper($status) == "CLOSED") || (strtoupper($status) == "RESOLVED"))
			return "lightgrey";
		else 
			return "";
	}
	function TaskJSGanttXML($xml,$task,$id,$pid)
	{
		//for($i=0;$i<$task['level'];$i++)
		//	echo "   ";
		$node = $xml->addChild("task");
		$node->addChild("pID",$id);
		$length = 65-($task['level']*3);
		//echo $task['summary']." ".$task['level']." ".$length."\n";
		//echo substr($task['summary'],0,$length);
		//if(strlen($task['summary'])> $length)
		//	$node->addChild("pName","#style=color:red ".substr($task['summary'],0,$length)."...");
		//else
		//	$node->addChild("pName",$task['summary']);
		$node->addChild("pStart",$task['start']);
		
		//echo $task['summary']." ".$task['status'];
		if( (strtoupper($task['status']) != "CLOSED") && (strtoupper($task['status']) != "RESOLVED"))
		{
			
			$today = strtotime(date('Y-M-d'));
			$end = strtotime($task['end']);
			if( $today > $end )
			{
				//echo " red".EOL;
				$node->addChild("pEnd","#style=color:red ".$task['end']);
			}
			else
				$node->addChild("pEnd",$task['end']);
				
		}
		else
			$node->addChild("pEnd",$task['end']);
		//echo EOL;
		
		$node->addChild("pEnd",$task['end']);
		$node->addChild("pMile",0);
		//if(($task['status'] == "Resolved")||($task['status'] == "Closed"))
		//	$node->addChild("pComp",100);
		//else
		
		if($task['issuetype'] == "Requirement")
		{
			$node->addChild("pCduration"," ");
			if((strtoupper($task['status']) == "CLOSED") || (strtoupper($task['status']) == "RESOLVED"))
				$node->addChild("pComp","Received");
			else
				$node->addChild("pComp","Awaiting");
		}
		else
		{
			$node->addChild("pComp",round($task['progress']));
			$dur = round($task['timeoriginalestimate']/(8*60*60),1);
			if($dur == 0)
				$node->addChild("pCduration"," ");
			else
			{
				if($task['timeoriginalestimate_orig'] > 0)
				{
					if($task['timeoriginalestimate'] > $task['timeoriginalestimate_orig'])
						$node->addChild("pCduration","#style=color:red ".round($task['timeoriginalestimate']/(8*60*60),1));
					else
						$node->addChild("pCduration",round($task['timeoriginalestimate']/(8*60*60),1));
				}
				else
					$node->addChild("pCduration",round($task['timeoriginalestimate']/(8*60*60),1));
				
			}
		}
		
		//$node->addChild("pCduration",round($task['timeoriginalestimate']/(8*60*60),1));
		//$node->addChild("pCduration"," ");
			$node->addChild("pGroup",$task['isparent']);
		$node->addChild("pParent",$pid);
		
		$node->addChild("pDepend","");
		$node->addChild("pNotes",WEBLINK.$task['key']);
		
		$color="";
		if($task['isparent'] == 0)
		{
			if( strtoupper($task['status']) == "IN PROGRESS")
			{
				if($task['issuetype'] == "Requirement")
				{
					$today = strtotime(date('Y-M-d'));
					$end = strtotime($task['end']);
					if( $today > $end ) // LaTE
					{
						$node->addChild("pClass","gtaskred");
					}
					else
				$node->addChild("pClass","gtaskblue");
				}
			else
					$node->addChild("pClass","gtaskgreen");
				}
				if((strtoupper($task['status']) == "CLOSED") || (strtoupper($task['status']) == "RESOLVED"))
				{
					$node->addChild("pClass","gtaskcomplete");
				}
				else
				{
				if($task['progress'] > 0)
					{
						$node->addChild("pClass","gtaskyellow");
					}
					else
					{
						$node->addChild("pClass","gtaskopen");
					}
				}
			}
		else
		{
			$label_found = 0;
			if($task['level'] == 3)
			{
				$node->addChild("pOpen",0);
				$label_found = 1;
			}
			else
			{
				foreach($task['labels'] as $label)
				{
					if(strtolower($label) == "gantt_show_closed")
					{
						$node->addChild("pOpen",0);
						$label_found = 1;
						break;
					}
				}
			}
			if($label_found)
			{
				
			}
			else
			{
				if($task['status'] == "RESOLVED")
					$node->addChild("pOpen",0);
				else 
					$node->addChild("pOpen",1);
			}
		}
	
		
		$color = $this->GetColor($task['status']);
		
		if($task['issuetype'] == "Requirement")
		{
			$task['summary'] = "->".$task['summary'];
		}
			
		if(strlen($task['summary'])> $length)
			$node->addChild("pName","#style=color:".$color." ".substr($task['summary'],0,$length)."...");
		else
			$node->addChild("pName","#style=color:".$color." ".$task['summary']);
		
		$node->addChild("pLink",WEBLINK.$task['key']);
		
		if($task['isparent'])
			$node->addChild("pRes","");
		else
			$node->addChild("pRes",$task['assignee']);
		
		$node->addChild("pCaption",$task['key']);
		$node->addChild("pStatus",$task['status_orig']);
		$node->addChild("pEnd",$task['end_orig']);
		$node->addChild("pStart",$task['start_orig']);
		$node->addChild("pEstimate",$task['timeoriginalestimate_orig']);
		$node->addChild("pTimeSpent", $task['timespent_orig']);
		//echo $id." ".$task['key']." ".$pid."\n";
		$ntid = $id+1;
		foreach($task['children'] as $ntask)
		{
			$ntid=$this->TaskJSGanttXML($xml,$ntask,$ntid,$id);
		}
		return $ntid;
	}
	function Save()
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><project></project>', null, false);
		$xml['xmlns:xsi'] = "http://www.w3.org/2001/XMLSchema-instance";
		
		$id = 1;
		$pid = 0;
		$node = $xml->addChild("task");
		$node->addChild("pID",$id);
	
		$color = $this->GetColor($this->project->status);
		$node->addChild("pName","#style=color:".$color." Project");
		
		$node->addChild("pStart",$this->project->start);
		
		///////////////////////////////////////////////////////////////////////////////////////
		if( $this->project->status != "RESOLVED")
		{
			$today = strtotime(date('Y-M-d'));
			$end = strtotime($this->project->end);
			if( $today > $end )
				$node->addChild("pEnd","#style=color:red ".$this->project->end);
			else
				$node->addChild("pEnd",$this->project->end);
		}
		else
			$node->addChild("pEnd",$this->project->end);
		//////////////////////////////////////////////////////////////////////////////////////
		//$node->addChild("pEnd",$this->end);
		$node->addChild("pMile",0);
		//echo $this->progress."\n";
		$node->addChild("pComp",round($this->project->progress));
		$node->addChild("pGroup",1);
		$node->addChild("pParent",$pid);
		$node->addChild("pOpen",1);	
		$node->addChild("pDepend","");
		$node->addChild("pNotes","");
		
		$node->addChild("pCduration",round($this->project->estimate/(8*60*60)));
		
		
		
		$ntid = $id+1;
		//echo $id." "."None"." ".$pid."\n";
		foreach($this->project->structure->tree as $task)
		{
			$ntid = $this->TaskJSGanttXML($xml,$task,$ntid,$id);
		}
		$data = $xml->asXML();
		file_put_contents($this->filename.".xml", $data);
	}
}
?>