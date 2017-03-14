<?php
class Filter {
	private $tasks;
	private $query;
	
	private $twauthors;
	private $twtasks;
	private $grand_total;
	
	public function __get($name) 
  	{
		switch($name)
		{
			case 'tasks':
				return $this->tasks;
			case 'query':
				return $this->query;
			default:
				trace("error","cannot access property ".$name);
			
		}
	}
	
	function __construct($name,$query)
	{
		$fields = 'key,status,summary,start,end,timeoriginalestimate,timespent,labels,assignee,created,issuetype';
		$this->query = $query;
		if (file_exists($name.".filter")) 
		{
			//echo "Updating\n";
			$last_update_date = date ("Y/m/d H:i" , filemtime($name.".filter"));
			$data = file_get_contents($name.".filter");
			$this->tasks = json_decode( $data );
			
			
			//$jtasks = Jira::Search("key=".$this->key,1,"key,status,timeoriginalestimate,timespent,progress,".JIRA_SCHEDULED_START.",".JIRA_SCHEDULED_END.",".JIRA_AGG_TIME_ORIG_ESTIMATE.",summary,fixVersion,labels,aggregateprogress,labels,assignee");
			$tasks  = Jira::Search($query." and updated>'".$last_update_date."'",1000,$fields);
			for($i=0;$i<count($tasks);$i++)
			{
				$worklogs = Jira::GetWorkLog($tasks[$i]['key']);
				$tasks[$i]['worklogs'] =  $worklogs;
				$this->tasks->$tasks[$i]['key'] = $tasks[$i];
				
			}
		}
		else
		{
			//echo "Rebuilding\n";
			$tasks = Jira::Search($query,1000,$fields);
			for($i=0;$i<count($tasks);$i++)
			{
				$worklogs = Jira::GetWorkLog($tasks[$i]['key']);
				$tasks[$i]['worklogs'] =  $worklogs;
				$this->tasks[$tasks[$i]['key']] = $tasks[$i];
			}
		}
		file_put_contents( $name.".filter", json_encode( $this->tasks ) );
		$data = file_get_contents($name.".filter");
		$this->tasks = json_decode( $data );
	}
	function BuildTimeSheet($date,$users=null)
	{
		if($users !=  null)
			$users = explode(",",$users);
	
		$thisfriday = date('Y-M-d',strtotime('this friday', strtotime( $date)));
		$twtasks = array();
		$twauthors = array();
		
		// Identify users and this week tasks
		foreach($this->tasks as $key=>$task)
		{
			foreach($task->worklogs as $worklog)
			{
				if($users != null)
				{
					if (in_array($worklog->author, $users))
					{ }
					else
						continue;
				}
				$friday = date('Y-M-d',strtotime('this friday', strtotime( $worklog->started)));
				if(strtotime($friday) == strtotime($thisfriday))
				{
					$twtasks[$task->key] = $task;
					$twauthors[$worklog->author] = 0.0;
				}
			}
		}
		
		// Assign all users to each task 
		foreach($twtasks as $key=>$twtask)
			$twtask->authors=$twauthors;
		
		$grand_total = 0.0;
		foreach($twtasks as $key=>$twtask)
		{
			$total=0.0;
			//echo $twtask->key." ".$twtask->summary."\n";
			foreach($twtask->worklogs as $worklog)
			{
				$friday = date('Y-M-d',strtotime('this friday', strtotime( $worklog->started)));
				$worklog->thisweek=0;
				if(strtotime($friday) == strtotime($thisfriday))
				{
					$worklog->thisweek=1;
					//echo $worklog->author." ".$worklog->timespent."\n";
					if( isset($twtask->authors[$worklog->author]))
					{
						$twtask->authors[$worklog->author] += (float)$worklog->timespent;
						$total += (float)$worklog->timespent;
					}
				}
			}
			$twtask->total = $total;
			$this->grand_total += $total;
		}
		
		foreach($twtasks as $key=>$twtask)
		{
			foreach($twtask->authors as $author=>$worklog)
			{
				$twauthors[$author] += $worklog;
			}
		}
		$this->twauthors = $twauthors;
		$this->twtasks = $twtasks;
	}
	function GetTimeSheet($date,$users=null)
	{
		$this->BuildTimeSheet($date,$users);
		$grand_total = $this->grand_total;

		// Fill data in return format
		$rows = array();
		$row = array();
		foreach($this->twauthors as $author=>$worklog)
		{
			$row[] = $author;
		}
		$row[] = "Total";
		$rows['header'] = $row;
		
		$row = array();
		foreach($this->twauthors as $author=>$worklog)
		{
			$row[] = $worklog;
		}
		$row[] = $grand_total;
		$rows['footer'] = $row;
		
		$row = array();
		$i=0;
		foreach($this->twtasks as $key=>$twtask)
		{
			$row = array();

			$row[]= '<a href="'.JIRA_URL.'/browse/'.$twtask->key.'">'. $twtask->summary.'</a>';
			foreach($twtask->authors as $author=>$worklog)
			{
				$row[] = $worklog;
				//$twauthors[$author] += 	$worklog;
			}
			$row[] = $twtask->total;
			$rows[] = $row;
			$i++;
		}
		return $rows;
	}
	function sort($twtask1, $twtask2) 
	{
		/*echo $twtask1->summary.EOL;
		foreach($twtask1->worklogs as $worklog)
		{
			echo $worklog->started." ".$worklog->time.EOL;
		}
		echo $twtask2->summary.EOL;
		foreach($twtask2->worklogs as $worklog)
		{
			echo $worklog->started." ".$worklog->time.EOL;
		}*/
		$date1 = $twtask1->worklogs[count($twtask1->worklogs)-1]->started;
		$time1 = $twtask1->worklogs[count($twtask1->worklogs)-1]->time;
		$date2 =  $twtask2->worklogs[count($twtask2->worklogs)-1]->started;
		$time2 =  $twtask2->worklogs[count($twtask2->worklogs)-1]->time;
		

		if( $date1 < $date2)
		{
			//echo "Task1 < Task2".EOL;
			return 1;
		}
		else if( $date1 > $date2)
		{
			//echo "Task1 > Task2".EOL;
			return -1;
		}
		else
		{
			if($time1 < $time2)
			{
				//echo "T::Task1 < Task2".EOL;
				return 1;
			}
			else
			{
				//echo "T::Task1 >= Task2".EOL;
				return -1;
			}
		}
		
		//echo EOL;
		//foreach($twtask1 as $twtask)
		//	$dateTimestamp1 = strtotime($a);
		//	$dateTimestamp2 = strtotime($b);
		//	return $dateTimestamp1 < $dateTimestamp2 ? -1: 1;
		return 1;
	}
	function GetWeeklyReport($date,$users=null)
	{
		$tasks = array();
		$this->BuildTimeSheet($date,$users);
		foreach($this->twtasks as $twtask)
		{
			$ignore = false;
			foreach($twtask->labels as $label)
			{
				if($label == "noweeklyreport")
				{
					$ignore = true;
				}
			}
			
			if(!$ignore)
				$tasks[] = $twtask;
		}
		usort($tasks,array($this,'sort'));
		return $tasks;
	}
}
?>