<?php
require_once 'include/scout_globals.php';
require_once 'login_include.php';

ob_start();
$pt = new ScoutTemplate();
$pt->addScript('scripts/md5.js');
$pt->setPageTitle( SITE_TITLE . ' - Login' );
$pt->writeBanner();
$pt->writeMenu();

if (isset($_POST['target']))
{
	if ($_POST['target'] == 'forgot_password')
	{
		$email = $_POST['email'];
		$site_title = SITE_TITLE;
		$additional_message = "  http://boyscoutwebsite.com/login.php?action=change_password&email=".$_POST['email'];
		$from_address = SITE_ADMIN_EMAIL;
		$from_name = SITE_ADMIN_NAME;
		HandleForgotPassword($email, $site_title, $additional_message, $from_address, $from_name);
		$pt->writeFooter();
		exit;
	}
	else if ($_POST['target'] == 'change_password')
	{
		$site_title = SITE_TITLE;
		$pt = new ScoutTemplate();
		HandleChangePassword($_SESSION['USER_ID'], $message, $_POST['email'], $_POST['pass_hash'], $site_title, $_POST['curr_pass_hash'], $pt);
	}
	else if ($_POST['target'] == 'new_login')
	{
//		$recipient = 'salisbm@hotmail.com, randy@reederhome.net';
    $recipient = SITE_ADMIN_EMAIL;
		$from_address = SITE_ADMIN_EMAIL;
		$from_name = SITE_ADMIN_NAME;
		$send_mail = true;
		HandleNewLogin($_POST['name'], $_POST['email'], $_POST['pass_hash'], $message, SITE_TITLE, $pt, $send_mail, $recipient, $from_address, $from_name, $_POST['troop_id'], $_POST['new_troop_number'], $_POST['council_id']);
	}
	else if ($_POST['target'] == 'login')
	{
		//(isset($_SESSION['login_redirect']) ? $_SESSION['login_redirect'] : '')
		$redirect = 'requirements.php';
		if (!authenticate($_SESSION['USER_ID'], $_POST['email'], $_POST['pass_hash'], isset($_POST['remember_password']), $error_message, $do_redirect = true, $redirect))
		{
			//echo '<br><div style="font-size: larger"></div>';
			echo '<script type="text/javascript">';
			echo "alert('$error_message');";
			echo '</script>';
		}
		//$pt->writeFooter();
		//exit;
	}
}

$action = null;
if (isset($_GET['action']))
	$action = $_GET['action'];

ShowLoginPage($action);


$pt->writeFooter();
?>