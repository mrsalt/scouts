<?php
require_once 'include/scout_globals.php';
require_once 'scout_requirements_include.php';

if ($_GET['become'])
{
	echo 'changed user IDs<br>';
	$_SESSION['USER_ID'] = $_GET['become'];
}

$pt = new ScoutTemplate();
$pt->setPageTitle( SITE_TITLE );
$pt->writeBanner();
$pt->writeMenu();
connect_db('scouts');

function sort_files(&$a, &$b)
{
	return $b['filemtime'] - $a['filemtime'];	
}

function get_directory_info(&$info, $file_name_match = null, $path = null)
{
	$files = scandir($path ? $path : '.');
	foreach ($files as $filename)
	{
		if ($filename == '.' or $filename == '..')
			continue;
		$rel_name = ($path ? $path.'/' : '').$filename;

		if (is_dir($rel_name))
			get_directory_info($info, $file_name_match, $rel_name);
		else if (is_file($rel_name) and (!$file_name_match or preg_match($file_name_match, $filename)))
			$info[$rel_name] = Array('filemtime' => filemtime($rel_name),
			                                   'filename' => $filename,
			                                   'directory' => $path);
	}
}

function pretty_print_file_list($title, $list, $time_format = 'M j, Y h:i A')
{
	if ($title)
		$text .= '<tr><td colspan="2">'.$title.'</td></tr>'."\n";
	if (is_array($list))
	{
		foreach ($list as $file_name => $data)
			$text .= '<tr><td style="padding-left: 25px;">'.$file_name.'</td><td>'.local_date($time_format,$data['filemtime']).'</td></tr>';
	}
	return $text;
}

//SELECT count( user_id ) AS count, req_id, description
//FROM user_req, requirement
//WHERE req_id = requirement.id
//AND signed_by !=0
//GROUP BY req_id, description
//ORDER BY count DESC 

function CascadeEverything()
{
	$sql = 'SELECT * FROM user_req, requirement WHERE signed_by != 0 AND user_req.req_id = requirement.id';
	//SELECT count(user_req.user_id) as c, award.title FROM user_req, requirement, award WHERE signed_by != 0 AND user_req.req_id = requirement.id AND requirement.award_id = award.id group by award.title
	//SELECT * FROM user_req, requirement, award WHERE award.title = 'Textile' AND signed_by != 0 AND user_req.req_id = requirement.id AND requirement.award_id = award.id
	$data = fetch_array_data($sql);
	//pre_print_r($data);

	foreach ($data as $req_data)
	{	
		if ($req_data['parent_id'])
		{
			CascadeRequirementSignOff($req_data['parent_id'], $req_data['user_id'], $req_data['signed_by'], $error_msg, true);
		}
	
		// need to check to see if the award is passed off now...	
		CascadeAwardSignOff($req_data['award_id'], $req_data['user_id'], $req_data['signed_by'], $req_data['signed_date'], $error_msg);
	}
	pre_print_r('Error Messages:');
	pre_print_r($error_msg);
}
	


//'/.*(\.php|\.js|\.css)/'
get_directory_info($file_list);
//pre_print_r($file_list);

uasort($file_list, 'sort_files');

foreach ($file_list as $file_name => $data)
{
	if (time() - $data['filemtime'] < 24 * 3600)
		$last_day[$file_name] = $data;
	else if (time() - $data['filemtime'] < 7 * 24 * 3600)
		$last_week[$file_name] = $data;
}

$text = '<table cellpadding=4>';
$text .= pretty_print_file_list('Files changed in the last day:', $last_day);
$text .= pretty_print_file_list('Files changed in the last week:', $last_week, 'l, M j, Y h:i A');
$text .= '</table>';

echo $text;

pre_print_r('<a href="todo.php?do=cascade_everything">Update All Requirements</a>');
if ($_GET['do'] == 'cascade_everything')
	CascadeEverything();

/*
if ($_GET['do'] == 'delete_log')
	unlink('logs/default.log');
pre_print_r('logs/default.log:'.(file_exists('logs/default.log') ? '  <a href="todo.php?do=delete_log">Delete Log</a>':'').'  <a href="todo.php">Refresh Page</a>');
pre_print_r(file_exists('logs/default.log') ? file_get_contents('logs/default.log') : 'No such file');
*/

$pt->writeFooter();
?>