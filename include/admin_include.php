<?php

require_once 'include/scout_globals.php';
require_once 'scout_requirements_include.php';

if ($site_host_name == 'qwest_host_2')
	define('LOCAL_ROOT_FOLDER', 'D:\\scouts\\');
else
	define('LOCAL_ROOT_FOLDER', $_SERVER['DOCUMENT_ROOT'].'/');

/*
function svn_get_files()
{
	$cmd = 'svn status '.LOCAL_ROOT_FOLDER;
	$result = shell_exec ( $cmd );
	$files = explode("\n", $result);
	$svn_files = Array();
	foreach ($files as $file)
	{
		if (strlen(trim($file)) == 0)
			continue;
		$filename = substr($file, 8);
		$extra_status = substr($file,1,7);
		if ($extra_status != '       ')
			pre_print_r("Warning, $filename SVN status is not regular (maybe file is locked or conflicted?): $extra_status");
		
		if ($file[0] == '?')
			$status = 'Unversioned';
		else if ($file[0] == 'A')
			$status = 'Added';
		else if ($file[0] == 'M')
			$status = 'Modified';
		else if ($file[0] == 'D')
			$status = 'Deleted';
		else
			die("Error, status on $filename is '$file[0]' which is unexpected");
		if ($status == 'Unversioned')
			$svn_files['Unversioned'][] = $filename;
		else
			$svn_files['Versioned'][$status][] = $filename;
	}
	return $svn_files;
}
*/

function md5_get_files()
{
	$production_file_list = unserialize(file_get_contents('http://boyscoutwebsite.com/todo.php?action=get_file_modified_list'));
	
	get_directory_info($dev_file_list, LOCAL_ROOT_FOLDER, $file_name_match = '/^.*(?<!\.svn)(\.php|\.js|\.css)$/', $get_md5_signature = true);
	
	$files = Array();
	foreach ($dev_file_list as $dev_file_key => $dev_file_details)
	{
		if (array_key_exists($dev_file_key, $production_file_list))
		{
			if ($dev_file_details['md5'] != $production_file_list[$dev_file_key]['md5'])
			{
				$files['Modified'][] = $dev_file_key;
			}
		}
		else
		{
			$files['Added'][] = $dev_file_key;
		}
	}
	
	foreach ($production_file_list as $file_key => $file_details)
	{
		if (!array_key_exists($file_key, $dev_file_list))
		{
			$files['Deleted'][] = $file_key;
		}
	}
	return $files;
}

function handle_ftp()
{
	/*$cmd = 'svn commit --message "'.$_POST['commit_msg'].'" --username '.$_POST['password'].' --password '.$_POST['password'].' '.LOCAL_ROOT_FOLDER;
	pre_print_r($cmd);
	$result = shell_exec ( $cmd );
	pre_print_r($result);
	*/
	
	foreach ($_POST['file'] as $type => $list)
	{
		//echo 'change type='.$type.'<br/>';
		foreach ($list as $key => $value)
		{
			//echo $key.' => '.$_POST['filename'][$key].'<br/>';
			if ($type == 'Added' or $type == 'Modified')
				$result = ftp_transfer_file($_POST['filename'][$key]);
			else if ($type == 'Deleted')
				$result = ftp_delete_file($_POST['filename'][$key]);
			if (!$result)
				pre_print_r('FTP operation failed for '.$_POST['filename'][$key]);
		}
	}
	ftp_close_connection();
}

function ftp_make_connection()
{
	if (!$GLOBALS['ftp_connection_id'])
	{
		$ftp_server = 'reederhome.net';
		$ftp_user_name = 'u37470848-mark';
		$ftp_user_pass = 'salisbury';
		$GLOBALS['ftp_connection_id'] = ftp_connect($ftp_server); 

		// login with username and password
		$login_result = ftp_login($GLOBALS['ftp_connection_id'], $ftp_user_name, $ftp_user_pass); 

		// check connection
		if ((!$GLOBALS['ftp_connection_id']) || (!$login_result)) { 
			echo "FTP connection has failed!";
			echo "Attempted to connect to $ftp_server for user $ftp_user_name"; 
			unset($GLOBALS['ftp_connection_id']);
			exit; 
		} else {
			echo "Connected to $ftp_server, for user $ftp_user_name";
		}
		ftp_pasv ( $GLOBALS['ftp_connection_id'] , true );
	}
}

