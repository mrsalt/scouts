<?php

// scout_membership_include.php
//
// contains routines for dealing with scout memberships, 
// including registration of scouts and the assignment of
// membership within scout groups (like 11 year old scouts, 
// 12-13 year old scouts, etc.) and the assignment of roles
// (patrol leader, chaplain aid, etc.)

// returns ID if successful, false if failure
function create_troop_group($troop_id, $group_name, &$error_message)
{
// Troop groups will not be customizable, but are created in the database.
// What happens is each troop can customize if a certain group is present in their 
// troop tracking or not.  For instance, we have 11 year old, 12-13 year old, and 14-15 year
// groups.  But not every troop has created instances of all groups within their troop.
// Instead the add_troop_group() and delete_troop_group() functions are used.
}

// returns true if successful, false if failure
function set_user_as_member($user_id, $group_id, $is_member, &$error_message)
{
// The original design was to allow users to belong to more than one group, however, 
// Randy implemented the simpler design where a user can belong to no group or 1 group.
// (which isn't necessarily a bad thing... makes things simpler, and in some cases more logical)
// This function will remain as a placeholder for that future idea, if it is ever implemented.
}

// returns true if successful, false if failure
function assign_role($user_id, $role_id, &$error_message, $start_date = null)
{
	if (!is_whole_number($role_id))
	{
		$role_id = do_query('SELECT id FROM roles WHERE title = \''.$role_id.'\'');	
	}
	
	$sql = "SELECT * FROM roles WHERE id = $role_id";
	$data = fetch_data($sql);
	if (!$data)
	{
		$error_message = 'Error.  Role '.$role_id.' does not exist';
		notify_administrator($error_message, $email = null, $echo_email_results = true);
		//pre_print_r('returing early -- line '.__LINE__);
		return false;
	}

	$sql = "SELECT * FROM user WHERE id = $user_id";
	$data = fetch_data($sql);
	if (!$data)
	{
		$error_message = 'Error.  User '.$user_id.' does not exist';
		notify_administrator($error_message, $email = null, $echo_email_results = true);
		return false;
	}
	$start_date = ($start_date == null ? date('Y-m-d') : date('Y-m-d',strtotime($start_date)));
	
	$sql = "SELECT * FROM scout_role WHERE user_id = $user_id AND role_id = $role_id";
	$data = fetch_data($sql);
	if ($data)
	{
		if ($data['start_date'] != $start_date or $data['active_role'] == 'F')
			$sql = "UPDATE scout_role SET start_date = '".$start_date."', active_role = 'T' WHERE user_id = $user_id AND role_id = $role_id";
		else
			$sql = '';
		//$error_message = 'Error.  '.do_query('SELECT name FROM user WHERE id = '.$user_id).' is already assigned role '.do_query('SELECT title FROM roles WHERE id = '.$role_id);
		//return false;
	}
	else
		$sql = "INSERT INTO scout_role (user_id, role_id, start_date) VALUES($user_id, $role_id, '".$start_date."')";
	
	if ($sql)
		return execute_query($sql);
	else
		return true;
}

function make_role_inactive($user_id, $role_id, &$error_message)
{
	$sql = "SELECT * FROM scout_role WHERE user_id = $user_id AND role_id = $role_id";
	$data = fetch_data($sql);
	if ($data)
	{
		if ($data['active_role'] == 'T')
			return execute_query("UPDATE scout_role SET active_role = 'F' WHERE user_id = $user_id AND role_id = $role_id");
	}
	return true;
}

function delete_all_roles($user_id, &$error_message)
{
	$sql = "SELECT * FROM user WHERE id = $user_id";
	$data = fetch_data($sql);
	if (!$data)
	{
		$error_message = 'Error.  User '.$user_id.' does not exist';
		notify_administrator($error_message, $email = null, $echo_email_results = true);
		return false;
	}
	
	$sql = "DELETE FROM scout_role WHERE user_id = $user_id";
	return execute_query($sql);	
}

function delete_role($user_id, $role_id, &$error_message)
{
	if (!is_whole_number($role_id))
	{
		$role_id = do_query('SELECT id FROM roles WHERE title = \''.$role_id.'\'');	
	}
	
	$sql = "SELECT * FROM roles WHERE id = $role_id";
	$data = fetch_data($sql);
	if (!$data)
	{
		$error_message = 'Error.  Role '.$role_id.' does not exist';
		notify_administrator($error_message, $email = null, $echo_email_results = true);
		return false;
	}

	$sql = "SELECT * FROM user WHERE id = $user_id";
	$data = fetch_data($sql);
	if (!$data)
	{
		$error_message = 'Error.  User '.$user_id.' does not exist';
		notify_administrator($error_message, $email = null, $echo_email_results = true);
		return false;
	}
	
	$sql = "DELETE FROM scout_role WHERE user_id = $user_id AND role_id = $role_id";
	return execute_query($sql);
}


//------------------------------------------------

