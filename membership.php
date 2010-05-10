<?php
require_once 'include/scout_globals.php';
require_once 'include/scout_membership_include.php';

$pt = new ScoutTemplate();
$pt->setPageTitle( SITE_TITLE );
//if ($_GET['todo'] == 'add member') // or edit member...
$pt->addScript('scripts/md5.js');
$pt->writeBanner();
$pt->writeMenu();
connect_db('scouts');

if (!array_key_exists('USER_ID',$_SESSION) || !isset($GLOBALS['troop_id']) || !$GLOBALS['troop_id'])
{
	echo '<br><br>You must first log in to access this page.';
	$pt->writeFooter();
	exit;
}

if($_GET['show_blocked_users'])
{
	if($_GET['show_blocked_users'] == 'true')
	{
		$_SESSION['show_blocked_users'] = true;
	}
	else
	{
		$_SESSION['show_blocked_users'] = false;
	}
}

$troop_id = $GLOBALS['troop_id'];

if (count($_POST))
{
	if ($_POST['target'] == 'update_groups')
	{
		foreach ($_POST['action'] as $group_name => $change)
		{
			if ($change == 'create group')
				add_troop_group($troop_id, $group_name);
			else if ($change == 'delete group')
				delete_troop_group($troop_id, $group_name);
		}
	}
	else if ($_POST['target'] == 'add_edit_patrol')
	{
		if (strlen(trim($_POST['patrol_name'])) == 0)
		{
			echo 'Error, '.$_POST['patrol_type'].' name cannot be blank.';
		}
		else
		{
			if (array_key_exists('patrol_id', $_POST))
			{
				$sql = 'UPDATE scout_patrol SET name = \''.addslashes($_POST['patrol_name']).'\' WHERE id = '.$_POST['patrol_id'];
				execute_query($sql, 'scouts');
				$patrol_id = $_POST['patrol_id'];
			}
			else
			{
				$sql = 'INSERT INTO scout_patrol (group_id, name, type) VALUES('.$_POST['group_id'].', \''.addslashes($_POST['patrol_name']).'\', \''.$_POST['patrol_type'].'\')';
				execute_query($sql);
				$patrol_id = mysql_insert_id();
			}
			
			$users_in_patrol_list = '';
			$insert_list = '';
			$sep = '';
			if (array_key_exists('user', $_POST))
			{
				foreach ($_POST['user'] as $user_id)
				{
					$users_in_patrol_list .= $sep . $user_id;
					$insert_list .= $sep . '('.$user_id.','.$patrol_id.')';
					$sep = ',';
				}
			}
			$sql = 'DELETE FROM scout_patrol_member WHERE patrol_id = '.$patrol_id;
			if ($users_in_patrol_list != '')
				$sql .= ' OR user_id IN ('.$users_in_patrol_list.')';		
			execute_query($sql);
			if ($insert_list != '')
			{
				$sql = 'INSERT INTO scout_patrol_member(user_id, patrol_id) VALUES '.$insert_list;
				execute_query($sql);
			}
			
			$patrol = fetch_data('SELECT * FROM scout_patrol WHERE id = '.$patrol_id,'scouts');
			$_GET['todo'] = 'patrol_roster';
			$_GET['type'] = $patrol['type'];
			$_GET['group_id'] = $patrol['group_id'];
			$_GET['id'] = $patrol_id;
			$_GET['name'] = $patrol['name'];
		}
	}
	else if ($_POST['target'] == 'delete_patrol')
	{
		$sql = 'DELETE FROM scout_patrol_member WHERE patrol_id = '.$_POST['patrol_id'];
		execute_query($sql);
		$sql = 'DELETE FROM scout_patrol WHERE id = '.$_POST['patrol_id'];
		execute_query($sql);
	}
	else if ($_POST['target'] == 'add_edit_member')
	{
		// validate that no one with the same name exists within this troop
		$sql = 'SELECT id FROM user WHERE name = \''.addslashes($_POST['name']).'\' AND scout_troop_id = '.$troop_id;
		$uid = do_query($sql, 'scouts');
		if (!$_POST['id'] and $uid)
		{
			$errors[] = 'You may not create a scout with the name "'.$_POST['name'].'".  A scout by that name already belongs to troop '.$GLOBALS['troop_info']['troop_number'].'.';
		}
		else if ($uid and $_POST['id'] and $_POST['id'] != $uid)
		{
			$errors[] = 'Form submitted user id does not match name "'.$_POST['name'].'" in database.  Please report error to administrator.';
		}
		else
		{
			if(!(isAdminUser() or (isUser('Scoutmaster') and (!$_REQUEST['id'] or ($troop_id == do_query('select scout_troop_id from user where id = '.$_REQUEST['id'],'scouts'))))))
			{
				echo 'Error: You do not have privileges to make these changes';
				exit;
			}
			
			if ($_POST['id'])
			{
				$udata = fetch_data('SELECT * FROM user WHERE id = '.$_POST['id'],'scouts');
				if ($udata['_scoutmaster'] == 'T' and (($_POST['scoutmaster'] != 'scoutmaster') or ($_POST['state'] != 'active')))
				{
					// scout master privilege is being removed.  make sure another active user in the troop still has it.
					$sql = 'SELECT COUNT(id) FROM user WHERE id != '.$_POST['id'].' AND scout_troop_id = '.$troop_id.' AND state = \'active\' AND _scoutmaster = \'T\'';
					$scoutmaster_count = do_query($sql,'scouts');
					if ($scoutmaster_count == 0)
					{
						echo 'Error: At least one person must in the troop with the scoutmaster privilege, otherwise no one can administer membership in the troop.';
						exit;						
					}
				}
			}
			
			$table_fields = Array('name' => $_POST['name'],
			                      'email' => $_POST['email'],
			                      '_scout' => ($_POST['youth_vs_adult'] == 'youth' ? 'T' : 'F'),
			                      '_scoutmaster' => ($_POST['scoutmaster'] == 'scoutmaster' ? 'T' : 'F'),
			                      'phone' => $_POST['phone'],
			                      'dob' => $_POST['year'].'-'.$_POST['month'].'-'.$_POST['day'],
			                      'scout_troop_id' => $troop_id,
			                      'state' => $_POST['state']);
			if(isAdminUser($_SESSION['USER_ID']))
				$table_fields['_administrator'] = ($_POST['administrator'] == 'administrator' ? 'T' : 'F');
			if (!$_POST['id'])
				$table_fields['create_date'] = date('Y-m-d');
			if ($_POST['pass_hash'])
				$table_fields['password'] = $_POST['pass_hash'];
			if ($_POST['parent'])
				$table_fields['rank'] = 'Parent';
			else if ($_POST['youth_vs_adult'] != 'youth')
				$table_fields['rank'] = 'Leader';
			
			$sep = '';
			foreach ($table_fields as $key => $value)
			{
				$query .= $sep.$key." = '".addslashes($value)."'";
				$sep = ', ';
			}
			if (!$_POST['id'])
				$sql = 'INSERT INTO user SET '.$query;
			else
				$sql = 'UPDATE user SET '.$query.' WHERE id = '.$_POST['id'];
			execute_query($sql);
			$id = ($_POST['id'] ? $_POST['id'] : mysql_insert_id());
			if($_POST['group_id'])
			{
				$group_id = do_query('select group_id from user_group where user_id = '.$id,'scouts');
				if(!$group_id)
				{
					$sql = 'INSERT INTO user_group set user_id = '.$id.', group_id = '.$_POST['group_id'];
					execute_query($sql);
				}
				else if ($group_id != $_POST['group_id'])
				{
					$sql = 'UPDATE user_group set group_id = '.$_POST['group_id'].' where user_id = '.$id;
					execute_query($sql);
					$sql = 'DELETE FROM scout_patrol_member WHERE user_id = '.$id;
					execute_query($sql);
				}
			}
			$sql = 'SELECT * FROM scout_role WHERE user_id = '.$id;
			$user_roles = fetch_array_data($sql,'','role_id');
			// when something becomes unchecked, we should change that responsibility to no longer active, but we shouldn't delete it
			foreach ($user_roles as $ur)
			{
				if ($ur['active_role'] == 'T' and !$_POST['responsibility'][$ur['role_id']])
				{
					// responsibility is being made inactive
					make_role_inactive($id, $ur['role_id'], $error_message);
				}
			}
			if (is_array($_POST['responsibility']))
			{
				foreach ($_POST['responsibility'] as $role_id => $is_checked)
				{
					if ($is_checked)
					{
						//pre_print_r('assigning role '.$role_id.' to user '.$id.' with date '.$_POST['start_date'][$role_id]);
						assign_role($id, $role_id, $error_message, $_POST['start_date'][$role_id]);
					}
				}
			}
			/*
			if ($_POST['scoutmaster'] == 'scoutmaster')
				assign_role($id, 'Scoutmaster', $error_message);
			else
				delete_role($id, 'Scoutmaster', $error_message);

			if(isAdminUser($_SESSION['USER_ID']))
			{
				if ($_POST['administrator'] == 'administrator')
					assign_role($id, 'Scoutmaster', $error_message);
				else
					delete_role($id, 'Scoutmaster', $error_message);
			}
			*/
		}	
		//pre_print_r($_POST);
	}
}

