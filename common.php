<?php
require_once('settings.php');
require_once('jira.php');
require_once('structure.php');
require_once('filter.php');
require_once('project.php');
require_once('gan.php');
require_once('jsgantt.php');
//ERRORS
define('ERROR','error');
define('WARN','warn');
define("WEBLINK",JIRA_URL.'/browse/');

date_default_timezone_set('Asia/Karachi');

class Obj{
}

function DebugLog($log)
{
	$traces = debug_backtrace();
	foreach($traces as $trace)
	{
		if($trace['args'][count($trace['args'])-1]=="debug")
		{
			echo debug_backtrace()[1]['function'];
			if(is_array($log))
				print_r($log);
			else
				echo "::".$log."\n";
		}
	}
}

function trace($type,$log)
{
	
	if($type == ERROR)
	{
		if(isset(debug_backtrace()[1]['class']))
			echo "ERROR::".debug_backtrace()[1]['class']."::".debug_backtrace()[1]['function']."::".$log."\n";
		else
			echo "ERROR::"."::".$log."\n";
		exit(-1);

	}
	if($type == WARN)
	{
		echo "WARN::".debug_backtrace()[1]['class']."::".debug_backtrace()[1]['function']."::".$log."\n";
	}
	
}
$date="";
if( isset($argv))
{
	foreach ( $argv as $value)
	{
		$env="cmd";
		define("EOL","\r\n"); 
		$params=explode("=",$value);
		if(count($params)==2)
			${$params[0]} = $params[1];
	}
}
else
{
	$env="web";
	define("EOL","<br>");
	$url = parse_url($_SERVER["REQUEST_URI"]);
	//var_dump($url);
	$cmd = basename($url['path']);
	//echo $url['query'];
	if(isset($url['query']))
	{
		$params=explode("=",$url['query']);
		for($i=0;$i<count($params);$i=$i+2)
		{
			//echo $key." ".$value."<br>";
			$$params[$i]=$params[$i+1];
		}
	}
	
}

function flushout()
{
	global $env;
	if($env=="web")
     {
      	flush();
      	ob_flush();
     }
	
}
?>
