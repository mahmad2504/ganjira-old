<?php
require_once('common.php');	

class Structure {
	public $tree;
	public $tasks;
	private $name;
	private $structureid;
	public function __get($name) 
  	{
		switch($name)
		{
			default:
				trace("error","cannot access property ".$name);
			
		}
	}

	function PrintSubTask($level,$task)
	{
		$level = $level+1;
		for($i=0;$i<$level;$i++)
			echo "  ";
		
		echo $task['key']."\n";
		foreach($task['children'] as $stask)
			$this->PrintSubTask($level,$stask);
	}
	
   	function __construct($structureid) 
   	{
   		if(!ctype_digit($structureid))
			trace("error","Invalid Structure Id");
		
		

		$rows = Jira::GetStructure($structureid);
   		$this->structureid = $structureid;
		$result = Jira::GETStructureInfo($structureid);
		
		if(isset($result->error))
		{
			trace('error',$result->error.EOL);
			exit(-1);
		}
		//print_r($result);
		//makedir("uploads\\".$structureid);
		$this->name = $result->name;
		DebugLog("Found structure ".$this->name);

		
	//	$level0 = array();
	//	$level1 = array();
	//	$level2 = array();
	//	$level3 = array();
	//	$root = array();
	//	$root['children'] =  array();
		
		//$parent = $root;
		foreach($rows as $row)
		{
			$query="id=".$row->taskid;
			$tasks = Jira::Search($query,1,"key");
			
			$tasks[0]['level'] = $row->level; 
			$tasks[0]['children'] = array();
			$this->tasks[] = &$tasks[0];
			
			if($row->level == 1)
			{
				$level0[] = &$tasks[0];
			}
			else
			{
				$level = 'level'.(string)($row->level-2);
				$lastindex = count($$level)-1;
				
				$arr = &$$level;
				$arr[$lastindex]['children'][] = &$tasks[0];
				$level = 'level'.(string)($row->level-1);
				$arr = &$$level;
				$arr[] = &$tasks[0];
			}
			//else if($row->level == 2)
			//{
			//	$level0[count($level0)-1]['children'][] = &$tasks[0];
			//	$level1[] = &$tasks[0];
			//	
			//}
			//else if($row->level == 3)
			//{
				//echo $level1[count($level1)-1]['key'];
			//	$level1[count($level1)-1]['children'][] = &$tasks[0];
			//	$level2[] = &$tasks[0];
			//}
			
			
			//if($tasks[0]['level'] == 1)
			//{
			//	$root['children'][] = $tasks[0];
			//}
			//else
			//$this->PlugTask($root,$tasks[0],$tasks[0]['level']);
			//if($tasks[0]['level'] == 2)
			//{
			//	$root['children'][count($root['children'])-1]['children'][] = $tasks[0];
			//}	
			//if($tasks[0]['level'] == 2)
			//{
			//	$root['children'][count($root['children'])-1]['children'][] = $tasks[0];
			//}
			
			//$parent = $$parent_array[count($$parent_array)-1];
			//echo $parent;
		}
		$this->tree = &$level0;
		//print_r($this->tree);
		for($i=0;$i<count($this->tasks);$i++)
		{
			//print_r($task);
			//echo count($task['children']);
			//echo "\n";
			if(count($this->tasks[$i]['children'])>0)
				$this->tasks[$i]['isparent'] = 1;
			else
				$this->tasks[$i]['isparent'] = 0;
		}
	}
}