if (isset($_GET['membership_view']))
	$_SESSION['membership_view'] = $_GET['membership_view'];
if (!isset($_SESSION['membership_view']))
{
	//$_SESSION['membership_view'] = 'All Troop Members';
	if (isset($_SESSION['group_id']))
		$_SESSION['membership_view'] = do_query('select group_name from scout_group where id = '.$_SESSION['group_id'],'scouts');
	else if (!$_SESSION['group_id'] and $_SESSION['USER_ID'])
	{
		$_SESSION['membership_view'] =  do_query('select group_name from scout_group, user_group where scout_group.id = user_group.group_id and user_id = '.$_SESSION['USER_ID'],'scouts');
	}
}

$troop_groups = fetch_array_data('SELECT id, group_name FROM scout_group WHERE troop_id = '.$troop_id.' ORDER BY group_name','scouts','id');
$group_names = array_field($troop_groups,'group_name');

if (!is_array($group_names))
	$group_names = Array();

$membership_views = array_merge(Array('All Troop Members'), $group_names, Array('Other'), Array('Edit Age Groups'));

if (count($group_names) > 0)
	$membership_views = array_merge($membership_views, Array('Patrols'));

echo '<br>';
$sep = '';
foreach ($membership_views as $view)
{
	if ($view == $_SESSION['membership_view'] and !$_GET['todo'])
		echo $sep.'<b>[ '.$view.' ]</b>';
	else
		echo $sep.'<a href="membership.php?membership_view='.$view.'">[ '.$view.' ]</a>';
	$sep = '&nbsp;&nbsp;';
}

