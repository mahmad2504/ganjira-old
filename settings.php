<?php 
define('JIRA_URL', 'http://jira.alm.mentorg.com:8080');
define('JIRA_UPDATE_ALLOWED','false');
define('FILTER_NAME','dmr');
define('QUERY','(project=BSP or project=OS) and labels=DMR');
define('USERS_TIMESHEET','');
define('USERS_WEEKLY_REPORT','');
define('JIRA_STRUCTURE',620);
define('GAN_FILE',FILTER_NAME.".gan");
// Create Project structure from
define('PROJECT_LAYOUT',JIRA_STRUCTURE);
//define('PROJECT_LAYOUT',GAN_FILE);
?>
