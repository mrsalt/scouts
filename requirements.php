<?php
require_once 'include/scout_globals.php';
require_once 'scout_requirements_include.php';
require_once 'report_functions.php';
require_once 'user_note_include.php';

//write_log('requirements.php.  url='.$_SERVER["REQUEST_URI"]);

$error_msg = '';
if (isset($_POST['target']))
{
	HandlePost($error_msg);
}
if (isset($_GET['action']))
{
	if ($_GET['action'] == 'make_optional')
	{
		$sql = 'UPDATE requirement SET required = \'F\' WHERE id IN ('.$_GET['reqs'].')';
		execute_query($sql,'scouts');
	}
	else if ($_GET['action'] == 'sign_requirements' or $_GET['action'] == 'erase_requirements')
	{
		$reqs = explode(',',$_GET['reqs']);
		$users = explode(',',$_GET['users']);
		//write_log('requirements.php.  action = '.$_GET['action'].', reqs='.$_GET['reqs'].', usesr='.$_GET['users']);
		foreach ($reqs as $req_id)
		{
			foreach ($users as $user_id)
			{
				if ($_GET['action'] == 'sign_requirements')
					$result = SignOffRequirement($req_id, $user_id, $_GET['sign_date'], $_GET['signed_by'], $error_msg);
				else if ($_GET['action'] == 'erase_requirements')
					$result = EraseRequirement($req_id, $user_id, $_GET['signed_by'], $error_msg);
				//write_log('requirements.php.  action = '.$_GET['action'].', result='.($result ? 'true' : 'false').', error_msg='.$error_msg);
			}
		}
		if ($error_msg)
		{
			die($error_msg);
		}
		else
		{
			$_SESSION['last_sign_date'] = $_GET['sign_date'];
			//pre_print_r($_GET);
			//write_log($_GET,'requirements_debug.txt');
			header('HTTP/1.1 204 No Content');
			exit;
		}
	}
	else if($_GET['action'] == 'sign_report')
	{
		$signoff_values = $_REQUEST['signoff_values'];
		foreach ($signoff_values as $user_id => $user_reqs)
		{
			foreach ($user_reqs as $req_id => $value)
			{
				if($value['signed_by'])
				{
					$result = SignOffRequirement($req_id, $user_id, $value['signed_date'], $value['signed_by'], $error_msg);
				}
				else
				{
					$result = EraseRequirement($req_id, $user_id, $_SESSION['USER_ID'], &$error_msg);
				}
			}
		}
		header('Content-type: text/xml');
		if($error_msg)
		{
			echo '<?xml version="1.0" ?><xmlresponse><result>failure</result><reason>'.htmlspecialchars($error_msg).'</reason></xmlresponse>';
			exit;
		}
		echo '<?xml version="1.0" ?><xmlresponse><result>success</result></xmlresponse>';
		exit;
	}
	else if ($_GET['action'] == 'update_my_notes')
	{
		if ($_POST['user_id'] != $_SESSION['USER_ID'])
		{
			echo 'You are no longer logged in.<br/>';
			exit;
		}
		if ($_POST['target'] == 'save')
			update_user_note($_POST['table'], $_POST['record_id'], $_POST['user_id'], $_POST['my_notes']);
		else if ($_POST['target'] == 'delete')
			delete_user_note($_POST['table'], $_POST['record_id'], $_POST['user_id']);
	}
	else if ($_GET['action'] == 'delete_note')
	{
		if (!$_SESSION['USER_ID'])
		{
			echo 'You are no longer logged in.<br/>';
			exit;		
		}
		// potential security -- check that user_id is someone in my troop
		delete_user_note($_POST['table'], $_POST['record_id'], $_POST['user_id']);
	}
}

$is_scoutmaster = isUser('Scoutmaster');
$is_scout = isUser('Scout');

if (isset($_GET['req_view']))
	$_SESSION['req_view'] = $_GET['req_view'];
else if (!isset($_SESSION['req_view']))
	$_SESSION['req_view'] = 'Rank Advancement';
if (isset($_GET['show_palms']))
	$_SESSION['show_palms'] = ($_GET['show_palms'] == 'true');

if(isset($_GET['scout_id']))
{ 
	$_SESSION['scout_id'] = $_GET['scout_id'];
	if ($_SESSION['req_view'] == 'Rank Advancement' and $_GET['scout_id'] and count(explode(',',$_GET['scout_id'])) == 1)
	{
		$rank = do_query('SELECT rank FROM user WHERE id IN ('.$_GET['scout_id'].')');	// scout_id should just be a single ID (see count() statement above)
		$_GET['award_id'] = get_next_rank_id($rank);
	}
}

//pre_print_r($_SESSION);
ini_set('session.bug_compat_42','false');

if (isset($_GET['award_id']) && (ctype_digit($_GET['award_id']) || $_GET['award_id'] == 'all')) // ?? Randy did you add "&& ctype_digit($_GET['award_id'])"?  It is causing the merit badge report not to come up when clicking 'show all' at the top...
	$_SESSION['award_id_'.$_SESSION['req_view']] = $_GET['award_id'];                       // Yes, I did add it.  I just put it back in (4/29/2009), but added the additional OR to allow award_id to be set to 'all' or a digit.
$award_id = null;
if (isset($_SESSION['award_id_'.$_SESSION['req_view']]))
	$award_id = $_SESSION['award_id_'.$_SESSION['req_view']];
else if ($award_id === null)
{
	$award_id = 'all';
}