if (is_array($errors) and count($errors))
{
	echo '<div style="font-size: large; font-weight: bold; color: red">'.implode('<br><br>',$errors).'</div>';
}
		
//pre_print_r($membership_views);
if ($_GET['todo'] == 'merge_user' or $_GET['todo'] == 'merge_user_confirmed')
{
	echo '<div style="margin: 25px;">';
	
	if (!isUser('scoutmaster'))
	{
		die('You must be a scoutmaster to perform this action');
	}
	
	$duplicate = fetch_data('SELECT * FROM user WHERE id = '.$_GET['duplicate_id'], 'scouts');
	$sql = 'SELECT * FROM user WHERE '.(is_numeric($_GET['real_id']) ? 'id = '.$_GET['real_id'] : ' name = \''.$_GET['real_id'].'\'');
	
	$original = fetch_data($sql, 'scouts');
	
	if (!$duplicate or !$original)
	{
		echo 'User not found with ID or Name '.$_GET['real_id'].'<br/>';
		$_GET['todo'] = 'edit member';
		$_GET['id'] = $_GET['duplicate_id'];
	}
	else
	{
		if ($_GET['todo'] == 'merge_user_confirmed')
		{
			if ($original['troop_id'] != $duplicate['troop_id'])
			{
				echo 'Error.  User ID '.$_GET['real_id'].' is not a member of this troop.';
			}
			else
			{
				$sql = 'UPDATE user SET password = \''.$duplicate['password'].'\', email = \''.addslashes($duplicate['email']).'\' WHERE id = '.$original['id'];
				//echo $sql;
				if (!mysql_query($sql))
					die('Query Failed!  error='.mysql_error()."\nsql=".$sql);
				$sql = 'DELETE FROM user WHERE id = '.$duplicate['id'];
				//echo $sql;
				if (!mysql_query($sql))
					die('Query Failed!  error='.mysql_error()."\nsql=".$sql);
			}
		}
		else
		{
			$duplicate_user_info = '<blockquote>Name: '.$duplicate['name'].'<br>Email: '.$duplicate['email'].'<br>ID: '.$duplicate['id'].'</blockquote>';
			$real_user_info = '<blockquote>Name: '.$original['name'].'<br>Email: '.$original['email'].'<br>ID: '.$original['id'].'</blockquote>';
			
			echo 'If you would like to delete this user: '.$duplicate_user_info.' and update the existing user: '.$real_user_info.' to have e-mail address and password of the first user, click OK';
			
			echo '<br/><br/><input type="button" value=" OK " onClick="location=\'membership.php?todo=merge_user_confirmed&duplicate_id='.$_GET['duplicate_id'].'&real_id='.$original['id'].'\'">';
			echo '</div>';
		}
	}
}