function ftp_transfer_file($file)
{
	$source_file = $file;
	if (!file_exists($file))
		$source_file = LOCAL_ROOT_FOLDER . $file;
	if (!file_exists($source_file))
		die('Unable to FTP file '.$source_file);
		
	$destination_file = str_replace('\\','/', str_replace(LOCAL_ROOT_FOLDER, '', $file));
	
	ftp_make_connection();
	
	// upload the file
	pre_print_r("FTP put of $source_file to $destination_file");
	return ftp_put($GLOBALS['ftp_connection_id'], $destination_file, $source_file, FTP_BINARY);
}

function ftp_delete_file($file)
{
	$source_file = $file;
	if (!file_exists($file))
		$source_file = LOCAL_ROOT_FOLDER . $file;
	$destination_file = str_replace('\\','/', str_replace(LOCAL_ROOT_FOLDER, '', $file));
	
	ftp_make_connection();
	
	pre_print_r("FTP delete $destination_file");
	return ftp_delete($GLOBALS['ftp_connection_id'], $destination_file);
}

function ftp_close_connection()
{
	if ($GLOBALS['ftp_connection_id'])
	{
		// close the FTP stream 
		ftp_close($GLOBALS['ftp_connection_id']);
		unset($GLOBALS['ftp_connection_id']);
	}
}

function get_modified_files_update_form()
{
	//$files = svn_get_files();
	$files = md5_get_files();
	$text = '<form method="POST">';
	/*$text .= 'Commit Message:<br/>';
	$text .= '<textarea name="commit_msg" rows=8 cols=65></textarea><br/>';
	$text .= 'SVN username <input type="text" name="username" /><br/>';
	$text .= 'SVN password <input type="password" name="password" /><br/>';*/
	$text .= '<table>';
	$count = 0;
	//foreach ($files['Versioned'] as $type => $list)
	foreach ($files as $type => $list)
	{
		$text .= "<tr><td colspan=2 style=\"font-weight: bold\">$type</td></tr>\n";
		foreach ($list as $file)
		{
			$text .= '<tr><td style="padding-left: 20px;"><input type="checkbox" checked name="file['.$type.']['.$count.']"></td>';
			$text .= '<td>'.$file.'<input type="hidden" name="filename['.$count.']" value="'.$file.'"/></td></tr>'."\n";
			$count++;
		}
	}
	$text .= '<tr><td colspan=2><input type="submit" value="FTP Modified Files"/></td></tr>';
	$text .= '</table></form>';
	
	/*
	$text .= 'Unversioned files:<div style="padding-left: 20px;">';
	foreach ($files['Unversioned'] as $file)
	{
		$text .= $file."<br/>\n";
	}
	$text .= '</div>';*/
	return $text;
}

function sort_files(&$a, &$b)
{
	return $b['filemtime'] - $a['filemtime'];	
}

function get_directory_info(&$info, $root, $file_name_match = null, $get_md5_signature = false, $path = null)
{
	$files = scandir($root . $path);
	foreach ($files as $filename)
	{
		if ($filename == '.' or $filename == '..')
			continue;
		$rel_name = ($path ? $path.'/' : '').$filename;

		if (is_dir($root.$rel_name))
		{
			get_directory_info($info, $root, $file_name_match, $get_md5_signature, $rel_name);
		}
		else if (is_file($root.$rel_name) and (!$file_name_match or preg_match($file_name_match, $filename)))
		{
			$info[$rel_name] = Array('filemtime' => filemtime($root.$rel_name),
			                         'filename' => $filename,
			                         'directory' => $path);
			if ($get_md5_signature)
			{
				$info[$rel_name]['md5'] = md5_file($rel_name);
			}
		}
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
	

function show_recently_modified_files()
{
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
}

?>