if (isset($_GET['group_id']))
{
	$_SESSION['group_id'] = $_GET['group_id'];
	
	$sql = 'SELECT DISTINCT user.id '.
		       'FROM user, user_group '.
		       'WHERE scout_troop_id = '.$GLOBALS['troop_id'].
		       ' AND state != \'blocked\' '.
		       ' AND user.id = user_group.user_id AND user_group.group_id IN ('.$_SESSION['group_id'].')'.
		       ' AND _scout = \'T\'';
	$scouts = fetch_array_data($sql,'scouts');
	if (is_array($scouts) and count($scouts))
		$_SESSION['scout_id'] = implode(',',array_field($scouts,'id'));
	else
		$_SESSION['scout_id'] = Array();
}
else if (!$_SESSION['group_id'] and $_SESSION['USER_ID'])
{
	$_SESSION['group_id'] =  do_query('select group_id from user_group where user_id = '.$_SESSION['USER_ID'],'scouts');
}

$pt = new ScoutTemplate();
$pt->setPageTitle( SITE_TITLE );
if($_SESSION['req_view'] == 'troop_report_summary' or $_SESSION['req_view'] == 'individual_report_summary')
{
	$pt->addStyleSheet('css/report-summary.css');
}
else
{
	$pt->addStyleSheet('css/report.css');
}
$pt->writeBanner();
if(!$_GET['printpage'])
{
	$pt->writeMenu();
}
if ($error_msg)
	echo '<h3 style="color: red">'.$error_msg.'</h3	>';

echo '<table cellpadding=10 border=0 style="width:100%;"><tr>';
if(!$_GET['printpage'])
{
	echo '<td style="width: 240px;" valign="top">';
	ShowRequirementsNavigationBar($can_add_awards = $is_scoutmaster,
	                              $can_edit_progress = $is_scoutmaster,
	                              $is_scout,
	                              $award_id,
	                              $_SESSION['req_view'],
	                              $GLOBALS['troop_id'],
	                              $_SESSION['group_id'],
	                              $_SESSION['scout_id'],
	                              $_GET['show'],
	                              $_SESSION['show_palms']);
	echo '</td>';
}
echo '<td valign="top">';

if (isset($_GET['do']))
{
	if ($_GET['do'] == 'create_new_award')
	{
		ShowCreateAward($_GET['type']);
	}
}
else if ($award_id or $_SESSION['req_view'] == 'troop_report_summary' or $_SESSION['req_view'] == 'individual_report_summary')
{
	if ($_SESSION['req_view'] == 'Rank Advancement' or $_SESSION['req_view'] == 'Merit Badge')
	{
		if ($_SESSION['scout_id'])
			$user_list = explode(',',$_SESSION['scout_id']);
		else
			$user_list = Array();

		//UpdateRequirementNumbers($award_id);
		//debug('award_id='.$award_id);
		if ($award_id == 'all')
		{
			if (count($user_list) == 1)
			{
				if ($_SESSION['req_view'] == 'Rank Advancement')
				{
					// show the progress of a single boy towards ALL rank advancements
					echo get_report(0, $_SESSION['scout_id'], 0, 'summary');
				}
				else
				{
					echo get_merit_badge_report($_SESSION['scout_id'], $group_id = 0);
				}
			}
			else if (count($user_list) > 1)
			{
				if ($_SESSION['req_view'] == 'Rank Advancement')
				{
					// show the progress of a group of boys towards ALL rank advancements
					echo get_report(0, 0, $_SESSION['group_id'], 'summary');
				}
				else
				{
					echo get_merit_badge_report($user_id = 0, $_SESSION['group_id']);
				}
			}
		}
		else
		{
			if ($_GET['show']=='report')
			{
				if (count($user_list) == 1)
				{
					// show the progress of a single towards a specific rank advancement or merit badge
					echo get_report($_SESSION['award_id_'.$_SESSION['req_view']], $_SESSION['scout_id']);
				}
				else if (count($user_list) > 1)
				{
					// show the progress of a group of boys towards a specific rank advancement or merit badge
					echo get_report($_SESSION['award_id_'.$_SESSION['req_view']], 0, $_SESSION['group_id']);
				}
			}
			else
			{
				$show_edit_requirement = ($error_msg and isset($_POST['target']) and $_POST['target'] == 'edit_requirement');			
				if (isset($_GET['revision_year']))
					$award_year = $_GET['revision_year'];
				else
					$award_year = null;
				ShowAwardRequirements($award_id, 
				                      $can_edit = $is_scoutmaster, 
				                      false, //$can_add_reqs = $is_scoutmaster, 
				                      false, //$show_edit_requirement and $is_scoutmaster, // this only applies if can_add_reqs is true
				                      $_SESSION['USER_ID'],
				                      $user_list,
				                      $award_year);
			}
		}
	}
	
	if ($_SESSION['USER_ID'] and $award_id != 'all')
	{		
		echo '</td></tr>';
		echo '<tr><td>&nbsp;</td><td>';
		echo '<div class="scout_report">';
		
		echo get_my_notes($_SESSION['USER_ID'], 'requirements.php', 'award', $award_id);
		
		if ($is_scoutmaster)
		{
			$user_notes_user_ids = array_field(fetch_array_data('SELECT id FROM user WHERE scout_troop_id = '.$GLOBALS['troop_id']), 'id');
			echo get_others_notes($user_notes_user_ids, $_SESSION['USER_ID'], 'requirements.php', 'award', $award_id);
		}
		echo '</div>';
	}
}
else
{
	echo '<div style="margin-left: 30px;"><h2>No award is selected.<br><br>To view requirements for an award, select the award from the menu on the left.</h2></div>';
}
echo '</td></tr></table>';

$pt->writeFooter();
?>