if ($_GET['todo'] == 'add member' or $_GET['todo'] == 'edit member')
{
	// person is an administrator or a scoutmaster in the troop of the user he is editing
	if(!(isAdminUser() or (isUser('Scoutmaster') and (!$_REQUEST['id'] or ($troop_id == do_query('select scout_troop_id from user where id = '.$_REQUEST['id'],'scouts'))))))
	{
		echo '<br /><br />Error: You do not have privileges to view this page.';
		exit;
	}
	echo '<div style="margin: 25px;">';
	require_once 'login_include.php';
	get_validation_script('new_login', $require_email = false, $require_password = false, $require_troop = false, $require_council = false);
	echo '<script type="text/javascript">';
	echo 'function validate_form(form){'."\n";
	echo "  if (form.month.value != parseInt(form.month.value) || parseInt(form.month.value) < 1 || parseInt(form.month.value) > 12){\n";
	echo "    alert('Please enter a number for the month (1-12)');\n";
	echo "    form.month.focus();\n";
	echo "    return false; }\n";
	echo "  if (form.day.value != parseInt(form.day.value) || parseInt(form.day.value) < 1 || parseInt(form.day.value) > 31){\n";
	echo "    alert('Please enter a number for the day (1-31)');\n";
	echo "    form.month.focus();\n";
	echo "    return false; }\n";
	echo "  if (form.year.value != parseInt(form.year.value) || parseInt(form.year.value) < 1 || parseInt(form.year.value) > 2000){\n";
	echo "    alert('Please enter a number for the year (1900-2000)');\n";
	echo "    form.month.focus();\n";
	echo "    return false; }\n";
	echo "  return validate_login(form);\n";
	echo "}\n";
	echo "function changeRoles(youth_roles){\n";
	echo "  document.getElementById('youth_roles').style.display = (youth_roles ? '' : 'none');\n";
	echo "  document.getElementById('adult_roles').style.display = (youth_roles ? 'none' : '');\n";
	echo "  document.getElementById('special_privileges_row').style.display = (youth_roles ? 'none' : '');\n";
	echo "  if (youth_roles){\n";
	echo "    document.getElementById('scoutmaster').checked = false;\n";
	echo "    document.getElementById('parent').checked = false;\n";
	echo "  }\n";
	echo "}\n";
	echo "function changeState(new_state){\n";
	echo "  document.getElementById('pending_description').style.display = (new_state == 'pending' ? '' : 'none');\n";
	echo "  document.getElementById('active_description').style.display = (new_state == 'active' ? '' : 'none');\n";
	echo "  document.getElementById('blocked_description').style.display = (new_state == 'blocked' ? '' : 'none');\n";
	echo "}\n";
	echo '</script>';
	echo '<form name="member_form" method="POST" action="membership.php" onSubmit="return validate_form(this);">';
	if ($_GET['todo'] == 'edit member')
	{
		$data = fetch_data('SELECT * FROM user WHERE id = '.$_GET['id'],'scouts');
		if (!$data)
			die('No troop member exists with id '.$_GET['id']);
		$title = 'Edit Troop Member';
		$text .= "<input type=\"hidden\" name=\"id\" value=\"".$_GET['id']."\">";
	}
	else // add member
	{
		$data = Array('_scout' => 'T', 'scout_troop_id' => $troop_id, 'state' => 'active');
		$title = 'New Troop Member';
	}
	
	if (isUser('Scoutmaster') and $_GET['id'])
	{
		$sql = 'SELECT COUNT(*) FROM user_req WHERE user_id = '.$_GET['id'];
		$pass_off_count = do_query($sql,'scouts');
		if ($pass_off_count == 0)
		{
			$text .= 'This user has no requirements passed off. <br />';
		}
		$user_info = fetch_data('SELECT * FROM user WHERE id = '.$_GET['id'], 'scouts');
		if ($pass_off_count == 0 and $user_info['state'] == 'pending' and $user_info['password'])
		{
			// All clues that the boy may have registered this user and 
			// perhaps we already have this user in the troop...
			// user has nothing passed off
			// user is in pending state
			// user has a password
			$text .= 'If this user is a new account and is a duplicate of another user account in the troop now, hit the &quot;Merge User&quot; button and enter the original name or user ID.<br/>';
			$text .= "<input type=\"button\" value=\"Merge User\" onClick=\"if (uid = prompt('Enter the real user ID or Name','')) location = 'membership.php?todo=merge_user&duplicate_id=".$_GET['id']."&real_id='+uid;\">";
		}
		
		//$text .= "<input type=\"button\" value=\"Delete User\"> <br />";
	}
	
	$text .= "<table class=\"scout_form\" cellpadding=\"10\" cellspacing=\"0\">\n";
	//$text .= "<tbody style=\"border: 1px solid black;\">\n";
	$text .= "<tr><td colspan=\"2\" align=\"center\">".$title."</td></tr>\n";
	
	$text .= "<tr style=\"background: #DBE8E3;\"><td colspan=\"2\"><table cellpadding=\"3\" style=\"color: black; margin-left: 20px; margin-right: 20px\">";
	$text .= "<tr style=\"background: #DBE8E3;\"><td>Youth or Adult</td><td>".
	         "<input type=\"radio\" onClick=\"changeRoles(this.checked == true);\" name=\"youth_vs_adult\" value=\"youth\"".($data['_scout'] == 'T' ? ' checked' : '')."> Youth<br />".
	         "<input type=\"radio\" onClick=\"changeRoles(this.checked == false);\" name=\"youth_vs_adult\" value=\"adult\"".($data['_scout'] == 'T' ? '' : ' checked')."> Adult<br />".
	         "</td></tr>";

	$text .= "<tr style=\"background: #DBE8E3;\" id=\"special_privileges_row\"><td width=\"240px\">Special Privileges<br><small><em>The scoutmaster privilege allows a user to do things a scoutmaster is able to do on the website, but does not give them the title (example: assistant scoutmaster should have this privilege).</em></small></td><td>".   
	         "<input type=\"checkbox\" id=\"scoutmaster\" name=\"scoutmaster\" value=\"scoutmaster\"".($data['_scoutmaster'] == 'T' ? ' checked' : '')."> Scoutmaster".
	         "<br /><input type=\"checkbox\" id=\"parent\" name=\"parent\" value=\"parent\"".($data['rank'] == 'Parent' ? ' checked' : '')."> Parent".
	         (isAdminUser() ? "<br /><input type=\"checkbox\" name=\"administrator\" value=\"administrator\"".($data['_administrator'] == 'T' ? ' checked' : '')."> Administrator" : "").
	         "</td></tr>\n";
	$text .= "<tr style=\"background: #DBE8E3;\"><td>Name (required)</td><td><input type=\"text\" name=\"name\" value=\"".$data['name']."\"></td></tr>\n";
	$text .= "<tr style=\"background: #DBE8E3;\"><td width=\"240px\">Password<br><small><em>Entering a password and e-mail address are only necessary if this person will be able to login to this website.  If a login is created for a boy, this information can be shared with him and his parents and he will be able to view requirements but not pass them off.  This can be entered later.</em></small></td><td><input type=\"password\" name=\"password\" value=\"\"></td></tr>\n";
	$text .= "<tr style=\"background: #DBE8E3;\"><td>Repeat password</td><td><input type=\"password\" name=\"re_password\" value=\"\"></td></tr>\n";
	$text .= "<tr style=\"background: #DBE8E3;\"><td>E-mail</td><td><input type=\"text\" name=\"email\" value=\"".$data['email']."\"></td></tr>\n";
	$text .= "<tr style=\"background: #DBE8E3;\"><td>Phone</td><td><input type=\"text\" name=\"phone\" value=\"".$data['phone']."\"></td></tr>\n";
	$text .= "<tr style=\"background: #DBE8E3;\"><td>Date of Birth (required)</td><td>".
	            "<input type=\"text\" id=\"month\" name=\"month\" value=\"". ($data['dob'] ? date('n',strtotime($data['dob'])) : '')."\" size=\"2\" maxlength=\"2\">".
	            " / <input type=\"text\" id=\"day\" name=\"day\" value=\"".  ($data['dob'] ? date('j',strtotime($data['dob'])) : '')."\" size=\"2\" maxlength=\"2\">".
	            " / <input type=\"text\" id=\"year\" name=\"year\" value=\"".($data['dob'] ? date('Y',strtotime($data['dob'])) : '')."\" size=\"4\" maxlength=\"4\"></td></tr>\n";
	$groups = fetch_array_data('select id as value, group_name as name from scout_group where troop_id = '.$data['scout_troop_id'],'scouts');
	array_unshift($groups, Array('name' => '&lt;Please Select&gt;', 'value' => 0));
	if ($_GET['id'])
		$group_id = do_query('select group_id from user_group where user_id = '.$_GET['id'],'scouts');
	else
		$group_id = 0;
	$text .= "<tr style=\"background: #DBE8E3;\"><td>Group</td><td>".ShowSelectList('group_id',$group_id,$groups).'</td></tr>';
	
	//Array('No Rank','Boy Scout','Tenderfoot','Second Class','First Class','Star','Life','Eagle','Leader','Parent')
	//$text .= "<tr style=\"background: #DBE8E3;\"><td>Current Rank</td><td>".ShowSelectList('rank',$data['rank'],get_enums('user','rank')).'</td></tr>';
	
	$text .= "<tr style=\"background: #DBE8E3;\"><td colspan=\"2\">";
	$text .= '<div id="youth_roles"><table style="color: black;"><tr><td>Responsibilities</td><td>Date Assigned</td></tr>';
	$roles = fetch_array_data('SELECT * FROM roles WHERE youth_role = \'T\' ORDER BY id');
	if ($_GET['id'])
		$user_data = fetch_array_data('SELECT * FROM scout_role WHERE active_role = \'T\' AND user_id = '.$_GET['id'],'','role_id');
	
	foreach ($roles as $role_data)
	{
		$date_val = ($user_data[$role_data['id']]['start_date'] ? date('m/d/Y',strtotime($user_data[$role_data['id']]['start_date'])) : '');
		$text .= '<tr><td><input type="checkbox" name="responsibility['.$role_data['id'].']" '.(isset($user_data[$role_data['id']]) ? 'checked' : '').'> '.$role_data['title']."</td><td><input type=\"text\" name=\"start_date[".$role_data['id']."]\" value=\"".$date_val."\"></td></tr>\n";
	}
	$text .= '</table></div><div id="adult_roles"><table style="color: black;"><tr><td>Responsibilities</td><td>Date Assigned</td></tr>';
	$roles = fetch_array_data('SELECT * FROM roles WHERE adult_role = \'T\' ORDER BY id');
	foreach ($roles as $role_data)
	{
		$date_val = ($user_data[$role_data['id']]['start_date'] ? date('m/d/Y',strtotime($user_data[$role_data['id']]['start_date'])) : '');
		$text .= '<tr><td><input type="checkbox" name="responsibility['.$role_data['id'].']" '.(isset($user_data[$role_data['id']]) ? 'checked' : '').'> '.$role_data['title']."</td><td><input type=\"text\" name=\"start_date[".$role_data['id']."]\" value=\"".$date_val."\"></td></tr>\n";
	}
	$text .= '</table></div>';
	$text .= '</td></tr>';
	$text .= "<tr style=\"background: #DBE8E3;\"><td width=\"240px\">State<br><small><em><span id=\"pending_description\" style=\"display: none\">Pending - User has registered for access to this troop but has not yet been approved access by someone with authority to grant access (Scoutmaster privilege has to be assigned.  See special privileges above.)</span><span id=\"active_description\" style=\"display: none\">Active - User is current member in troop and can login to site (if e-mail address and password are set).</span><span id=\"blocked_description\" style=\"display: none\">Blocked - User is no longer active with troop, has completed their scouting, has moved away, or for whatever reason is no longer part of the troop.</span></td><td>".
	         "<input type=\"radio\" name=\"state\" value=\"pending\"".($data['state'] == 'pending' ? ' checked' : '')." onclick=\"changeState('pending')\"> Pending<br />".
	         "<input type=\"radio\" name=\"state\" value=\"active\"".($data['state'] == 'active' ? ' checked' : '')." onclick=\"changeState('active')\"> Active<br />".
	         "<input type=\"radio\" name=\"state\" value=\"blocked\"".($data['state'] == 'blocked' ? ' checked' : '')." onclick=\"changeState('blocked')\"> Blocked".
	         "</td></tr>\n";
	$text .= "</table></td></tr>";
	$text .= '<tr><td colspan="2" align="center"><input type="submit" value="Submit">&nbsp;&nbsp;&nbsp;<input type="button" value="Cancel" onClick="location=\'membership.php\'"></td></tr>';
	$text .= "</table>";
	echo $text;
	echo "<script type=\"text/javascript\">\n";
	echo "changeRoles(".($data['_scout'] == 'F' ? ' false' : 'true').");\n";
	echo "changeState('".$data['state']."');\n";
	echo "</script>\n";
	echo '<input type="hidden" name="pass_hash" value="">';
	echo '<input type="hidden" name="target" value="add_edit_member">';
	//echo '<input type="button" value="test" onClick="alert(validate_form(document.member_form) ? \'success\' : \'failure\');">';
	echo '</form>';
	echo '</div>';
}
else if ($_SESSION['membership_view'] == 'Edit Age Groups')
{
	echo '<div style="margin: 25px;">';
	echo '<form method="POST">';
	echo get_troop_group_details($troop_id, true);
	echo '<input type="hidden" name="target" value="update_groups">';
	echo '<input type="submit" value="Submit">';
	echo '</form>';
	echo '</div>';
}
else if ($_SESSION['membership_view'] == 'Patrols')
{
	// Patrol / Team / Crew [                  ]
	//   or Patrol :  No Patrols created yet
	// Add Patrol / Team / Crew
	echo '<br/><br/>';
	echo '<table><tr><td valign=top>';
	echo '<table cellpadding=5 style="background: white;">';
	foreach ($troop_groups as $group)
	{
		echo '<tr style="font-weight: bold; color: white; background: black"><td>'.$group['group_name'].'</td></tr>';
		$patrol_types = get_patrol_type_names($group['group_name']);
		foreach ($patrol_types as $pt_name)
		{
			echo '<tr style="color: white; background: gray"><td>'.$pt_name.'s</td></tr>';
			$sql = 'SELECT scout_patrol.*, COUNT(scout_patrol_member.user_id) AS patrol_count FROM scout_patrol LEFT JOIN scout_patrol_member ON (scout_patrol.id = scout_patrol_member.patrol_id) WHERE scout_patrol.group_id = '.$group['id'].' AND scout_patrol.type = \''.$pt_name.'\' GROUP BY scout_patrol.id ORDER BY name';
			$patrols = fetch_array_data($sql, 'scouts');
			foreach ($patrols as $patrol)
			{
				echo '<tr><td style="color: black">';
				if ($_GET['todo'] == 'patrol_roster' and $_GET['id'] == $patrol['id'])
					echo $patrol['name'].' ('.$patrol['patrol_count'].' boy'.($patrol['patrol_count']==1?'':'s').')';
				else
					echo '<a href="membership.php?membership_view=Patrols&type='.$pt_name.'&group_id='.$group['id'].'&id='.$patrol['id'].'&name='.$patrol['name'].'&todo=patrol_roster">'.$patrol['name'].' ('.$patrol['patrol_count'].' boy'.($patrol['patrol_count']==1?'':'s').')</a>';
				echo '</td></tr>';
			}
			echo '<tr><td><a href="membership.php?membership_view=Patrols&type='.$pt_name.'&group_id='.$group['id'].'&todo=create_patrol">[ Create '.$pt_name.' ]</a></td></tr>';
		}
	}
	echo '<tr><td></td></tr>';
	echo '</table></td><td valign=top style="padding-left: 25px;">';
	
	if ($_GET['todo'] == 'patrol_roster')
	{
		$sql = 'SELECT user.* FROM user, scout_patrol_member WHERE user.id = scout_patrol_member.user_id AND scout_patrol_member.patrol_id = '.$_GET['id'].' AND scout_troop_id = '.$troop_id;
		$members = fetch_array_data($sql);
		echo '<h2>'.$_GET['name'].' '.$_GET['type'].'</h2>';
		
		echo '<a href="membership.php?membership_view=Patrols&type='.$_GET['type'].'&group_id='.$_GET['group_id'].'&id='.$_GET['id'].'&name='.$_GET['name'].'&todo=edit_patrol">[ Update '.$_GET['name'].' '.$_GET['type'].' Name/Membership ]</a><br/><br/>';
		
		echo get_member_table($members, $troop_id, $group_name, $make_names_links = true, $show_blocked_users = false);
	
	}
	else if ($_GET['todo'] == 'create_patrol' or $_GET['todo'] == 'edit_patrol')
	{
		$group_name = $troop_groups[$_GET['group_id']]['group_name'];
		$text = "<form action=\"membership.php?membership_view=Patrols\" method=\"POST\" onsubmit=\"if (document.getElementById('target').value == 'delete_patrol') { return confirm('Are you sure you want to delete this ".$_GET['type']."?'); } else { return true; }\">";
		$text .= "<input type=\"hidden\" name=\"target\" id=\"target\" value=\"add_edit_patrol\">";
		if ($_GET['todo'] == 'edit_patrol')
			$text .= "<input type=\"hidden\" name=\"patrol_id\" value=\"".$_GET['id']."\">";
		$text .= "<input type=\"hidden\" name=\"patrol_type\" value=\"".$_GET['type']."\">";
		$text .= "<input type=\"hidden\" name=\"group_id\" value=\"".$_GET['group_id']."\">";
		
		$text .= "<table class=\"scout_form\" cellpadding=\"10\" cellspacing=\"0\">\n";
		$text .= "<tr><td colspan=\"2\" align=\"center\">".($_GET['todo'] == 'edit_patrol' ? 'Update' : 'Create')." ".$_GET['type']."<br/>".$group_name."</td></tr>\n";
		
		$text .= "<tr style=\"background: #DBE8E3;\"><td colspan=\"2\">";
		  $text .= "<table cellpadding=\"3\" style=\"color: black; margin-left: 20px; margin-right: 20px\">";
		  $text .= "<tr style=\"background: #DBE8E3;\"><td>".$_GET['type']." Name</td><td><input type=\"text\" name=\"patrol_name\"".($_GET['todo'] == 'edit_patrol' ? 'value="'.$_GET['name'].'"' : '')."></td></tr>";
		  $text .= "</table>";
		$text .= "</td></tr>";
		
		$sql = 'SELECT user.*, scout_patrol_member.patrol_id, scout_patrol.name AS patrol_name './/
		       'FROM user_group, scout_group, user '. //scout_patrol,
			   'LEFT JOIN (scout_patrol_member, scout_patrol) ON (user.id = scout_patrol_member.user_id AND scout_patrol_member.patrol_id = scout_patrol.id) '.
			   'WHERE user.id = user_group.user_id'.
               ' AND scout_group.id = '.$_GET['group_id'].
			   ' AND troop_id = '.$troop_id.
			   ' AND scout_group.id = user_group.group_id'.
               ' AND user.state = \'Active\' '.
			   ' AND user._scout = \'T\' '.
			   //' AND (scout_patrol_member.patrol_id = scout_patrol.id OR scout_patrol_member.patrol_id IS NULL) '.
			   'ORDER BY _administrator asc, _scoutmaster asc, _scout desc, name asc';
			   
		$users = fetch_array_data($sql, 'scouts');
		
		$text .= "<tr style=\"background: #DBE8E3;\"><td colspan=\"2\">";
		$text .= "Select boys to assign to this patrol:<br/>";
		
		  $text .= '<table cellpadding=\"3\" style=\"color: black; margin-left: 20px; margin-right: 20px\">';
		  $text .= "<tr><th></th><th style=\"color: black;\">Name</th><th style=\"color: black;\">Current ".$_GET['type']."</th></tr>";
		
		  $count = 0;
		  foreach ($users as $user)
		  {
			$text .= "<tr style=\"background: #DBE8E3;\"><td><input type=\"checkbox\" name=\"user[".$count."]\" value=\"".$user['id']."\"";
			if ($_GET['todo'] == 'edit_patrol' and $user['patrol_id'] == $_GET['id'])
				$text .= "checked";
			$text .= "></td>";
			$text .= "<td style=\"color: black;\">".$user['name']."</td>";
			$text .= "<td style=\"color: black;\">".$user['patrol_name']."</td></tr>";
			$count++;
		  }
		  $text .= "</table>";
		$text .= "</td></tr>";
		$text .= "<tr><td><input type=\"submit\" onclick=\"document.getElementById('target').value='add_edit_patrol';\" value=\"".($_GET['todo'] == 'edit_patrol' ? 'Update' : 'Create')." Patrol\">";
		if ($_GET['todo'] == 'edit_patrol')
			$text .= "&nbsp;&nbsp;&nbsp;<input type=\"submit\" onclick=\"document.getElementById('target').value='delete_patrol';\" value=\"Delete ".$_GET['type']."\">";
		$text .= "</td></tr>";
		$text .= "</table>";
		$text .= "</form>";
		echo $text;
	}
	else
	{
		foreach ($troop_groups as $group)
		{
			echo '<h3>'.$group['group_name'].'</h3>';
			$patrol_types = get_patrol_type_names($group['group_name']);
			foreach ($patrol_types as $pt_name)
			{
				$sql = 'SELECT * FROM scout_patrol WHERE group_id = '.$group['id'].' AND scout_patrol.type = \''.$pt_name.'\'';
				$patrols = fetch_array_data($sql,'scouts');
				foreach ($patrols as $patrol)
				{
					echo '<div style="padding-left: 15px;"><h4>'.$patrol['name'].' '.$pt_name.'</h4>';
					$sql = 'SELECT user.name FROM user, scout_patrol_member WHERE user.id = scout_patrol_member.user_id AND scout_patrol_member.patrol_id = '.$patrol['id'].' AND user.state = \'active\'';
					$patrol_members = fetch_array_data($sql,'scouts','name');
					$member_list = implode(', ',array_keys($patrol_members));
					echo $member_list ."</div>\n";
				}
			}
			
			$sql = 'SELECT user.name FROM user_group, user LEFT JOIN scout_patrol_member ON scout_patrol_member.user_id = user.id WHERE user.id = user_group.user_id AND user_group.group_id = '.$group['id'].' AND patrol_id IS NULL AND _scout = \'T\' AND state = \'active\'';
			$not_assigned = fetch_array_data($sql,'scouts','name');
			if (count($not_assigned) > 0)
			{
				$member_list = implode(', ',array_keys($not_assigned));
				echo '<div style="padding-left: 15px;"><h4>Not assigned to any '.implode(', ', $patrol_types).':</h4>';
				echo $member_list ."</div>\n";
			}
		}
	}
	echo '</td></tr></table>';
	
}
else
{
	echo '<div style="margin: 25px;">'.get_members($troop_id, $_SESSION['membership_view'], (isUser('Scoutmaster') or isAdminUser()), $_SESSION['show_blocked_users']).'</div>';
}

$pt->writeFooter();
?>