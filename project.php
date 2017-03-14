<?php
require_once('common.php');	

class Project {
	private $filter;
	private $structure;
	private $estimate=0;
	private $timespent=0;
	private $progress=0;
	private $start=0;
	private $status = "RESOLVED";
	private $end=0;
	
	public function __get($name) 
  	{
		switch($name)
		{
			case 'structure':
				return $this->structure;
			case 'estimate':
				return $this->estimate;
			case 'timespent':
				return $this->timespent;
			case 'progress':
				return $this->progress;
			case 'start':
				return $this->start;
			case 'status':
				return $this->status;
			case 'end':
				return $this->end;
			default:
				trace("error","cannot access property ".$name);
			
		}
	}
	function datesort($a, $b) 
	{
			$dateTimestamp1 = strtotime($a);
			$dateTimestamp2 = strtotime($b);
			return $dateTimestamp1 < $dateTimestamp2 ? -1: 1;
	}
	function ComputeStatus(&$task)
	{
		$status = array();
		if($task['isparent'] == 0)
		{
			if( (strtoupper($task['status']) == "OPEN") || (strtoupper($task['status']) == "IN PROGRESS") || (strtoupper($task['status']) == "BLOCKED"))
				return "IN PROGRESS";
			else
				return "RESOLVED";
		}
		$task['status']="RESOLVED";
		for($i=0;$i<count($task['children']);$i++)
		{
			$ntask = &$task['children'][$i];
			$status = $this->ComputeStatus($ntask);
			if($status == "IN PROGRESS")
				$task['status']="IN PROGRESS";
		}
		//echo $task['status'].EOL;
		return $task['status'];
	}
	function CompudeEndDate($start,$dur)
	{
		//echo "start=".$start." duration=".$dur." ";
		if($dur == 0)
			return $start;
		while($dur)
		{
			$dayofweek = date('l', strtotime($start));
			$dayofweek = strtolower(substr($dayofweek,0,3));
			if(($dayofweek == "sat") || ($dayofweek == "sun")) {}
			else
				$dur--;
			$start = date('Y-m-d', strtotime($start. ' + 1 day'));
		}
		$end = date('Y-m-d', strtotime($start. ' - 1 day'));
		//echo "end=".$end;
		return $end;
	}
	function ComputeEnd(&$task)
	{
		$ends =  array();
		if($task['isparent'] == 0)
		{
			//echo "ddd".$task['end']."\n";
			if(strlen($task['end'])>0)
				$end = $task['end'];
			else
			{
				$dur = round($task['timeoriginalestimate']/(8*60*60));
				//echo $task['key']." ".$dur."\n";
				$end = $this->CompudeEndDate($task['start'],$dur);
			}
			$task['end'] = $end;
			return $task['end'];
		}
		$ends[] = $task['end'];
		for($i=0;$i<count($task['children']);$i++)
		{
			$ntask = &$task['children'][$i];
			$ends[] = $this->ComputeEnd($ntask);
		}
		usort($ends,array( $this, 'datesort' ));
		$task['end'] = $ends[count($ends)-1];
		
		//echo $task['key']."-->";
		//foreach($starts as $start)
		//	echo $start." ";
		//echo "\n";
		//$task['start'] = $total;
		//echo $task['key']." ".$total."\n";
		//return $total;
		return $task['end'];//$ends[0];
	}
	function ComputeStart(&$task)
	{
		$starts =  array();
		if($task['isparent'] == 0)
		{
			
			if (count($task['worklogs'])>0)
			{
				$task['start'] = $task['worklogs'][0]->started;
				//echo $task['key']." ".$task['start']."\n";
			}
			else
			{
				if( strlen($task['start']) == 0)
					$task['start'] = date("Y-m-d");
			}
			return $task['start'];
		}
		for($i=0;$i<count($task['children']);$i++)
		{
			$ntask = &$task['children'][$i];
			$starts[] = $this->ComputeStart($ntask);
		}
		usort($starts,array( $this, 'datesort' ));
		$task['start'] = $starts[0];
		
		//echo $task['key']."-->";
		//foreach($starts as $start)
		//	echo $start." ";
		//echo "\n";
		//$task['start'] = $total;
		//echo $task['key']." ".$total."\n";
		//return $total;
		return $task['start'];
	}
	
