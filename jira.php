<?php
require_once('common.php');		
class Object{
}


define("EPIC_NAME", "customfield_11441");
define("STORY_POINTS", "customfield_10022");
define("EXT_ID", "customfield_14151");


define("CUSTOM_START","customfield_13640");
define("CUSTOM_END","customfield_13641");
define("SCHEDULED_START", "customfield_11642");
define("SCHEDULED_END", "customfield_11643");

define("START",SCHEDULED_START);
define("END",SCHEDULED_END);


$fieldmap = array(START=>'start',
				  END=>'end',
				  EXT_ID=>'ext_id',
				  STORY_POINTS => 'story_points',
				  EPIC_NAME => 'epic_name'
				  );



//const issuetype = "issuetype";

class Jira 
{
	private static $curl = 0;
	private static $debug = 1;

	private static function getcurl()
	{
		if(self::$curl == 0)
			self::$curl = curl_init();
		return self::$curl;
	}
	private static function resetcurl()
	{
		$curl = self::getcurl();
		curl_reset($curl);
		//curl_setopt($curl, CURLOPT_HTTPGET, 1);
		//curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt_array($curl, array(
		CURLOPT_USERPWD => 'himp' . ':' . 'hmip',
		CURLOPT_RETURNTRANSFER => true
		));
	}
	
	static function RestApi($method,$resource)
	{

		$curl = self::getcurl();
		self::resetcurl();
		$url = JIRA_URL . '/rest/api/latest/' . $resource;

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_URL,$url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

		
		$result = curl_exec($curl);
		$ch_error = curl_error($curl); if ($ch_error) { trace('error',"$ch_error");} else { DebugLog($result);}
		$result = json_decode($result,true);
		if( is_array ( $result))
		{
			if (array_key_exists("errorMessages",$result))
			{
				print_r($result['errorMessages'][0]);
				return null;
			}
		}
		return $result;
	}
	private static function PUTFile($issue,$filepath)
	{
		$curl = self::getcurl();
		self::resetcurl();
		$headers = array('X-Atlassian-Token: nocheck','Content-Type: multipart/form-data');
		//$data = array("file" => "@" ";filename=in.gan");

		$data['file'] = new CurlFile($filepath, 'file', basename($filepath));
		
		$url = JIRA_URL . '/rest/api/latest/issue/'.$issue.'/attachments';
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl,  CURLOPT_POSTFIELDS ,$data);
		curl_setopt($curl, CURLOPT_URL, $url);
		$result = curl_exec($curl);
		$ch_error = curl_error($curl); if ($ch_error) { trace('error',"$ch_error");} else { DebugLog($result);}

		$returnvalue = json_decode($result,true);
		if(isset($returnvalue["errorMessages"]))
		{
			trace(ERROR,$returnvalue["errorMessages"][0]);
		}
		
	}
	
	static function GETStructureInfo($structid) 
	{
	
		$curl = self::getcurl();
		self::resetcurl();
	
		$url = JIRA_URL.'/rest/structure/2.0/structure/'.$structid;
		//$url = JIRA_URL.'/rest/structure/2.0/forest/latest?s={%22structureId%22:'.$structid.'}';
		curl_setopt($curl, CURLOPT_URL,$url);
	
		//echo $url;
		$result = curl_exec($curl);
		//echo $result;
		$ch_error = curl_error($curl); if ($ch_error) { trace('error',"$ch_error");} else { DebugLog($result);}

		//var_dump($result);

		$xml = simplexml_load_string($result);
		//print_r($xml);
		return $xml;
	}
	
	private static function GET($resource) 
	{
		$curl = self::getcurl();
		self::resetcurl();
	
		$url = JIRA_URL . '/rest/api/latest/' . $resource;
		curl_setopt($curl, CURLOPT_URL,$url);
		DebugLog($url);
		//echo $url;
		$result = curl_exec($curl);
		$ch_error = curl_error($curl); if ($ch_error) { trace('error',"$ch_error");} else { DebugLog($result);}

		$returnvalue = json_decode($result,true);
		if(isset($returnvalue["errorMessages"]))
		{
			trace(ERROR,$returnvalue["errorMessages"][0]);
		}
		return $returnvalue;
	}

	static function GetIssue($issueid,$fields)
	{
		return self::Get("issue/".$issueid."?fields=".$fields);
	}
	static  function GetStructure($structid) 
	{

		
		//$jdata = '{"requests":[{"forestSpec":{"structureId":473,"title":true},"rows":[69782,69802,69804,69806,69808,69810,69812,90101,132878,135432,141496,147896],"attributes":[{"id":"key","format":"text"},{"id":"summary","format":"text"},{"id":"editable","format":"boolean"},{"id":"project","format":"id"},{"id":"issuetype","format":"id"}]}]}';
		//dsdsd= '{"requests":[{"forestSpec":{"structureId":473,"title":true},"rows":[69782,69802,69804,69806,69808,69810,69812,90101,132878,135432,141496,147896],"attributes":[{"id":"done","format":"boolean"},{"id":"children-generators-hint","format":"html"},{"id":"url","format":"text"},{"id":"icon","format":"html"},{"id":"description","format":"html"},{"id":"extended-summary","format":"html"},{"id":"progress","format":"number","params":{"basedOn":"timetracking","resolvedComplete":true,"includeSelf":true}},{"id":"issuetype.icon","format":"html"},{"id":"priority.icon","format":"html"}]}]}';
		$jdata = '{"forests":[{"spec":{"type":"clipboard"},"version":{"signature":898732744,"version":0}},{"spec":{"structureId":'.$structid.',"title":true},"version":{"signature":0,"version":0}}],"items":{"version":{"signature":-157412296,"version":43401}}}';
		//$jdata = '{"forests":[{"spec":{"type":"clipboard"},"version":{"signature":438013222,"version":0}},{"spec":{"structureId":'.$structid.',"title":true},"version":{"signature":0,"version":0}}],"items":{"version":{"signature":-157412296,"version":44813}}}';
		self::resetcurl();
		$curl = self::getcurl();
		curl_setopt_array($curl, array(
		CURLOPT_POST => 1,
		//CURLOPT_URL => JIRA_URL . '/rest/structure/2.0/value',
		CURLOPT_URL => JIRA_URL . '/rest/structure/2.0/poll?loggedIn=true',
		CURLOPT_POSTFIELDS => $jdata,
		CURLOPT_HTTPHEADER => array('Content-type: application/json')));
		$result = curl_exec($curl);
		$ch_error = curl_error($curl); if ($ch_error) { trace('error',$ch_error); exit(-1);} else { DebugLog($result);}
		$json = json_decode($result);
		//var_dump($result);
		if(isset($json->forestUpdates[1]->error))
		{
			trace("error","Jira structure does not exist");
			exit(-1);
		}
		
		$formula_array = explode(",",$json->forestUpdates[1]->formula);
		$objects = array();
		foreach($formula_array as $formula)
		{
			$detail = explode(":",$formula);
			$obj = new Obj();
			
			$obj->rwoid = $detail[0];
			$obj->level = $detail[1];
			$obj->taskid = $detail[2];
			if(strpos($detail[2], "/")>0)
			{}
			else
			{
				$objects[] = $obj;
			}
		}
		return $objects;
	}

	private static function POST($resource, $jdata) 
	{
		self::resetcurl();
		$curl = self::getcurl();
		curl_setopt_array($curl, array(
		CURLOPT_POST => 1,
		CURLOPT_URL => JIRA_URL . '/rest/api/latest/' . $resource,
		CURLOPT_POSTFIELDS => $jdata,
		CURLOPT_HTTPHEADER => array('Content-type: application/json')));
		$result = curl_exec($curl);
		$ch_error = curl_error($curl); if ($ch_error) { trace('error'."$ch_error");} else { DebugLog($result);}
		return json_decode($result);
	}

	private static function PUT($resource, $json_data) 
	{
		self::resetcurl();
		$curl = self::getcurl();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
		curl_setopt_array($curl, array(
		CURLOPT_URL => JIRA_URL . '/rest/api/latest/' . $resource,
		CURLOPT_HTTPHEADER => array('Content-type: application/json'),
		CURLOPT_RETURNTRANSFER => true));
		
		$result = curl_exec($curl);
		$ch_error = curl_error($curl); if ($ch_error) { trace('error'."$ch_error");} else { DebugLog($result);}
		return json_decode($result);
	}
	
	static function _CreateTask($json_data) 
	{
		return self::POST('issue', $json_data);
	}
	static function _UpdateTask($task_key,$json_data)
	{
		return self::PUT('issue/'.$task_key, $json_data);
	}

	static function CreateTask($project,$summary,$start,$end,$estimateddays,$assignee,$ext_id)
	{

		//$result = '{"id":"151363","key":"IFXUI-43","self":"http://jira.alm.mentorg.com:8080/rest/api/latest/issue/151363"}';
		//return json_decode($result);
		$data = '{ "fields": 
						{ "project": { "key": "'.$project.'" }, 
						"summary": "'.$summary.'", 
						"issuetype": { "name": "Task" },
						"timetracking": { "originalEstimate": "'.$estimateddays.'d"},
						"'.JIRA_SCHEDULED_START.'": "'.$start.'", 
						"customfield_14151": "'.$ext_id.'",
						"'.JIRA_SCHEDULED_END.'": "'.$end.'"';
		//if(strlen($desc)>0)
		//	$data = $data.",".'"description": "'.$desc.'"';
		
		if(strlen($assignee)>0)
			$data = $data.",".'"assignee":{"name":"'.$assignee.'"}';

		$data = $data.'}}';
		
		$result = self::_CreateTask($data);
		
		//$result = '{"id":"151363","key":"IFXUI-43","self":"http://jira.alm.mentorg.com:8080/rest/api/latest/issue/151363"}';
		return $result;
	}

	static function UpdateTask($task_key,$summary,$start,$end,$estimateddays,$assignee,$ext_id)
	{
		$data = '{ "fields": {';
		$delem = "";

		$data = $data.$delem.'"summary": "'.$summary.'"';
		$delem = ",";

		//$data = $data.$delem.'"description": "'.$desc.'"';
		//$delem = ",";

		if(strlen($ext_id)>0)
		{
			$data = $data.$delem.'"customfield_14151": "'.$ext_id.'"';
			$delem = ",";
		}
		if(strlen($start)>0)
		{
			$data = $data.$delem.'"'.JIRA_SCHEDULED_START.'": "'.$start.'"';
			$delem = ",";
		}
		if(strlen($end)>0)
		{
			$data = $data.$delem.'"'.JIRA_SCHEDULED_END.'": "'.$end.'"';
			$delem = ",";
		}
		if(strlen($estimateddays)>0)
		{
			$data = $data.$delem.'"timetracking": { "originalEstimate": "'.$estimateddays.'d"}';
			$delem = ",";
		}
		//if(strlen($assignee)>0)
		//{
			$data = $data.$delem.'"assignee": { "name": "'.$assignee.'"}';
			$delem = ",";
		//}
		$data = $data.'}}';
		//print_r($data);
		$result = self::_UpdateTask($task_key,$data);
		return $result;
	}

	static function  AddLabels($task_key,$labels)
	{
		$labelarray = explode(",",$labels);
		$data = '{ "update": { "labels": [';
		$delim = "";
		foreach($labelarray as $label)
		{
			$data = $data.$delim.'{"add": "'.$label.'"}';
			$delim = ",";
		}
		$data = $data.'] } }';
		self::_UpdateTask($task_key,$data);
		
	}
	static function DeleteLabel($task_key,$label)
	{
		$data = '{ "update": { "labels": [ {"remove": "'.$label.'"} ] } }';
		self::_UpdateTask($task_key,$data);
	}
	private static function ParseJiraData($data,$field)
	{
		//echo "Parsing  ".$field."<br>\n";
		switch($field)
		{
			case 'created':
				$start_date= explode("T", $data['fields']['created'], 2);
				return $start_date[0];
			case START:
				if(isset($data['fields'][START]))
					return $data['fields'][START];
				else 
					return "";//date('Y-m-d');	
			case END:
				if(isset($data['fields'][END]))
					return $data['fields'][END];
				else 
					return "";//date('Y-m-d');	
			case 'aggregateprogress':
				return $data['fields']['aggregateprogress'];
			case 'progress':
				return $data['fields']['progress'];
			case 'timeoriginalestimate':
				return $data['fields']['timeoriginalestimate'];
			case 'description':
				return $data['fields']['description'];
				break;
			case 'key':
				return $data['key'];
				break;
			case 'id':
			    return $data['id'];
				break;
			case 'summary':
				return $data['fields']['summary'];
				break;
			case 'assignee':
				return $data['fields']['assignee']['name'];
				break;
			case 'aggregatetimeoriginalestimate':
				if(isset($data['fields']['aggregatetimeoriginalestimate']))
					return $data['fields']['aggregatetimeoriginalestimate'];
				else
					return 0;
				break;
			case 'updated':
				//echo $data['fields']['updated']."\n";
				$update_date= explode("T", $data['fields']['updated'], 2);
				return $update_date[0];
			case 'components':
				$components = array();
				foreach($data['fields']['components'] as $component)
				{
					$comp = new Object();
					$comp->id = $component['id'];
					$comp->name = $component['name'];
					$components[] = $comp;
				}
				return $components;
				break;
			case 'timespent':
				if(isset($data['fields']['timespent']))
					return $data['fields']['timespent'];
				else
					return 0;
			case 'status':
				return $data['fields']['status']['name'];
				break;
			case 'parent':
				$parent = array();
				$parent["id"] = 0;
				$parent["key"] = 0;
				if(isset($data['fields']['parent']['id']))
				{
					$parent["id"] = $data['fields']['parent']['id'];
					$parent["key"] = $data['fields']['parent']['key'];
				}
				return $parent;
				break;
			case 'issuetype':
				return $data['fields']['issuetype']['name'];
			case EPIC_NAME:
				if(isset($data['fields'][EPIC_NAME]))
					return $data['fields'][EPIC_NAME];
				else 
					return null;
				break;
			case STORY_POINTS:
				if(isset($data['fields'][STORY_POINTS]))
					return $data['fields'][STORY_POINTS];
				else 
					return 0;
				break;
			case EXT_ID:
				if(isset($data['fields'][EXT_ID]))
					return $data['fields'][EXT_ID];
				else 
					return 0;
				break;
			case 'issuelinks':
				$issuelinks = array();
				foreach($data['fields']['issuelinks'] as $issuelink)
				{
					$issuelinks[] =  $issuelink['inwardIssue']['key'];
				}
				return $issuelinks;
			case 'fixVersion':
			    if(isset($data['fields']['fixVersion']))
					return $data['fields']['fixVersion'];
				else 
					return null;
				break;
			case 'labels':
				$labels = array();
				foreach($data['fields']['labels'] as $label)
				{
					$labels[] = $label;
				}
				return $labels;
				break;
			case 'attachment':
				if(isset($data['fields']['attachment']))
					return $data['fields']['attachment'];
				else 
					return null;
				break;
			default:
				trace(ERROR,"Unhandled field ".$field);
				return "";
			
		}
	}
	static function GetAttachementData($task)
	{
		$attachements = array();
		for($i=0;$i<count($task[attachment]);$i++)
		{	
			if(isset($task[attachment][$i]['filename']))
			{
				$attachements[$task[attachment][$i]['filename']] = $task[attachment][$i]['id'];		
			}
		}
		return $attachements;
	}
	static function GetAttachment($task_key)
	{
		$attachements = array();
		$query = "key=".$task_key;
		//echo $query;
		$tasks = self::Search($query,1,array(attachment)); 
		
		//print_r($tasks);
		for($i=0;$i<count($tasks[0][attachment]);$i++)
		{	
			if(isset($tasks[0][attachment][$i]['filename']))
			{
				$attachements[$tasks[0][attachment][$i]['filename']] = $tasks[0][attachment][$i]['id'];		
			}
		}
		return $attachements;
	}
	static function DeleteAttachment($task_key,$filename)
	{
		$query = "key=".$task_key;
		//echo $query;
		$tasks = self::Search($query,1,"attachment"); 
		
		//print_r($tasks);
		for($i=0;$i<count($tasks[0][attachment]);$i++)
		{	
			if(isset($tasks[0][attachment][$i]['filename']))
			{
				if( strcmp($tasks[0][attachment][$i]['filename'],$filename)==0)
				{
					echo "Deleting old attachement ".$tasks[0][attachment][$i]['filename']."\n";
					self::RestApi("DELETE","attachment/".$tasks[0][attachment][$i]['id']);
				}
			}
		}	
		//$this->PUTFile($task_key,$filepath);
	}
	static function UploadAttachment($task_key,$filepath)
	{
		self::PUTFile($task_key,$filepath);
	}
	static function DownloadAttachment($id,$filename)
	{
		//http://jira.alm.mentorg.com:8080/secure/attachment/99524/in.gan
	
		$curl = self::getcurl();
		self::resetcurl();
		$url = JIRA_URL."/secure/attachment/".$id."/".$filename;
		//echo $url;
		curl_setopt($curl, CURLOPT_URL,$url);
		//echo $url;
		$result = curl_exec($curl);
		$ch_error = curl_error($curl); if ($ch_error) { echo "CURL Error: $ch_error";} else { DebugLog($result);}
		return $result;
	}
	
	static function Search($query,$maxresults,$fields) 
	{
		global $fieldmap;
		//global $FIELD_RENAMES;
		$fields_array = explode(',', $fields);

		for($i=0;$i<count($fields_array);$i++)
		{
			foreach($fieldmap as $key=>$value)
			{
				if($fields_array[$i] == $value)
					$fields_array[$i] = $key;
			}
		}
		
		$fields = $str = implode (",", $fields_array);
		$curl = self::getcurl();
		
		$tasks =  array();
		$query = str_replace(" ","%20",$query);

		$resource="search?jql=".$query.'&maxResults='.$maxresults.'&fields='.$fields;
	
		$issues = self::GET($resource);

	
		if(isset($issues['issues']))
		{
			$fields_names =  explode(",",$fields);
			foreach ($issues['issues'] as $entry) 
			{
				$task = array();
				foreach($fields_names as $field)
				{	
					$data = self::ParseJiraData($entry,$field);
					$task[$field]= $data;
					foreach($fieldmap as $key=>$value)
					{
						if($field == $key)
							$task[$value] = $data;
					}
				}
				$tasks[] = $task;
			}
		}
		return $tasks;
	}
	private static function ConvertTimeSpentSimple($timespent)
	{
		$count = (int)$timespent;
		$type = preg_replace('/\d+/', '', $timespent );
		if($type == 'd')
			return $count;
		if($type == 'h')
			return $count/8;
		if($type == 'm')
			return $count/480;
		if($type == 'w')
			return $count*5;
		else
		{
			//echo $timespent." ".$count." wrong type of time coversion";
			return 0;
		}	
	}
	private static  function ConvertJiraTime($timespent)//converts to days
	{
		$acc = 0;
		$timespent_array = explode(" ",$timespent);
		foreach ($timespent_array as $i)
			$acc = $acc + self::ConvertTimeSpentSimple($i);
		return $acc;
	}
	static function GetHistory($key)
	{
		$history_logs =  array();
		$curl = self::getcurl();
	    $history = self::Get("issue/".$key."?expand=changelog");
		//print_r($history);
		foreach ($history['changelog']['histories'] as $key=>$value) 
		{
			echo $value['created']."    "."<br>";
			foreach($value['items'] as $k=>&$itm)
				$itm['created'] = $value['created'];
			$history_logs[] = $value['items'];
			//foreach($value['items'] as $key2=>$value2)
			//{
			//	echo $key2."----";
			//	print_r($value2);
			//	echo "<br>";
			//}
			//echo $key."----";
			//print_r($value);
			//echo "<br>"."****************************8\n"."<br>";
		}
		return $history_logs;
	}
	static function GetOrigEstimateHistory($key)
	{
		$history  = jira::GetHistory($key);
		foreach($history as $key=>$shistory)
		{
			
			//echo "<br>*********************".$shistory['created']."<br>";
			foreach($shistory as $key2=>$log)
			{
				//$log['created'] = $shistory['created'];
				//if($log['field'] == 'timeoriginalestimate')
				{
					echo $key2."   ";
					print_r($log);
					echo "<br>";
				}
			}

		}
	}
	static function GetWorkLog($key)
	{
		//echo func_get_args()[func_num_args()-1];

		
		$curl = self::getcurl();
		$worklogs = self::GET("issue/$key/worklog");
		//$url=JIRA_SERVER."/rest/api/latest/issue/$key/worklog";
		#echo $url;
		//curl_setopt($curl, CURLOPT_URL,$url);
		//$out = curl_exec($curl);
		//$worklogs = json_decode($out, true);
		$worklog_array = array();
		#echo $key.EOL;
	
    	foreach ($worklogs['worklogs'] as $log) 
		{
		
			$worklog = new Obj();	
			$ts = self::ConvertJiraTime($log['timeSpent']);
			if($ts == 0)
			{
				echo "Some work log of task ".$key." is wrong\n";
			}
			$worklog->id = $log['id'];
			$start_date= explode("T", $log['started'], 2);
			$worklog->started = $start_date[0];
			$worklog->time = $start_date[1];
			$worklog->timespent = self::ConvertJiraTime($log['timeSpent']);
			$worklog->timespent_seconds = $log['timeSpentSeconds'];
			$worklog->displayname = $log['author']['displayName'];
			
			$worklog->comment = $log['comment'];
			$worklog->author = $log['author']['name'];
			$worklog_array[] = $worklog;
			
			DebugLog($worklog->started." ".$worklog->timespent." ".$worklog->comment);
    	}
		return $worklog_array;
	}
}
?>