function add_troop_group($troop_id, $group_name)
{
	$group_id = do_query('SELECT id FROM scout_group WHERE group_name = \''.$group_name.'\' AND troop_id = '.$troop_id,'scouts');
	if (!$group_id)
	{
		$sql = 'INSERT INTO scout_group (troop_id, group_name) VALUES ('.$troop_id.",'$group_name')";
		execute_query($sql);
	}
}

function delete_troop_group($troop_id, $group_name)
{
	$group_id = do_query('SELECT id FROM scout_group WHERE group_name = \''.$group_name.'\' AND troop_id = '.$troop_id,'scouts');
	if ($group_id)
	{
		$member_count = do_query('SELECT COUNT(*) FROM user_group WHERE group_id = '.$group_id);
		if ($member_count == 0)
		{
			$sql = 'DELETE FROM scout_group WHERE id = '.$group_id;
			execute_query($sql);
		}
	}
}

function get_troop_group_details($troop_id, $show_edit = true)
{
	$all_troop_groups = get_enums($table_name = 'scout_group', $field_name = 'group_name');
	$text .= "<table class=\"scout_form\" cellpadding=\"10\" cellspacing=\"0\">\n";
	//$text .= "<tbody style=\"border: 1px solid black;\">\n";
	$text .= "<tr><td>Group</td><td>Members</td>";
	if ($show_edit)
		$text .= "<td colspan=3>Action</td></tr>\n";
	foreach ($all_troop_groups as $group_name)
	{
		$group_id = do_query('SELECT id FROM scout_group WHERE group_name = \''.$group_name.'\' AND troop_id = '.$troop_id,'scouts');
		//$group_members = get_group_members($group_name, $troop_id);
		if ($group_id)
			$member_count = do_query('SELECT COUNT(*) FROM user_group WHERE group_id = '.$group_id);
		$text .= "<tr style=\"background: #DBE8E3;\">";
		$text .= "<td>".$group_name."</td>";
		$text .= "<td>".($group_id ? $member_count : 'N/A').'</td>';
		if ($show_edit)
		{
			$text .= "<td style=\"font-size: 8pt;\"><input type=\"radio\" name=\"action[".$group_name."]\" value=\"no change\" checked> No change</td>";
			if (!$group_id)
				$text .= "<td style=\"font-size: 8pt;\"><input type=\"radio\" name=\"action[".$group_name."]\" value=\"create group\"> Add Group</td>";
			else
				$text .= "<td></td>";
			if ($group_id and $member_count == 0)
				$text .= "<td style=\"font-size: 8pt;\"><input type=\"radio\" name=\"action[".$group_name."]\" value=\"delete group\"> Delete Group</td>";
			else
				$text .= "<td></td>";
		}
		$text .= "</tr>\n";
	}
	//$text .= "</tbody>\n";
	$text .= "</table>\n";
	return $text;
}

function get_members($troop_id, $group_name, $make_names_links = true, $show_blocked_users = false)
{
	if(!$group_name)
		$group_name = 'All Troop Members';
	
	if ($group_name == 'All Troop Members')
	{
		$sql = 'SELECT * FROM user WHERE scout_troop_id = '.$troop_id . ' AND (_scout = \'T\' OR _scoutmaster = \'T\')';
	}
	else if ($group_name == 'Other')
	{
		$sql = 'SELECT * FROM user WHERE scout_troop_id = '.$troop_id . ' AND (_scout = \'F\' AND _scoutmaster = \'F\')';
	}
	else
	{
		$sql = 'SELECT user.* FROM user, user_group, scout_group WHERE user.id = user_id AND group_name = \''.$group_name.'\' AND troop_id = '.$troop_id.' AND scout_group.id = user_group.group_id';
	}

	$sql .= ' ORDER BY _administrator asc, _scoutmaster asc, _scout desc, name asc';
		 
	//pre_print_r($sql);
	//pre_print_r(
	$members = fetch_array_data($sql,'scouts');
	
	return get_member_table($members, $troop_id, $group_name, $make_names_links, $show_blocked_users);
}