	function ComputeEstimate(&$task)
	{
		$total = 0;
		if($task['isparent'] == 0)
		{
			if($task['timeoriginalestimate'] > 0)
			{
				$task['noestimate'] =  0;
			}
			else 
			{
				$task['timeoriginalestimate'] = $task['timespent'];
				$task['noestimate'] =  1;
				//$task['timeoriginalestimate']= 0;
			}
			
			if(($task['status'] == "Resolved")||($task['status'] == "Closed"))
				$task['timeoriginalestimate'] = $task['timespent'];
			//echo $task['key']."    ".$task['timeoriginalestimate']."<br>";
			return $task['timeoriginalestimate'];
		}
			
		//echo $task['key']." ".$task['isparent']."\n";
		for($i=0;$i<count($task['children']);$i++)
		{
			$ntask = &$task['children'][$i];
			$est = intval($this->ComputeEstimate($ntask));
			$total += $est;
			//echo $ntask['key']." ".$est/(8*60*60)."\n";
			
		}
		if($task['timeoriginalestimate'] > 0)
		{
			if(($task['status'] == "Resolved")||($task['status'] == "Closed"))
		        $task['timeoriginalestimate'] = $total;
		}
		else
			$task['timeoriginalestimate'] = $total;
		//echo $task['key']."    ".$task['timeoriginalestimate']."<br>";
		//echo $task['key']." ".$total."\n";
		return $task['timeoriginalestimate'];
	}
	function ComputeTimeSpent(&$task)
	{
		$total = 0;
		if($task['isparent'] == 0)
		{
			if( ($task['status'] == "Resolved") || ($task['status'] == "Closed"))
				return $task['timeoriginalestimate'];
			
			return $task['timespent']>$task['timeoriginalestimate']?$task['timeoriginalestimate']:$task['timespent'];
		}
			
		//echo $task['key']." ".$task['isparent']."\n";
		for($i=0;$i<count($task['children']);$i++)
		{
			$ntask = &$task['children'][$i];
			$total += intval($this->ComputeTimeSpent($ntask));
		}
		
		$task['timespent'] = $total > $task['timeoriginalestimate']? $task['timeoriginalestimate']:$total;
		$task['acc_timespent'] =  $total;
		//$task['timespent'] = $total;
		return $task['timespent'];
	}
	function ComputeProgress(&$task)
	{
		if($task['isparent'] == 0)
		{
			if($task['noestimate'] ==  0)
				$task['progress'] = ($task['timespent']/$task['timeoriginalestimate'])*100;
			else
			{
				$task['timeoriginalestimate'] = $task['timespent'];
				$task['progress'] = "-100";
			}
			//echo $task['key']." p=".$task['progress']."\n";
			if( strtoupper($task['status']) == "IN PROGRESS")
				return 1;
			return 0;
		}	
		//echo $task['key']." ".$task['isparent']."\n";
		$status = 0;
		for($i=0;$i<count($task['children']);$i++)
		{
			$ntask = &$task['children'][$i];
			$status += $this->ComputeProgress($ntask);
		}
		
		if($task['timeoriginalestimate'] > 0)
		{
			$task['progress'] = ($task['timespent']/$task['timeoriginalestimate'])*100;
			//if( ($status > 0) && ($task['progress'] == 100))
			//{
			//	$task['progress'] = 99;
			//}
		}
		else
			$task['progress'] = 0;
	}
	function __construct($structure,$filter,$date=null)
	{
		$this->filter = $filter;
		for($i=0;$i<count($structure->tasks);$i++)
		{
			$task = &$structure->tasks[$i];
			
			if(isset($filter->tasks->$task['key']))
			{
				foreach($filter->tasks->$task['key'] as $property => $value)  
					$task[$property] = $value;
			}
			else
				trace('error',$task['key']." data not found in filter (".$this->filter->query.")");
		}
		$starts = array();
		$ends = array();
		$status = "RESOLVED";
        if($date != null)
		{
			// Remove tasks that are newer than $date
			for($i=0;$i<count($structure->tasks);$i++)
			{
				$task = &$structure->tasks[$i];
			
				if(strtotime($task['created'])>strtotime($date))
				{
					$structure->tasks[$i]['timeoriginalestimate'] = 0;
					$structure->tasks[$i]['timespent'] = 0;
					$structure->tasks[$i]['worklogs'] = array();
				}
				for($j=0;$j< count($structure->tasks[$i]['worklogs']); $j++)
				{
					if(strtotime($structure->tasks[$i]['worklogs'][$j]->started)>strtotime($date))
					{
						$structure->tasks[$i]['worklogs'][$j]->started = 0;
						$structure->tasks[$i]['timespent'] = $structure->tasks[$i]['timespent'] - $structure->tasks[$i]['worklogs'][$j]->timespent;
					}
				}
			}
		}
	
		for($i=0;$i<count($structure->tree);$i++)
		{
			
			$task = &$structure->tree[$i];
			$this->ComputeEstimate($task);
			$this->ComputeTimeSpent($task);
			$this->ComputeProgress($task);
			$starts[] = $this->ComputeStart($task);
			$ends[] = $this->ComputeEnd($task);
			$sta = $this->ComputeStatus($task);
			if($this->ComputeStatus($task) ==  "IN PROGRESS")
				$status = "IN PROGRESS";
				
			//echo $task['acc_timespent']/(8*60*60)."<br>";
			//if($task['timeoriginalestimate'] > 0)
			//	$task['progress'] = ($task['timespent']/$task['timeoriginalestimate'])*100;
			//else
			//	$task['progress'] = 0;
			//echo $task['key']." est=".c/(8*60*60)." ts=".$task['timespent']/(8*60*60)."\n";//." p=".$task['progress']."\n";
			//echo $task['timeoriginalestimate']/(8*60*60)."<br>";
			$this->estimate += $task['timeoriginalestimate'];
			if( ($task['status'] == "Resolved") || ($task['status'] == "Closed"))
				$this->timespent += $task['timeoriginalestimate'];
			else
				$this->timespent += $task['timespent']>$task['timeoriginalestimate']?$task['timeoriginalestimate']:$task['timespent'];
			
			//echo $task['key']." st=".$task['start']." est=".$task['timeoriginalestimate']." end=".$task['end']." ts=".$task['timespent']." p=".$task['progress']."\n";
		}
		$this->status = $status;
		if($this->estimate > 0)
			$this->progress = ($this->timespent/$this->estimate)* 100;
		else
			$this->progress = 0 ;
		
		usort($starts,array( $this, 'datesort' ));
		usort($ends,array( $this, 'datesort' ));
		$this->start = $starts[0];
		$this->end = $ends[count($ends)-1];
		$this->structure = $structure;
		//echo "Start=".$this->start." est=".$this->estimate." ts=".$this->timespent." p=".$this->progress." end=".$this->end."\n";
	}
}
?>