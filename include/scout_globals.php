<?php
ini_set('include_path','.:/usr/local/lib/php:include');
require_once 'globals.php';

//global $troop_id;
$GLOBALS['troop_id'] = 0;

global $award_types;
$award_types = Array('Rank Advancement','Merit Badge','Other Award');

if (isset($_SESSION['USER_ID']))
{
	$GLOBALS['user_info'] = fetch_data('SELECT * FROM user WHERE id = '.$_SESSION['USER_ID'],'scouts');
	$GLOBALS['troop_id'] = do_query('SELECT scout_troop_id FROM user WHERE id = '.$_SESSION['USER_ID'],'scouts');
}

define('SITE_ADMIN_NAME','Boy Scout Webmaster');
define('SITE_ADMIN_EMAIL','webmaster@boyscoutwebsite.com');

if ($GLOBALS['troop_id'])
{
	$GLOBALS['troop_info'] = fetch_data('SELECT troop.*, council.id as council_id, council.name as council FROM troop, council WHERE troop.council_id = council.id and troop.id = '.$GLOBALS['troop_id'],'scouts');
	define('SITE_TITLE','Boy Scout Troop '.$GLOBALS['troop_info']['troop_number'].', '.$GLOBALS['troop_info']['location']);
}
else
{
	define('SITE_TITLE','The Boy Scout Website');
}

function get_scout_rank($user_id)
{
	if ($user_id)
	{
		$sql = 'SELECT rank FROM user WHERE user_id = '.$user_id;
		if ($rank = do_query($sql,'scouts'))
			return $rank;
	}
	return 'Unspecified';
}

/*
function get_scout_rank_id($rank_name)
{
	return do_query('SELECT id FROM award WHERE title = \''.$rank_name.'\'','scouts');	
}
*/

function get_next_rank_id($rank_name)
{
	switch ($rank_name)
	{
		case 'Boy Scout': $next_rank = 'Tenderfoot'; break;
		case 'Tenderfoot': $next_rank = 'Second Class'; break;
    case 'Second Class': $next_rank = 'First Class'; break;
    case 'First Class': $next_rank = 'Star'; break;
    case 'Star': $next_rank = 'Life'; break;
    case 'Life': $next_rank = 'Eagle'; break;
    case 'Eagle': $next_rank = ''; break;
  }
  
  if ($next_rank){
		$sql = 'SELECT id FROM award WHERE type = \'Rank Advancement\' AND title = \''.$next_rank.'\' ORDER BY req_revision DESC LIMIT 1';
		return do_query($sql,'scouts');
	}
}

function get_award_list($type)
{
	if ($type == 'Rank Advancement' or $type == 'Eagle Palm')
		$sql = "SELECT * FROM award WHERE type = '".$type."' ORDER BY rank_num, req_revision DESC";
	else if ($type == 'Merit Badge')
		$sql = "SELECT * FROM award WHERE type = '".$type."' ORDER BY title, req_revision DESC";
	$awards = fetch_array_data($sql,'scouts');
	
	$current_title = '';
	$out = Array();
	foreach ($awards as $award)
	{
		if ($award['title'] != $current_title){
			$out[] = $award;
			$current_title = $award['title'];
		}
	}
	return $out;
}

function show_pending_users_table()
{
	$sql = 'select id, name, email, create_date from user where state = \'pending\'';
	$pending_users = fetch_array_data($sql,'scouts');
	if(is_array($pending_users))
	{
		echo '<br /><br />';
		echo '<span style="font-weight: bold; font-size: 140%;">Pending Users:</span><br />';
		$header = false;
		echo '<table class="main">';
		foreach($pending_users as $user)
		{
			if(!$header)
			{
				echo '<tr>';
				foreach ($user as $key => $value)
				{
					echo '<td class="header">'.$key.'</td>';
				}
				echo '</tr>';
				$header = true;
			}
			foreach ($user as $key => $value)
			{
				echo '<td class="value">'.$value.'</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
	}
}

function getScoutsInGroup($group_id)
{
	$sql = 'select id from user, user_group where user_group.user_id = user.id and user._scout = \'T\' and user_group.group_id = '.$group_id;
	$users = fetch_array_data($sql,'scouts');
	if(is_array($users))
	{
		return array_field($users,'id');
	}
	return Array();
}

function isUser($privilege, $user_id = null)
{
	if (!$user_id)
		$user_id = $_SESSION['USER_ID'];
	if (!$user_id)
		return false;
	//$sql = 'SELECT id FROM user WHERE id = '.$_SESSION['USER_ID'].' AND _'.$privilege.' = \'T\'';
	if ($privilege == 'Scoutmaster' or $privilege == 'scoutmaster')
	{
		// Having the scoutmaster title and
		// having the scoutmaster privilege 
		// on the website are 2 different 
		// things.  You get the scoutmaster
		// title by being assigned the
		// role, but the scoutmaster (website)
		// privilege is given to any user who
		// has 'scoutmaster' checked on their
		// user form.
		// This allows other users (like the
		// assistant scoutmaster) to use the 
		// website like they were the scoutmaster,
		// but they are not officially listed 
		// as a scoutmaster on the responsibilities
		// list.
		if ($user_id == $_SESSION['USER_ID'])
		   return ($GLOBALS['user_info']['_scoutmaster'] == 'T');
		return do_query('SELECT id FROM user WHERE id = '.$user_id.' AND _scoutmaster = \'T\'');
	}
	else if ($privilege == 'Scout' or $privilege == 'scout')
	{
		if ($user_id == $_SESSION['USER_ID'])
		   return ($GLOBALS['user_info']['_scout'] == 'T');
		return do_query('SELECT id FROM user WHERE id = '.$user_id.' AND _scout = \'T\'');
	}
	else if (!is_whole_number($privilege))
	{
		$privilege = do_query('SELECT id FROM roles WHERE title = \''.$privilege.'\'');	
	}
	if (!$privilege)
	{
		notify_administrator('Invalid privilege id passed into function isUser().');
		return false;
	}
	
	$sql = 'SELECT COUNT(*) FROM scout_role WHERE user_id = '.$user_id.' AND role_id = '.$privilege.' AND active_role = \'T\'';
	if (do_query($sql,'scouts'))
		return true;
	return false;
}
	
function isAdminUser($user_id = null)
{
	if (!$user_id)
		$user_id = $_SESSION['USER_ID'];
	if (!$user_id)
		return false;
	$sql = 'SELECT id FROM user WHERE id = '.$user_id.' AND _administrator = \'T\'';
	//write_log(__FILE__.', line '.__LINE__.', sql='.$sql);
	if (do_query($sql,'scouts'))
		return true;
	return false;
	
}

function get_name($user_id)
{
	return do_query('SELECT name FROM user WHERE id = '.$user_id,'scouts');
}


?>