function get_member_table($members, $troop_id, $group_name, $make_names_links = true, $show_blocked_users = false)
{
	$text = "<table class=\"scout_form\" cellpadding=\"10\" cellspacing=\"0\" style=\"font-size: smaller;\">\n";
	//$text .= "<tbody style=\"border: 1px solid black;\">\n";
	$text .= "<tr>".($make_names_links ? '<td></td>':'')."<td>Name</td>";
	$text .= ($group_name == 'All Troop Members' ? "<td>Member Of</td>" : '');
	$text .= "<td>Rank</td><td>Responsibilities</td><td>Birthday</td>";
	$text .= "<td>Phone Number</td>".($make_names_links ? "<td>State</td><td>Last Login</td>" : '')."</tr>";
	
	$col_count = 5;
	if ($make_names_links)  // for the action column
		$col_count++;
	if ($group_name == 'All Troop Members')
		$col_count++;
	if ($make_names_links)
		$col_count += 2;

	$blocked_scout_found = false;
	if (count ($members))
	{
		foreach ($members as $member)
		{
			if($member['state'] == 'blocked')
			{
				$blocked_scout_found = true;
				if($show_blocked_users == false)
				{
					continue;
				}
			}

			if($member['_administrator'] == 'T')
				$text .= "<tr style=\"background: #98A5FC;\">";
			else if($member['_scoutmaster'] == 'T')
				$text .= "<tr style=\"background: #A9B4FC;\">";
			else if($member['_scout'] == 'F')
				$text .= "<tr style=\"background: #C1C9FD;\">";
			else
				$text .= "<tr style=\"background: #DBE8E3;\">";
			
			/********** Name Column ************************/
			if ($make_names_links)
			{
				$text .= "<td>";
				if ($member['_scout'] == 'T')
				{
					$award_id = get_next_rank_id($member['rank']);
					if ($award_id)
						$text .= "<a title=\"Pass off requirements\" style=\"color: blue\" href=\"requirements.php?req_view=Rank Advancement&scout_id=".$member['id']."&award_id=".$award_id."\"><img src=\"images/b_edit.png\" border=0></a>";
				}
				$text .= "</td>";
			}
			if ($make_names_links)
				$text .= "<td><a style=\"color: blue\" title=\"Update membership info\" href=\"membership.php?todo=edit member&id=".$member['id']."\">".$member['name']."</a></td>";
			else
				$text .= "<td>".$member['name']."</td>";

			/********** Age Group Column ************************/				
			if ($group_name == 'All Troop Members')
			{
				$sql = 'SELECT group_name '.
					   'FROM scout_group, user_group '.
					   'WHERE user_group.user_id = '.$member['id'].
					   ' AND user_group.group_id = scout_group.id';
				$groups = fetch_array_data($sql,'scouts');

				$text .= "<td>";
				$sep = '';
				if (count($groups))
				{
					foreach (array_field($groups,'group_name') as $g_name)
					{
						$text .= $sep.$g_name;
						$sep = ', ';
					}
				}
				$text .= "</td>";
			}

			/********** Rank Column ************************/			
			if ($member['rank'] == 'Leader' and isAdminUser($member['id']))
				$text .= "<td>Administrator</td>";
			else
				$text .= "<td>".$member['rank']."</td>";
			
			/********** Responsibility/Role Column ************************/
			$text .= "<td>";
			$sep = '';
			$sql = 'SELECT title '.
			       'FROM roles, scout_role '.
			       'WHERE scout_role.user_id = '.$member['id'].
			       ' AND active_role = \'T\''.
			       ' AND scout_role.role_id  = roles.id';
			$roles  = array_field(fetch_array_data($sql,'scouts'),'title');			
			if(is_array($roles))
			{
				$roles = array_unique($roles);
				foreach ($roles as $role_name)
				{
					$text .= $sep.$role_name;
					$sep = ', ';
				}
			}
			$text .= "</td>";
			
			/********** Birthday Column ************************/
			$text .= "<td>".((date('M d, Y',strtotime($member['dob'])) != 'Dec 30, 1969') ? date('M d, Y',strtotime($member['dob'])) : '').'</td>';
			
			/********** Phone Column ************************/
			$text .= "<td>".$member['phone']."</td>";
			
			if ($make_names_links)
			{
				/********** State Column ************************/
				$text .= "<td><span style=\"color: ".get_state_color($member['state']).";\">".$member['state']."</span></td>";
				/********** Last Login Column ************************/
				$text .= "<td>".$member['last_login']."</td>";
			}
			$text .= "</tr>\n";
		}
	}
	else
	{
		$text .= '<tr style="background: #DBE8E3;"><td colspan="'.$col_count.'">There are no troop members in the database in "'.$group_name.'" for troop '.$GLOBALS['troop_info']['troop_number'].'.</td></tr>';
	}
	if ($make_names_links)
	{
		$text .= '<tr style="background: #DBE8E3;"><td colspan="'.$col_count.'"><a href="membership.php?todo=add member">[ Add New Scout / Scouter to Troop '.$GLOBALS['troop_info']['troop_number'].']</td></tr>';
		if($blocked_scout_found)
		{
			$text .= '<tr style="background: #DBE8E3;"><td colspan="'.$col_count.'"><a href="membership.php?show_blocked_users=' . ($show_blocked_users ? 'false">[ Hide ' : 'true">[ Show ' ) . 'Blocked Scouts in Troop '.$GLOBALS['troop_info']['troop_number'].']</td></tr>';
		}
	}
	$text .= "</table>\n";
	return $text;
}

function get_state_color($state)
{
	$colors = Array('pending' => 'red',
	                'active' => 'green',
	                'blocked' => 'purple');
	if($colors[$state])
	{
		return $colors[$state];
	}
	return 'black';
}

function get_patrol_type_names($group_name)
{
	switch ($group_name)
	{
		case '11 Year Old Scouts':
			return Array('Patrol');
		case '12-13 Year Old Scouts':
			return Array('Patrol');
		case '14-15 Year Old Scouts':
			return Array('Team');
		case '16-18 Year Old Scouts':
			return Array('Crew');
		case '14-18 Year Old Scouts':
			return Array('Team','Crew');
	}
}

?>