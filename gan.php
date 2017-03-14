<?php
require_once('common.php');	
class Gan {
	public $tree;
	public $tasks;
	private $name;
	
	private $filename;
	private $doc;
	private $jira_col;
	private $xml;
	private $rid=0;
	public function __get($name) 
  	{
		switch($name)
		{
			default:
				trace("error","cannot access property ".$name);
			
		}
	}
	function Process($level,$tasknode,&$parent)
	{
		$task =  array();
		//for($i=0;$i<$level;$i++)
		//	echo "  ";

		$task['level'] = $level++;
		$task['children'] = array();
		$this->tasks[] = &$task;
		foreach($tasknode->childNodes as $cnode)
		{
			if($cnode->nodeName === 'customproperty')
			{
				$col = $cnode->getAttribute('taskproperty-id');
				if($col == $this->jira_col)
					$task['key'] = $cnode->getAttribute('value');
				
			}
			if($cnode->nodeName === 'task') 
			{
				$this->Process($level,$cnode,$task['children']);
			}
		}
		$parent[] = &$task;
	}
   	function __construct($filename=null) 
   	{
		if($filename == null)
			return;
		if(!file_exists ($filename))
		{
			$this->filename=$filename;
			return;
		}
		$xmldata = file_get_contents($filename);
		$this->filename =  $filename;
		$this->doc = new DOMDocument();
		$this->doc->loadXML($xmldata);
		$this->rid = 0;
		$xpath = new DOMXPath($this->doc);
		
		/*$resources = $this->doc->getElementsByTagname('resource'); 
		$resources_head = $xpath->query('/project/resources');
		foreach($resources as $resource)
			$resources_head->item(0)->removeChild($resource);
		
		
		$allocations = $xpath->query('/project/allocation');
		$allocations_head = $xpath->query('/project/allocations');
		foreach($allocations as $allocation)
			$allocations_head-item(0)->removeChild($allocation);*/
			
		$taskproperties = $xpath->query('/project/tasks/taskproperties/*');
		$this->jira_col = "";
		foreach ($taskproperties as $i => $taskproperty) 
		{
			if( strtoupper($taskproperty->getAttribute('name')) == "JIRA")
			{
				$this->jira_col = $taskproperty->getAttribute('id');
				break;
			}
		}
		if($this->jira_col == "")
			trace('error',"No Jira column found");
		
		
		$tasks = $xpath->query('/project/tasks/task');
		
		if(count($tasks) == 1)
		{
			if($tasks->item(0)->getAttribute('name') == "Project")
				$tasks = $xpath->query('/project/tasks/task/task');
		}
	
	
		$this->tasks = array();

		foreach ($tasks as $i => $tasknode) 
		{
			$this->Process(1,$tasknode,$this->tree);
		}

		for($i=0;$i<count($this->tasks);$i++)
		{
			if(count($this->tasks[$i]['children'])>0)
				$this->tasks[$i]['isparent'] = 1;
			else
				$this->tasks[$i]['isparent'] = 0;
			if($i > 0)
				if(!isset($this->tasks[$i]['key']))
					trace('error',"Jira key not found for some tasks");
		}
		
		////////////  Remove tasks too ////////////////////////
		/*$tasks = $xpath->query('/project/tasks/task');
		$tasks_head = $xpath->query('/project/tasks');
		foreach ($tasks as $task)
		{
			$tasks_head->item(0)->removeChild($task);
			//$tasks_head->removeChild($task);
		}*/
		//$this->doc->save($this->filename);
	
	}
	function ComputeDuration($start,$end)
	{
		$startt = strtotime($start);
		$endt = strtotime($end);
		$duration = floor(($endt - $startt)/(60 * 60 * 24));
		return $duration+1;
	}
	function AddResource($name)
	{
		if(strlen($name)==0)
			return -1;
		foreach($this->xml->resources->resource as $resource)
		{
			if($resource['name'] == $name)
				return $resource['id'];
		}		
		$resource = $this->xml->resources->addChild('resource');
		
		//echo "resource id=".$this->rid."\n";
		$resource->addAttribute('id',$this->rid);
		$id = $this->rid++;
		$resource->addAttribute('name',$name);
		$resource->addAttribute('function','Default:0');
		$resource->addAttribute('contacts',"");
		$resource->addAttribute('phone',"");
		return $id;
		//<resources>
        //<resource id="0" name="mumtaz" function="Default:0" contacts="" phone=""/>
    //</resources>
	}
	function AssignResourceToTask($taskid,$resourceid)
	{
		//echo "Assigning ".$resourceid." to ".$taskid."\n";
		$allocation = $this->xml->allocations->addChild('allocation');
		$allocation->addAttribute('task-id',$taskid);
		$allocation->addAttribute('resource-id',$resourceid);
		$allocation->addAttribute('function','Default:0');
		$allocation->addAttribute('responsible','true');
		$allocation->addAttribute('load','100');
	}
	function ProcessChildNodes($parent,$id,&$task)
	{
		$child = $parent->addChild('task');
		$child->addAttribute('id',$id);

		$child->addAttribute('name',$task['summary']);
		$child->addAttribute('meeting','false');
		$child->addAttribute('start',$task['start']);
		$duration = $this->ComputeDuration($task['start'],$task['end']);
		$child->addAttribute('duration',$duration);
		$child->addAttribute('expand','true');
		$child->addAttribute('complete',round($task['progress']));
		//<customproperty taskproperty-id="tpc0" value="MEH-2773"/>
		$customproperty = $child->addChild('customproperty');
		$customproperty->addAttribute('taskproperty-id',$this->jira_col);
		$customproperty->addAttribute('value',$task['key']);
		$rid = $this->AddResource($task['assignee']);
		if($rid >= 0)
		{
			//echo $id." ".$rid."\n";
			$this->AssignResourceToTask($id,$rid);
		}
		foreach($task['children'] as $stask)
		{
			$id = $id + 1;
			$id = $this->ProcessChildNodes($child,$id,$stask);
			
		}
		return $id;
	}
	
	function Save($project,$filename=null)
	{
		if($filename != null)
			$this->filename =  $filename;
		
		$this->xml=simplexml_load_file('template');
		
		$this->jira_col = "";
		foreach($this->xml->tasks->taskproperties->taskproperty as $taskproperty)
		{
			if( strtoupper($taskproperty['name']) == "JIRA")
			{
				$this->jira_col = $taskproperty['id'];
				break;
			}
		}
		if($this->jira_col == "")
			trace('error',"No Jira column found");
		
		$root = $this->xml->tasks->addChild('task');
		//<task id="0" name="task_1" color="#8cb6ce" meeting="false" start="2017-02-06" duration="1" complete="0" expand="true">
		$id = 0;
		$root->addAttribute('id',$id);
		$root->addAttribute('name','Project');
		$root->addAttribute('meeting','false');
		$root->addAttribute('start',$project->start);
		$duration = $this->ComputeDuration($project->start,$project->end);
		$root->addAttribute('duration',$duration);
		$root->addAttribute('expand','true');
		$root->addAttribute('complete',round($project->progress));
		
		foreach($project->structure->tree as  $task)
		{
			$id++;
			$id = $this->ProcessChildNodes($root,$id,$task);
		}
		
		$data = $this->xml->asXML();
		file_put_contents($this->filename,$data);

		
	}
}