<?php
require_once 'include/admin_include.php';

if ($_GET['action'] == 'get_file_modified_list')
{
	get_directory_info($info, LOCAL_ROOT_FOLDER, $file_name_match = '/^.*(?<!\.svn)(\.php|\.js|\.css)$/', $get_md5_signature = true);
	echo serialize($info);
	exit;
}

if (!isAdminUser($_SESSION['USER_ID']))
{
	echo 'Access denied.';
	exit;
}

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

if (array_key_exists('file', $_POST))
{
	handle_ftp();
}

if ($site_host_name == 'qwest_host_2')
{
	echo get_modified_files_update_form();	
}
else
{
	show_recently_modified_files();
}

/*
pre_print_r('<a href="todo.php?do=cascade_everything">Update All Requirements</a>');
if ($_GET['do'] == 'cascade_everything')
	CascadeEverything();
*/
/*
if ($_GET['do'] == 'delete_log')
	unlink('logs/default.log');
pre_print_r('logs/default.log:'.(file_exists('logs/default.log') ? '  <a href="todo.php?do=delete_log">Delete Log</a>':'').'  <a href="todo.php">Refresh Page</a>');
pre_print_r(file_exists('logs/default.log') ? file_get_contents('logs/default.log') : 'No such file');
*/

$pt->writeFooter();
?>