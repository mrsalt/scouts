<?php

require_once 'include/scout_globals.php';
require_once 'scout_requirements_include.php';
require_once 'report_functions.php';

$error_msg = '';

$is_scoutmaster = isUser('Scoutmaster');
$is_scout = isUser('Scout');
$allow_updates = false;

if (is_array($_POST) and count($_POST) > 0)
{
	if ($is_scoutmaster)
	{
		//pre_print_r($_POST);
		foreach ($_POST as $key => $val)
		{
			if (preg_match("/state_(\d+)_(\d+)/",$key,$matches))
			{
				$date_val = strtotime($_POST['presented_'.$matches[1].'_'.$matches[2]]);
				if ($date_val)
					$date_str = date('Y-m-d',$date_val);
				else
					$date_str = '';
				$sql = 'UPDATE user_award SET state = \''.$val.'\', presented_date = \''.$date_str.'\' WHERE award_id = '.$matches[1].' AND user_id = '.$matches[2];
				//pre_print_r($sql);
				execute_query($sql);
			}
		}
	}
	else
	{
		$error_msg = 'Error.  Your session has timed out.  Please login again.';
	}
}


if (isset($_GET['action']))
{
	if ($is_scoutmaster and $_GET['action'] == 'update')
	{
		$allow_updates = true;
	}
}
if(isset($_GET['scout_id']))
{ 
	$_SESSION['scout_id'] = $_GET['scout_id'];
}
if (isset($_GET['req_view']))
	$_SESSION['req_view'] = $_GET['req_view'];
else if (!isset($_SESSION['req_view']))
	$_SESSION['req_view'] = 'Rank Advancement';

ini_set('session.bug_compat_42','false');

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
$pt->addStyleSheet('css/report.css');
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
	
	echo '<table cellpadding=5 border=0>';
	echo '<tr><td align="left" valign="top" '.($_SESSION['req_view'] == 'Rank Advancement' ? ' style="background: white"' : '').'>';
	echo '<a href="awards.php?req_view=Rank Advancement">Rank Advancement</a></td></tr>';
	echo '<tr><td align="left" valign="top" '.($_SESSION['req_view'] == 'Merit Badge' ? ' style="background: white"' : '').'>';
	echo '<a href="awards.php?req_view=Merit Badge">Merit Badges</a></td></tr>';
	echo '<tr><td align="left" valign="top"></table>';
	
	ShowGroupScoutSelectionBoxes($GLOBALS['troop_id'], $_SESSION['scout_id'], $is_scout, $_SESSION['group_id'], 'awards.php', $_GET['show']);	
	echo '</td>';
}
echo '<td valign="top">';

if ($_SESSION['scout_id'])
	$user_list = explode(',',$_SESSION['scout_id']);
else
	$user_list = Array();

//pre_print_r('user list: '.$user_list);
//pre_print_r($user_list);

$users = fetch_array_data('SELECT * FROM user WHERE id IN ('.implode(',',$user_list).') ORDER BY name');

if ($_SESSION['req_view'] == 'Rank Advancement')
{
	$awards = fetch_array_data('SELECT * FROM `award` WHERE type IN (\'Rank Advancement\', \'Eagle Palm\') ORDER BY type, rank_num');
	$title = 'Rank Advancements and Eagle Palms';
}
else
{
	$awards = fetch_array_data('SELECT * FROM `award` WHERE type IN (\'Merit Badge\') ORDER BY title');
	$title = 'Merit Badges';
}

$sql = 'SELECT * FROM award, user_award WHERE award.id = user_award.award_id AND user_id IN ('.implode(',',$user_list).')';
//pre_print_r($sql);
$user_award_data = fetch_array_data($sql);
$award_data = Array();
foreach ($user_award_data as $row)
	$award_data[$row['award_id']][$row['user_id']] = $row;

if($_GET['printpage'])
{
	echo '<script type="text/javascript">window.print();</script>';
}
else
{
	echo '<a href="awards.php?printpage=1" target="_blank">Print Page</a>';//&nbsp;&nbsp;|&nbsp;&nbsp;';
	//echo '<a href="requirements.php">Show Requirement Progress</a>';
	echo '<br />';
	
}

ShowAwardTable($awards, $users, $award_data, $title, $is_scoutmaster, $allow_updates);

echo '</td></tr></table>';

$pt->writeFooter();


?>