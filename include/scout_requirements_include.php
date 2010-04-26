<?php

require_once 'scout_globals.php';
require_once 'scout_requirements_navigation.php';

$GLOBALS['write_debug_log'] = true;

function hasUserFulfilledRequirement($requirement_id, $user_id, &$data, $data_passed_in = false)
{
	if(!$data_passed_in)
	{
		$sql = "SELECT * FROM user_req WHERE user_id = $user_id AND req_id = $requirement_id";
		$data = fetch_data($sql);
	}
	if ($data)
	{
		return ($data['signed_by'] != 0);
	}
	return false;
}

function hasUserCompletedAward($award_id, $user_id, &$data)
{
	$sql = "SELECT * FROM user_award WHERE user_id = $user_id AND award_id = $award_id";
	$data = fetch_data($sql);
	if ($data)
	{
		return ($data['signed_by'] != 0);
	}
	return false;
}

function EraseRequirement($requirement_id, $user_id, $erased_by, &$error_msg)
{
	// only the person who signed it off in the first place should be allowed to erase it...  perhaps...  there could also be a troop "super" user
	
	$sql = "SELECT requirement.*, award.title AS award_title, award.type AS award_type FROM requirement, award WHERE requirement.id = $requirement_id AND requirement.award_id = award.id";
	$req_data = fetch_data($sql);
	if (!$req_data)
	{
		die('Cannot erase requirement - requirement id '.$requirement_id.' not found');	
	}
	
	if (!hasUserFulfilledRequirement($requirement_id, $user_id, $user_data))
	{
// RR - commented this out for group passoffs	- this should not be an error, but we should return in this case
//		$error_msg = "Cannot erase requirement pass off ".$req_data['user_number'].
//		             " for ".$req_data['award_title']." ".$req_data['award_type'].".  ".
//		             get_name($user_id)." has not passed off this requirement";
		return false;	
	}
	
// - since proxy signing is now allowed, 4/22/2006, Mark Salisbury, we need to remove this check.
//	$signed_by_originally = do_query("SELECT signed_by FROM user_req WHERE user_id = $user_id AND req_id = $requirement_id",'scouts');
//	
//	if ($erased_by != $signed_by_originally)
//	{
//		$error_msg = "Cannot erase requirement pass off ".$req_data['user_number'].
//		             " for ".$req_data['award_title']." ".$req_data['award_type']." for ".get_name($user_id).'.'.
//		             "  Only the person who passed this requirement off originally (".get_name($signed_by_originally).") may erase this signature.";
//		return false;	
//	}
	
	if ($GLOBALS['write_debug_log'])
		write_log('erasing requirement (req_id='.$requirement_id.', user_id='.$user_id.')');
	$sql = "UPDATE user_req SET signed_date = '0000-00-00', signed_by = 0 WHERE user_id = $user_id AND req_id = $requirement_id";
	
	if ($sql)
	{
		execute_query($sql,'scouts');
	}
	
	if ($req_data['parent_id'])
	{
		CascadeRequirementSignOff($req_data['parent_id'], $user_id, $erased_by, $error_msg, false);
	}
	
	// need to check to see if the award is still passed off...	
	CascadeAwardSignOff($req_data['award_id'], $user_id, $erased_by, &$error_msg);
	return true;
}

function EraseAward($award_id, $user_id, $erased_by, &$error_msg)
{
	if (!hasUserCompletedAward($award_id, $user_id, $user_data))
		return;
	
	$sql = "UPDATE user_award SET signed_date = '0000-00-00', signed_by = 0 WHERE user_id = $user_id AND award_id = $award_id";
	
	if ($sql)
	{
		execute_query($sql,'scouts');
	}
	
	// if this award is a merit badge... we should find and erase a requirement now
	$sql = 'SELECT * FROM award WHERE id = '.$award_id;
	$award_data = fetch_data($sql);
	if ($award_data['type'] == 'Merit Badge')
	{
		$sql = 'SELECT req_id FROM user_req WHERE signed_by != 0 AND user_id = '.$user_id.' AND merit_badge_id = '.$award_id;
		$req_id = do_query($sql);
		if ($req_id)
		{
			if ($GLOBALS['write_debug_log'])
				write_log('erasing merit badge requirement (req_id='.$req_id.', user_id='.$user_id.') because the matching merit badge (award_id='.$award_id.') has been erased');
			EraseRequirement($req_id, $user_id, $erased_by, $error_msg);
		}
		else
		{
			notify_administrator('erasing award (id='.$award_id.', user_id='.$user_id.') which is a merit badge but no matching user_req can be found');
		}
	}
	else if ($award_data['type'] == 'Rank Advancement')
	{
		UpdateRank($user_id);
	}
}

function SignOffRequirement($requirement_id, $user_id, $sign_date, $signed_by, &$error_msg, $merit_badge_id = 0)
{
	$sign_date = date('Y-m-d',strtotime($sign_date));
	$sql = "SELECT requirement.*, award.title AS award_title, award.type AS award_type FROM requirement, award WHERE requirement.id = $requirement_id AND requirement.award_id = award.id";
	$req_data = fetch_data($sql,'scouts');
	if (!$req_data)
	{
		notify_administrator('Cannot sign requirement - requirement id '.$requirement_id.' not found');	
	}
	// validate that merit badge id is set (or not set) correctly for this requirement
	if ($merit_badge_id)
	{
		if ($req_data['req_type'] != 'Merit Badge')
			notify_administrator('Merit badge id is set, requirement is not a merit badge.  req_data='.var_export($req_data,true).', merit_badge_id='.$merit_badge_id);
	}
	else
	{
		if ($req_data['req_type'] == 'Merit Badge')
			notify_administrator('Merit badge id is not set, requirement is a merit badge.  req_data='.var_export($req_data,true).', merit_badge_id='.$merit_badge_id);
	}

	if ($fulfilled = hasUserFulfilledRequirement($requirement_id, $user_id, $user_data))
	{
// RR - commented this out for group passoffs	- this should not be an error
//		$error_msg = "Cannot pass off requirement ".$req_data['user_number'].
//		             " for ".$req_data['award_title']." ".$req_data['award_type'].".  ".
//		             get_name($user_id).' has already passed off this requirement';
//		return false;
		if(!$user_data)
		{
			$error_msg .= ' error in if ';
		}
	}
	if($fulfilled && !$user_data)
	{
		$error_msg .= ' error out of if ';
	}
	
	if ($GLOBALS['write_debug_log'])
		write_log('signing off requirement (req_id='.$requirement_id.', user_id='.$user_id.')');
	if ($user_data || $fulfilled)
		$sql = "UPDATE user_req SET signed_date = '$sign_date', signed_by = $signed_by, merit_badge_id = $merit_badge_id WHERE user_id = $user_id AND req_id = $requirement_id";
	else
		$sql = "INSERT INTO user_req(signed_date, signed_by, user_id, req_id, merit_badge_id) VALUES('$sign_date',$signed_by,$user_id,$requirement_id,$merit_badge_id)";
	
	if ($sql)
	{
		execute_query($sql,'scouts');
	}
	
	$parent_id = $req_data['parent_id'];
	
	if ($parent_id)
	{
		CascadeRequirementSignOff($parent_id, $user_id, $signed_by, $error_msg, true);
	}
	
	// need to check to see if the award is passed off now...	
	CascadeAwardSignOff($req_data['award_id'], $user_id, $signed_by, &$error_msg);
}

function CascadeAwardSignOff($award_id, $user_id, $signed_by, &$error_msg)
{
	$sql = 'SELECT * FROM user_award WHERE user_id = '.$user_id.' AND award_id = '.$award_id;
	$award_data = fetch_data($sql);	
	
	// An award is passed off when all of its requirements are passed off
	// This query will tell us if there are any missing requirements for this user to have this award
	$sql = 'SELECT COUNT(*) FROM requirement '.
	       'LEFT JOIN user_req ON (requirement.id = user_req.req_id AND user_req.user_id = '.$user_id.') '.
	       'WHERE requirement.parent_id = 0 AND requirement.award_id = '.$award_id.
	       ' AND requirement.req_type != \'Comment\''.
	       ' AND (user_req.req_id IS NULL OR user_req.signed_by = 0)';
	       
	write_log('CascadeAwardSignOff(award_id='.$award_id.', user_id='.$user_id.', signed_by='.$signed_by."), checking for missing requirements.\n  sql=$sql");
	$missing_reqs = intval(do_query($sql));
	
	//if ($GLOBALS['write_debug_log'])
	//	write_log('CascadeAwardSignOff(award_id='.$award_id.'), missing reqs = '.$missing_reqs);
	
	if ($missing_reqs == 0 and (!$award_data or $award_data['signed_by'] == '0'))
	{
		$sign_date = do_query("SELECT MAX(signed_date) FROM user_req, requirement WHERE requirement.id = req_id AND parent_id = 0 AND user_id = $user_id AND requirement.award_id = ".$award_id,'scouts');
		if ($GLOBALS['write_debug_log'])		
			write_log('calling sign off award(award_id='.$award_id.', user_id='.$user_id.', signed_by='.$signed_by.', sign_date='.$sign_date.')');
		SignOffAward($award_id, $user_id, $sign_date, $signed_by, $error_msg);
	}
	else if ($missing_reqs > 0 and ($award_data and $award_data['signed_by'] != '0'))
	{
		if ($GLOBALS['write_debug_log'])
			write_log('calling erase award(award_id='.$award_id.', user_id='.$user_id.', signed_by='.$signed_by);
		EraseAward($award_id, $user_id, $signed_by, $error_msg);
	}
}

function SignOffAward($award_id, $user_id, $sign_date, $signed_by, &$error_msg)
{
	if (hasUserCompletedAward($award_id, $user_id, $user_data))
		return;
	
	if ($user_data)
		$sql = "UPDATE user_award SET signed_date = '$sign_date', signed_by = $signed_by WHERE user_id = $user_id AND award_id = $award_id";
	else
		$sql = "INSERT INTO user_award(signed_date, signed_by, user_id, award_id) VALUES('$sign_date',$signed_by,$user_id,$award_id)";

	execute_query($sql,'scouts');
	
	// if this award is a merit badge... we should find and sign off a requirement now
	
	$sql = 'SELECT * FROM award WHERE id = '.$award_id;
	$award_data = fetch_data($sql,'scouts');
	if ($award_data['type'] == 'Merit Badge')
	{
		// look for a merit badge requirement of the same type first 
		// (if this is a required merit badge, look for a spot for a required merit badge)
		$is_rqd_mb = $award_data['is_rqd_mb'];
		// There are a few required merit badges that are part of a group of merit badges
		// (You have to earn cycling, hiking, OR swimming.  If you earn cycling, when 
		//  you get hikig it doesn't count as a required mb.  Let's check for that here.
		if ($is_rqd_mb == 'T')
		{
			$sql = '';
			if ($award_data['title'] == 'Emergency Prepardness' or // Only one of these two is required
			    $award_data['title'] == 'Lifesaving')
				$sql = 'SELECT id FROM award WHERE title IN(\'Emergency Prepardness\',\'Lifesaving\')';
			if ($award_data['title'] == 'Cycling' or               // Only one of these three is required
			    $award_data['title'] == 'Hiking' or                //
			    $award_data['title'] == 'Swimming')                //
				$sql = 'SELECT id FROM award WHERE title IN(\'Cycling\',\'Hiking\',\'Swimming\')';
			if ($sql)
			{
				$ids = array_field(fetch_array_data($sql,'scouts'),'id');
				$sql = 'SELECT COUNT(*) FROM user_req WHERE signed_by != 0 AND user_id = '.$user_id.' AND merit_badge_id IN ('.implode(',',$ids).')';//54,115,118)';
				if ($sql and do_query($sql))
					$is_rqd_mb = 'F';
			}
		}
		
		$sql = 'SELECT id FROM requirement '.
		       'LEFT JOIN user_req ON (requirement.id = user_req.req_id AND user_req.user_id = '.$user_id.') '.
		       'WHERE requirement.req_type = \'Merit Badge\''.
		       ' AND requirement.required = \''.$is_rqd_mb.'\''.
		       ' AND (user_req.req_id IS NULL OR user_req.signed_by = 0) '.
		       'ORDER BY award_id, id LIMIT 1';
		$req_id = do_query($sql);
		// user has completed all required merit badges... and is doing more required merit badges still!
		if (!$req_id and $is_rqd_mb == 'T')
		{
			$sql = 'SELECT id FROM requirement '.
			       'LEFT JOIN user_req ON (requirement.id = user_req.req_id AND user_req.user_id = '.$user_id.') '.
			       'WHERE requirement.req_type = \'Merit Badge\''.
			       ' AND requirement.required = \F\''.
			       ' AND (user_req.req_id IS NULL OR user_req.signed_by = 0) '.
			       'ORDER BY award_id, id LIMIT 1';
			$req_id = do_query($sql);	
		}
		if ($req_id)
		{
			if ($GLOBALS['write_debug_log'])
				write_log('SignOffAward(), signing off merit badge requirement (req_id = '.$req_id.', merit badge / award id = '.$award_id.')');
			SignOffRequirement($req_id, $user_id, $sign_date, $signed_by, $error_msg, $award_id);
		}
		else
		{
			// there are not merit badge requirements left to fulfill...
			// we should probably add palms in...  (and they should not be marked as required merit badges)
		}
	}
	else if ($award_data['type'] == 'Rank Advancement')
	{
		UpdateRank($user_id);
	}
}

function UpdateRank($user_id)
{
	$ranks_earned = fetch_array_data('SELECT award.title, rank_num FROM `user_award`, award WHERE user_id = '.$user_id.' AND user_award.award_id = award.id AND award.type = \'Rank Advancement\' AND user_award.signed_by != 0 ORDER BY award.rank_num', 'scouts', 'rank_num');
	$new_rank = 'No Rank';
	for ($i = 0; $i <= 6; $i++)
	{
		if (array_key_exists($i, $ranks_earned))
			$new_rank = $ranks_earned[$i]['title'];
		else
			break;
	}

	$sql = "UPDATE user SET rank = '".$new_rank."' WHERE id = ".$user_id;
	execute_query($sql,'scouts');
	if ($GLOBALS['write_debug_log'])
		write_log('UpdateRank(), rank advancement completed, new user rank = '.$new_rank.', user id = '.$user_id);
}

function CascadeRequirementSignOff($parent_id, $user_id, $signed_by, &$error_msg, $req_added)
{
	$signed_off = hasUserFulfilledRequirement($parent_id, $user_id, $urdata);
	
	// a child requirement was just set to true and this is already signed off... no need to sign it off again
	if ($req_added and $signed_off)
		return;
	// a child requirement was just set to false and this isn't already signed off... no need to continue
	if (!$req_added and !$signed_off)
		return;
	
	$sql = 	"SELECT * FROM requirement WHERE id = ".$parent_id;
	$parent_data = fetch_data($sql,'scouts');
	// In order for a parent requirement (with id = x) to be met, the
	// following must be true:
	//   1.  All requirements with parent id x that are required are passed off
	//   2.  If n_required for requirement with id x is not zero, then at least
	//       n_required are passed off.  (this includes required and optional requirements)
			
	//$sql = "SELECT * FROM requirement LEFT JOIN user_req ON requirement.id = user_req.req_id WHERE requirement.parent_id = ".$req_data['parent_id']." AND user_req.user_id = ".$user_id;
			
	// this version of mysql doesn't support select statements as 'where in (select ...)
	$sql = "SELECT req_id ".
	       "FROM user_req, requirement ".
	       "WHERE user_id = $user_id".
	       " AND req_id = requirement.id".
	       " AND requirement.parent_id = $parent_id".
	       " AND user_req.signed_by != 0";
	$user_reqs = array_field(fetch_array_data($sql,'scouts'),'req_id');
	if(count($user_reqs))
	{
		$sql = "SELECT id FROM requirement WHERE parent_id = $parent_id AND required = 'T' AND id NOT IN (".join(',',$user_reqs).") AND requirement.req_type != 'Comment'";
		$unfulfilled_required_reqs = fetch_array_data($sql,'scouts');
	}
	if (count($unfulfilled_required_reqs) == 0)
	{
		// condition 1 is true
		if ($parent_data['n_required'] > 0)
		{
			$sql = "SELECT COUNT(req_id) FROM user_req, requirement WHERE parent_id = $parent_id AND req_id = requirement.id AND user_id = $user_id AND signed_by != 0";
			$passed_off_count = do_query($sql,'scouts');
		}
		if ($parent_data['n_required'] == 0 or ($passed_off_count >= $parent_data['n_required']))
		{
			 $parent_sign_date = do_query("SELECT MAX(signed_date) FROM user_req, requirement WHERE id = req_id AND parent_id = $parent_id AND user_id = $user_id",'scouts');
			// condition 2 is true
			$parent_requirement_met = true;
		}
	}
	
	if (!$signed_off and $parent_requirement_met)
		SignOffRequirement($parent_id, $user_id, $parent_sign_date, $signed_by, $error_msg);
	else if ($signed_off and !$parent_requirement_met)
		EraseRequirement($parent_id, $user_id, $signed_by, $error_msg);
}

function getUserPassOffCount($requirement_id, $user_list)
{
	$pass_count = 0;
	foreach ($user_list as $user_id)
	{
		$data = $GLOBALS['user_reqs'][$user_id][$requirement_id];
		$data_passed_in = true; // makes the page load SIGNIFICANTLY faster
		if (hasUserFulfilledRequirement($requirement_id, $user_id, $data, $data_passed_in))
			$pass_count++;
	}
	return $pass_count;
}

function add_children($parent_id, $requirements, &$result)
{
	foreach ($requirements as $req)
	{	
		if ($req['parent_id'] == $parent_id)
		{
			$result[$req['id']] = $req;
			add_children($req['id'], $requirements, $result);		
		}
	}
}

function GetRequirements($award_id, $include_comments = true)
{
	$sql = 'SELECT * FROM requirement WHERE award_id = '.$award_id.($include_comments ? '' : ' AND req_type != \'Comment\'').' ORDER BY parent_id, number';
	$reqs = fetch_array_data($sql,'scouts','id');
	add_children(0, $reqs, $requirements);
	return $requirements;
}

function UpdateRequirementNumbers($award_id)
{
	$reqs = NumberRequirements($award_id);
	connect_db('scouts');
	foreach ($reqs as $req_id => $data)
	{
		$sql = "UPDATE requirement SET user_number ='".$data['n']."' WHERE id = ".$req_id;
		execute_query($sql);
	}
}

function UpdateAllRequirementNumbers($type=null)
{
	$awards = fetch_array_data("SELECT id FROM award".($type ? ' WHERE type = \''.$type.'\'':''),'scouts');
	foreach ($awards as $data)
	{
		UpdateRequirementNumbers($data['id']);	
	}
}

//UpdateAllRequirementNumbers();

function NumberRequirements($award_id, $parent_req_id = 0, $parent_number = 0, $depth = 0)
{
  $sql = 'SELECT * FROM requirement WHERE award_id = '.$award_id.' AND parent_id = '.$parent_req_id.' ORDER BY number';
	$requirements = fetch_array_data($sql,'scouts','id');
	$results = Array();
	//debug($requirements);
	
	$count = 0;
	foreach ($requirements as $key => $requirement)
	{
		if ($requirement['req_type'] == 'Comment')
			continue;
			
		if ($depth == 0)
			$number = $count + 1;
		else if ($depth == 1)
			$number = $parent_number . chr($count + 70 + 26 + 1);
		else if ($depth == 2)
			$number = $parent_number .'-'. ($count + 1);
		else
			$number = $parent_number .'.'. ($count + 1);
			
		$results[$key] = $requirement;
		$results[$key]['description'] = nl2br($requirement['description']);
		$results[$key]['n'] = $number;
		
		$sub_req = NumberRequirements($award_id, $requirement['id'], $number, $depth + 1);
		if (count($sub_req))
		{
			foreach ($sub_req as $key => $val)
				$results[$key] = $val;
			//$results = array_merge($results, $sub_req);
		}
		
		$count++;
	}
	return $results;
}

function WriteAwardJS($award_id, $user_list, $user_id)
{
    // writes javascript info about award -
    //   all award details, including requirements and users who have passed off those requirements
  	$award_data = fetch_data('SELECT * FROM award WHERE id = '.$award_id,'scouts');
	
	$requirements = GetRequirements($award_id, $include_comments = false);
	// fields being set currently...  (all fields)
	//'user_id' : '6',
	//'req_id' : '1',
	//'completed_date' : '2005-05-17',
	//'signed_date' : '2005-05-17',
	//'signed_by' : '1',
	//'id' : '1',
	//'award_id' : '1',
	//'number' : '1',
	//'description' : 'Present yourself to your leader, properly dressed, before going on an overnight camping trip. Show the camping gear you will use. Show the right way to pack and carry it.',
	//'parent_id' : '0',
	//'n_required' : '0',
	//'required' : 'T'},
	
	$user_info = fetch_array_data('SELECT id, name, "true" AS checked FROM user WHERE id IN ('.implode(',',$user_list).') ORDER BY name','scouts','id');
	
	$sql = 'SELECT user_req.* FROM user_req, requirement '.
	       'WHERE user_req.req_id = requirement.id'.
	       ' AND requirement.award_id = '.$award_id.
	       ' AND user_req.user_id IN ('.implode(',',$user_list).')'.
	       ' AND user_req.signed_by != 0';
	$user_reqs = fetch_array_data($sql,'scouts');
	$req = Array();
	foreach ($user_reqs as $ur)
	{
		$req[$ur['user_id']][$ur['req_id']] = $ur;
	}
	
	$sm_list = fetch_array_data('SELECT u1.id, u1.name FROM user AS u1, user AS u2 WHERE u2.id = '.$user_id.' AND u1.scout_troop_id = u2.scout_troop_id AND (u1.id = '.$user_id.' OR u1._scoutmaster = \'T\') ORDER BY u1.name','scouts','id');
	//$mb_list = fetch_array_data('SELECT id, title FROM award WHERE type = \'Merit Badge\'','scouts','id');
	//debug($requirements);
	echo "\n<script type=\"text/javascript\">\n";
	echo "user_count = ".count($user_list).";\n";
	echo WritePHPVarInJS($requirements, 'requirements').";\n";
	//echo WritePHPVarInJS($mb_list, 'merit_badges').";\n";
	echo WritePHPVarInJS($award_data, 'award_data').";\n";
	echo WritePHPVarInJS($user_info, 'user_info').";\n";
	echo WritePHPVarInJS($req,'user_reqs').";\n";
	echo WritePHPVarInJS($user_id, 'active_user_id').";\n";
	echo WritePHPVarInJS(get_name($user_id),'active_user_name').";\n";
	echo WritePHPVarInJs($_SESSION['last_sign_date'] ? $_SESSION['last_sign_date'] : date('m/d/Y'),'default_date').";\n";
	echo WritePHPVarInJs($sm_list,'scoutmasters').";\n";
	echo WritePHPVarInJs(true,'allowProxySigning').";\n";
	echo "</script>\n";
}

function DisplayRequirements($all_reqs, $can_edit = false, $user_list = null, $parent_req_id = 0, $depth = 0)
{
	$rtext = '';	
	if ($can_edit and $parent_req_id == 0 and is_array($user_list))
	{
		$rtext .= '<DIV id=editDiv style="BORDER: black 1px solid; PADDING: 0px; FONT-SIZE: 10pt; LEFT: 0px; VISIBILITY: hidden; FONT-FAMILY: sans-serif; POSITION: absolute; TOP: 0px; COLOR: black; BACKGROUND-COLOR: #eee; layer-background-color: #eee"></div>'."\n";
//		$user_name = do_query('SELECT name FROM user WHERE id = '.$user_id,'scouts');
//		$rtext .= "<script type=\"text/javascript\">\n";
//		$rtext .= "var user_name = '$user_name';\n";
//		$rtext .= "var user_id = $user_id;\n";
//		$rtext .= "</script>\n";
		//if ($_SESSION['USER_ID'] == 1)
		//$rtext .= "<script type=\"text/javascript\" src=\"scripts/scout_requirements2_debug.js\"></script>\n";
		//else
		$rtext .= "<script type=\"text/javascript\" src=\"scripts/scout_requirements2.js\"></script>\n";
	}
//	$award_data = fetch_data('SELECT * FROM award WHERE id = '.$award_id,'scouts');
//	$sql = 'SELECT * FROM requirement WHERE award_id = '.$award_id.' AND parent_id = '.$parent_req_id.' ORDER BY number';
//	$requirements = fetch_array_data($sql,'scouts');
	$requirements = get_sub_reqs($all_reqs, $parent_req_id);
	if (count($requirements))
	{
		if ($depth == 0)
			$ls = 'list-style: decimal;';
		else if ($depth == 1)
			$ls = 'list-style: lower-alpha;';
		else // should need to go here...
			$ls = 'list-style: lower-roman;';
		$rtext .= "\n<ol style=\"$ls\">\n";
		$count = 0;
		$onClick = '';
		$end_comment = '';
		foreach ($requirements as $requirement)
		{
			$req_title = '';
			$pass_off_description = '';
				
			if (is_array($user_list))
			{
				$separator = '';
				foreach ($user_list as $user_id)
				{
					if (isset($GLOBALS['user_reqs'][$user_id][$requirement['id']]))
					{
						$info = $GLOBALS['user_reqs'][$user_id][$requirement['id']];
						$sign_date = date('m/d/Y',strtotime($info['signed_date']));
						if (count($user_list) == 1)
							$pass_off_description .= 'Signed by '.$info['signed_by_name'].' on '.$sign_date;
						else
						{
							$pass_off_description .= $separator . $info['scout_name'];
							$separator = ' | ';
//							$req_title .= $info['scout_name'].' (signed by '.$info['signed_by_name'].' on '.$sign_date.")\n";
						}
					}
				}
			}
			
			if ($pass_off_description or $requirement['req_type'] == 'Merit Badge' or $_SESSION['USER_ID'] == 1 or $_SESSION['USER_ID'] == 8)
			{
				$req_title .= ' title="';
				if ($_SESSION['USER_ID'] == 1 or $_SESSION['USER_ID'] == 8)
					$req_title .= 'ID=['.$requirement['id'].'], Rqd=['.$requirement['required'].'], # Rqd=['.$requirement['n_required'].'] ';
				if ($requirement['req_type'] == 'Merit Badge')
					$req_title .= 'To sign off merit badges requirements, sign off Merit Badges individually through the menu at the left and the requirements will automatically be passed off in this section.';
				if ($pass_off_description)
					$req_title .= $pass_off_description;
				$req_title .= '"';
			}
			
			if ($requirement['req_type'] == 'Comment')
			{
				// This end_comment thing is to workaround the fact that IE (even IE 7) ignores
				// </li> end tags.  In other words it is not possible to separate the last requirement
				// from a comment immediately following it.
				if (count($requirements) != $count + 1)
					$rtext .= '<div>'.nl2br($requirement['description']).'</div>'."\n";
				else
					$end_comment = '<div>'.nl2br($requirement['description']).'</div>'."\n";
			}
			else
			{
				$sub_req = DisplayRequirements($all_reqs, $can_edit, $user_list, $requirement['id'], $depth + 1);
				if (!$sub_req)// and $requirement['req_type'] != 'Merit Badge')
				{
					if ($can_edit)
					{
						$onClick = " onClick=\"ClickReq(".$requirement['id'].", this);\"";
					}
					$rtext .= '<li'.$req_title.' class="req_edit"'.$onClick.' id="li_'.$requirement['id'].'">'; // onMouseEnter="style.color=\'red\';"
				}
				else
				{
					$rtext .= '<li'.$req_title;
					$rtext .= ' class="requirement" id="li_'.$requirement['id'].'">';
				}
			
				$req_text = '&nbsp;&nbsp;&nbsp;';
				if ($user_list)
				{
					$pass_offs = getUserPassOffCount($requirement['id'], $user_list);
					if (count($user_list) == 1)
					{
						if ($pass_offs == 1)
						{
							//debug($requirement);
							if ($requirement['req_type'] == 'Merit Badge')
								$req_text = '&nbsp;'.do_query('SELECT title FROM award, user_req WHERE award.id = user_req.merit_badge_id AND user_req.user_id = '.$user_list[0].' AND req_id = '.$requirement['id']).'&nbsp;';
							else
								$req_text = '&nbsp;x&nbsp;';
						}
					}
					else
					{
						if ($pass_offs == count($user_list))
							$req_text = '&nbsp;All&nbsp;';
						else if ($pass_offs > 0)
							$req_text = '&nbsp;('.$pass_offs.')&nbsp;';
					}
				}
				//pre_print_r($req_text);
				$rtext .= '<u>'.$req_text.'</u> ';
				
				$rtext .= nl2br($requirement['description']);
				if(!$sub_req)
					$rtext .= "</li>\n";
				$rtext .= $sub_req;
				//echo '<tr><td width="'.($depth * 80).'"></td><td>'.$requirement['number']
				$count++;
			}
		}
		$rtext .= "</ol>\n";
		if($depth == 1)
			$rtext .= "</li>\n";
		if ($end_comment)
			$rtext .= $end_comment;
	}
	return $rtext;
}


function PostDataValid(&$error_msg)
{
	if (!isset($_POST['target']))
	{
		$error_msg = 'Error: post[target] not set';
		return false;
	}
	if ($_POST['target'] == 'new_award')
	{
		if (strlen($_POST['title']) < 1)
			$error_msg = 'Please enter a title';
		if (do_query('SELECT id FROM award WHERE title = \''.addslashes($_POST['title']).'\'','scouts'))
			$error_msg = 'This award has already been created.';
	}
	else if ($_POST['target'] == 'edit_requirement')
	{
		if (strlen($_POST['description']) < 5)
			$error_msg = 'Please enter a description';
		else if (strlen($_POST['number']) < 1)
			$error_msg = 'Please enter a number (1, 2, 3, etc.)';
		else if (isset($_POST['is_sub_req']) and strlen($_POST['sub_number']) < 1)
			$error_msg = 'Please enter the sub requirement number (a, b, c, etc.)';
		
		$req_id = do_query('SELECT id FROM requirement WHERE award_id = '.$_GET['award_id']." AND number = '".addslashes($_POST['number'])."' AND parent_id = 0",'scouts');
		if ($req_id)
		{
			$sql = "SELECT id FROM requirement WHERE award_id = ".$_GET['award_id'];
			if (isset($_POST['is_sub_req']))
				$sql .= " AND parent_id = $req_id AND number = '".addslashes($_POST['sub_number'])."'";
			else
				$sql .= " AND parent_id = 0 AND number = '".addslashes($_POST['number'])."'";
			if (isset($_POST['requirement_id']))  // is this an update?
				$sql .= " AND id != ".$_POST['requirement_id'];
			debug($sql);
			if (do_query($sql,'scouts'))
				$error_msg = 'This requirement number is already used.  Please use a different number.';
		}
	}
	else
	{
		return false;
	}
	
	if ($error_msg)
		return false;
	return true;
}

function HandlePost(&$error_msg)
{
	if (isset($_SESSION['FORM_GUIDS'][$_POST['form_guid']]))
	{
		// Error, form posted twice.
		$error_msg = 'Error, form posted twice.'; 
	}
	else
	{
		$_SESSION['FORM_GUIDS'][$_POST['form_guid']] = true;
		if ($_POST['target'] == 'new_award')
		{
			$sql = "INSERT INTO award(title,type) VALUES('".addslashes($_POST['title'])."','".$_POST['award_type']."');";
			execute_query($sql,'scouts');
			$_GET['award_id'] = mysql_insert_id();  // so that we will view this award when the page comes up...
		}
		else if ($_POST['target'] == 'edit_requirement')
		{
			assert(isset($_GET['award_id']) and $_GET['award_id']);
			//debug($_POST);
			if (PostDataValid($error_msg))
			{
				$parent_id = 0;
				$number = addslashes($_POST['number']);
				if (isset($_POST['is_sub_req']))
				{
					$sql = 'SELECT id FROM requirement WHERE award_id = '.$_GET['award_id']." AND number = '$number' AND parent_id = 0";
					$parent_id = do_query($sql,'scouts');
					if (!$parent_id)
					{
						$sql = "INSERT INTO requirement(award_id,number,user_number,description,parent_id) VALUES(".$_GET['award_id'].",'$number','?','',0)";
						execute_query($sql,'scouts');
						$parent_id = mysql_insert_id();
					}
					$number = addslashes($_POST['sub_number']);
				}
				//$_POST['requirement_id']
				$sql = "INSERT INTO requirement(award_id,number,user_number,description,parent_id) VALUES(".$_GET['award_id'].",'$number','?','".addslashes($_POST['description'])."',$parent_id)";
				execute_query($sql,'scouts');
				UpdateRequirementNumbers($_GET['award_id']);
			}
		}
	}
}

function ShowCreateAward($award_type)
{
	echo '<center>';
	echo '<h2>New '.$award_type.'</h2>';
	echo '<form method="POST" action="requirements.php">';
	echo '<input type="hidden" name="form_guid" value="'.uniqid().'">';
	echo '<input type="hidden" name="target" value="new_award">';
	echo '<input type="hidden" name="award_type" value="'.$award_type.'">';
	echo '<table>';
	echo '<tr><td>Award Title</td><td><input type="text" id="title" name="title" value=""></td></tr>';
	echo '<tr><td colspan="2" align="center"><input type="submit" value="Submit"></td></tr>';
	echo '</table>';
	echo '</form>';
	echo '</center>';
}

function GetAwardVersionCount($award_id_name)
{
	global $award_version_count;
	if (!is_array($award_version_count))
	{
		$sql = 'SELECT title, COUNT(id) AS award_count FROM award GROUP BY title';
		$award_version_count = fetch_array_data($sql,'scouts','title');
		//debug('getting award versions...');
	}
	
	if (is_numeric($award_id_name))
	{
		global $award_id_titles;
		if (!is_array($award_id_titles))
		{
			$sql = 'SELECT id, title FROM award';
			$award_id_titles = fetch_array_data($sql,'scouts','id');
			//debug('getting award titles...');
			//debug($award_id_titles);
		}
		$award_id_name = $award_id_titles[$award_id_name]['title'];
	}
	
	if (array_key_exists($award_id_name, $award_version_count))
		return $award_version_count[$award_id_name]['award_count'];
	else
		return 0;
}

function GetAwardVersionsInProgress($award_title, $user_list)
{
	// returns an array of awards ids and the number of scouts working on each award id
		$sql = 'SELECT id, req_revision FROM award WHERE title = \''.addslashes($award_title).'\' ORDER BY req_revision DESC';
		//$potential_award_versions = array_keys(fetch_array_data($sql,'scouts','id'));
		$potential_award_versions = fetch_array_data($sql,'scouts','id');
		
		/*$sql = 'SELECT requirement.award_id, COUNT(DISTINCT(user_req.user_id)) AS ucount FROM user_req, requirement '.
		       'WHERE user_req.user_id IN ('.implode(',',$user_list).')'.
		       ' AND user_req.req_id = requirement.id'.
		       ' AND requirement.award_id IN ('.implode(',',$potential_award_versions).') GROUP BY requirement.award_id ORDER BY ucount';
		//debug($sql);
		$user_count = fetch_array_data($sql,'scouts', 'award_id');*/
		//debug($potential_award_versions);
		foreach ($potential_award_versions as $key => $version)
		{
			if (is_array($user_list) and count($user_list))
			{
				$sql = 'SELECT DISTINCT user_req.user_id '.
				       'FROM user_req, requirement '.
				       'WHERE user_req.user_id IN ('.implode(',',$user_list).')'.
				       ' AND user_req.req_id = requirement.id'.
				       ' AND requirement.award_id = '.$version['id'].
				       ' AND user_req.signed_by != 0';
				$potential_award_versions[$key]['users'] = array_field(fetch_array_data($sql,'scouts'),'user_id');
			}
			else
			{
				$potential_award_versions[$key]['users'] = Array();
			}
		}
		
		//debug($potential_award_versions);
		//return $versions;
		return $potential_award_versions;
}

function GetAwardVersionTable($award_title, $user_list, $extra_url_params, &$selected_user_list, &$selected_award_id, $award_year=null)
{
	$award_versions = GetAwardVersionsInProgress($award_title, $user_list);
	
	//debug('user_list='.implode(',',$user_list));
	//debug($award_versions);
	
	$not_working_on_award = $user_list;
	
	$newest_id = 0;
	$newest_in_progress = 0;
	$explicit_id = 0;
	
	$in_progress_count=0;
	
	foreach ($award_versions as $award_id => $info)
	{
		if ($info['req_revision'] == $award_year)
			$explicit_id = $award_id;
		if ($newest_id == 0)
			$newest_id = $award_id;
		if (is_array($info['users']))
		{
			$in_progress_count++;
			foreach ($info['users'] as $user_id)
			{
				if ($newest_in_progress == 0)
					$newest_in_progress = $award_id;
				foreach ($not_working_on_award as $key => $id)
				{
					if ($id == $user_id)
						unset($not_working_on_award[$key]);
				}
			}
		}
	}
	
	if ($explicit_id != 0)
		$selected_award_id = $explicit_id;
	else if ($newest_in_progress != 0)
		$selected_award_id = $newest_in_progress;
	else if ($newest_id != 0)
		$selected_award_id = $newest_id;
	else
		notify_administrator('Unable to find default award id for award "'.$award_title.'"');
	
	//debug('not working on award='.implode(',',$not_working_on_award));
	foreach ($not_working_on_award as $id)
		$award_versions[$newest_id]['users'][] = $id;

	$assigned_award_group_count = 0;
	foreach ($award_versions as $award_id => $info)
	{
		if (is_array($info['users']) and count($info['users']))
			$assigned_award_group_count++;
	}
	/*
	debug($award_versions);
	debug('newest in progress='.$newest_in_progress.', newest='.$newest_id.', explicit='.$explicit_id.', award_id='.$award_id);
	debug('in_progress_count='.$in_progress_count);*/
	
	//if ($assigned_award_group_count > 1)
	{
		$text = '<table style="color: black">';//<tr><td>Year</td><td></td></tr>';
		foreach ($award_versions as $award_id => $info)
		{
			if (is_array($info['users']) and count($info['users']) > 0 and $assigned_award_group_count != 1)
				$revision_info = $info['req_revision'].' - '.implode(', ',array_field(fetch_array_data('SELECT name FROM user WHERE id IN('.implode(',',$info['users']).')'),'name'));
			else
				$revision_info = 'Requirement Version '.$info['req_revision'];
			if ($selected_award_id == $award_id)
			{
				$selected_user_list = $info['users'];
				$text .= '<tr>'.($assigned_award_group_count > 1 ? '<td nowrap>--></td>':'').'<td style="font-weight: bold;">'.$revision_info.'</td></tr>';
			}
			else if (is_array($info['users']) and count($info['users']))
			{
				$text .= '<tr>'.($assigned_award_group_count > 1 ? '<td>&nbsp;</td>':'').'<td><a href="requirements.php?award_id='.$award_id.($_GET['req_view']?'&req_view='.$_GET['req_view']:'').'&revision_year='.$info['req_revision'].$extra_url_params.'">'.$revision_info.'</a></td></tr>';
			}
		}
		$text .= '</table>';
	}
	return $text;
}

function ShowAwardRequirements($award_id, $can_edit, $can_add_reqs, $show_edit_requirement, $user_id, $user_list = null, $award_year = null)
{
	echo '<div class="scout_report">';
	
	$sql = 'SELECT * FROM award WHERE id = '.$award_id;
	$data = fetch_data($sql,'scouts');
	$is_rqd_mb = ($data['type'] == 'Merit Badge' and $data['is_rqd_mb'] == 'T');
	echo '<center><h2>'.$data['title'].($is_rqd_mb ? '*' : '').' '.$data['type'].' Requirements';
	
	if ($data['type'] == 'Merit Badge') 
	{
	  // For now, we are only keeping multiple versions of merit badges in the database.
		$selected_award_id = null;
		$selected_user_list = Array();
		$award_version_table = GetAwardVersionTable($data['title'], $user_list, '', $selected_user_list, $selected_award_id, $award_year);
			// user_list can change after the call to GetAwardVersionTable...
		$user_list = $selected_user_list;
		if ($selected_award_id != $award_id)
		{
			$award_id = $selected_award_id;
			$sql = 'SELECT * FROM award WHERE id = '.$award_id;
			$data = fetch_data($sql,'scouts');
		}
	}
	else
	{
		$award_version_table = '';
	}
	
	if (count($user_list) == 1)
	{
		echo '<br>for '.do_query('SELECT name FROM user WHERE id = '.$user_list[0],'scouts');
		//pre_print_r($data);
		$user_award_info = fetch_data('SELECT user_award.* FROM user_award WHERE user_award.user_id = '.$user_list[0].' AND user_award.award_id = '.$award_id,'scouts');
		//pre_print_r($user_award_info);
		//echo '<br> for 
		if ($user_award_info['signed_by'])
			echo '.  Completed '.$user_award_info['signed_date'];
	}
	if ($is_rqd_mb)
		echo '<br><small>* Required to earn Eagle rank advancement</small>';
	echo '</h2>'."\n";
	
	echo $award_version_table."\n</center>\n";
	
	// Display all requirements.  (if user is logged in, allow them to edit each requirement), allow them to delete requirements if no one has earned them.
	if ($can_edit && count($user_list))
	    WriteAwardJS($award_id, $user_list, $user_id);

	if (is_array($user_list) and count($user_list) and !isset($GLOBALS['user_reqs']))
	{
		$sql = 'SELECT user_req.*, u1.name as signed_by_name, u2.name as scout_name FROM user_req, requirement, user AS u1, user AS u2 '.
			       'WHERE user_req.req_id = requirement.id'.
			       ' AND requirement.award_id = '.$award_id.
			       ' AND user_req.user_id IN ('.implode(',',$user_list).')'.
			       ' AND user_req.signed_by != 0'.
			       ' AND user_req.user_id = u2.id'.
			       ' AND user_req.signed_by = u1.id';
			       
		$ureqs = fetch_array_data($sql,'scouts');
		foreach ($ureqs as $ureq)
		{
			$GLOBALS['user_reqs'][$ureq['user_id']][$ureq['req_id']] = $ureq;
		}
	}
	
	$sql = 'SELECT * FROM requirement WHERE award_id = '.$award_id.' ORDER BY number';
	$all_reqs = fetch_array_data($sql,'scouts');
	$rtext = DisplayRequirements($all_reqs, $can_edit, $user_list);
	if (!$rtext)
	{
		echo 'No requirements are entered for this award.';
	}
	else
	{
		if ($can_edit)
		{
			echo '<input type="button" value="Sign Off Reqs" id="button_sign_off_reqs" onClick="clickSignOff()">';
			echo ' &nbsp; <input type="button" value="Mark All" id="button_mark_all" onClick="clickMarkAll()">';
			//if ($_SESSION['USER_ID'] == 1) // need to remove this once it's working good
			echo ' &nbsp; <input type="button" value="Quick Fill" id="button_quick_fill" onClick="clickQuickFill()">';
			if ($_SESSION['USER_ID'] == 1)
			echo ' &nbsp; <input type="button" value="Make Optional" id="button_make_optioal" onClick="clickMakeOptional()">';
		}
		echo $rtext;
	}
	// If user is logged in, give them the ability to add a new requirement
	if ($can_add_reqs)
	{
		if ($show_edit_requirement)
			$display = 'block';
		else
			$display = 'none';
		if ($display == 'none')
		{
			echo '<br><div style="cursor: pointer; color: red;" onClick="document.getElementById(\'new_req_div\').style.display = \'block\'; innerHTML = \'<h3>New Requirement Details</h3>\'; document.getElementById(\'number\').focus();">[ Add Requirement ]</div>'; //style.color=\'white\';
			echo '<div id="new_req_div" style="display: none;">';
		}
		else
		{
			echo '<h3>New Requirement Details</h3>';
		}
		echo '<form method="POST" action="requirements.php?award_id='.$award_id.'">';
		echo '<input type="hidden" name="form_guid" value="'.uniqid().'">';
		echo '<input type="hidden" name="target" value="edit_requirement">';
		//echo '<input type="hidden" name="parent_id" value="0">';
		echo '<table border=0><tbody style="color: black;">';
		echo '<tr><td>Number</td><td><table><tr><td><input size="10" type="text" id="number" name="number" value="'.($show_edit_requirement ? $_POST['number'] : '').'">';
		echo '</td><td style="color: black;">  (<input type="checkbox" name="is_sub_req" onClick="checkSubRequirement(checked)"> Sub-requirement </td><td style="color: black;"><input size="10" type="text" style="display: none;" name="sub_number" id="sub_number"></td><td style="color: black;">)</td>';
		echo '</tr></tr></table></td></tr>';
		echo '<tr><td>Description</td><td><textarea name="description" rows="5" cols="60">'.($show_edit_requirement ? $_POST['description'] : '').'</textarea></td></tr>';
		echo '<tr><td colspan="2"><input type="submit" value="Submit"></td></tr>';
		echo '</tbody></table>';
		echo '</form>';
		if ($display == 'none')
		{
			echo '</div>';
			echo '</div>';
		}
	}
	
	echo '</div>';
}

//UpdateRequirementNumbers(115); // swimming

function ShowAwardTable($awards, $users, $award_data, $title, $is_scoutmaster = false, $allow_updates = false)
{
	if($_GET['show_completed'])
		$_SESSION['show_completed']=$_GET['show_completed'];
	if (!$_SESSION['show_completed'])
		$_SESSION['show_completed']='T';

	if($_GET['show_presented'])
		$_SESSION['show_presented']=$_GET['show_presented'];
	if (!$_SESSION['show_presented'])
		$_SESSION['show_presented']='T';
		
	$include_images = ($title != 'Merit Badges');
	echo '<div style="padding-left: 30px">';
	echo '<h2>'.$title.'</h2>';
	if (count($users) == 1)
		echo '<h3>'.$users[0]['name'].'</h3>';
	
	$users_no_awards = 0;
	foreach ($awards as $award)
	{
		foreach ($users as $ukey => $user)
		{
			if (array_key_exists($award['id'], $award_data) and array_key_exists($user['id'], $award_data[$award['id']]) and
			    $award_data[$award['id']][$user['id']]['signed_by'])
			{
				if (!array_key_exists('award_count',$user))
					$users[$ukey]['award_count'] = 1;
				else
					$users[$ukey]['award_count']++;
			}
		}
	}
	
	$update_button = '';
	if ($is_scoutmaster and !$allow_updates)
		$update_button = '<button onclick="location=\'/awards.php?action=update\'">Update</button>';
	
	$checkboxes = '<script type="text/javascript">'."\n";
	$checkboxes .= "function changeView(el){\n";
	$checkboxes .= "  location='/awards.php?'+el.name+'='+(el.checked ? 'T' : 'F');\n";
	$checkboxes .= "}</script>\n";
	$checkboxes .= '<input type="checkbox" name="show_completed" '.(($_SESSION['show_completed'] == 'T') ? 'checked' : '').' onClick="changeView(this)"> Show Date Completed<br>';
	$checkboxes .= '<input type="checkbox" name="show_presented" '.(($_SESSION['show_presented'] == 'T' or $allow_updates) ? 'checked' : '').' onClick="changeView(this)"'.($allow_updates ? ' disabled="disabled"':'').'> Show Presented Status<br>';

	echo $checkboxes;
	
	if ($allow_updates)
		echo '<form action="awards.php" method="POST">';
		
	if (count($users) > 1)
	{
		echo '<table border=0 cellpadding=4 cellspacing=0 class="main">';				
		echo '<tr><td colspan='.($include_images ? '2':'1').'>';
		echo $update_button;
		echo '</td>';
		foreach ($users as $user)
		{
			if (!array_key_exists('award_count',$user) or $user['award_count'] == 0)
			{
				$users_no_awards++;
				continue;
			}
			echo '<td class="yellow">';
			echo '<center><img src="/text_image.php?string='.$user['name'].'&amp;background-color=FFFF53&amp;rotate=270&amp;x=5&amp;y=3&font-size=5&amp;width=190&amp;height=20" border=0></center>';
			echo '</td>'."\n";
		}
		echo '</tr>'."\n";
	}
	else
	{
		if (!$_GET['printpage'])
			echo $update_button;
		echo '<table border=0 cellpadding=4 cellspacing=0 class="main">';
	}

	$award_rows = Array();
	foreach ($awards as $award)
	{
		$text = '<tr>';
		
		if ($include_images)
		{
			$text .= '<td rowspan=3 style="background-color: white; border: 1px solid black;">';
			if ($award['type'] == 'Rank Advancement')
				$text .= '<img src="'.$award['img_url'].'" border=0 height=80>';
			else
				$text .= '<img src="'.$award['img_url'].'" border=0 width=70>';
			$text .= '</td>';
		}
		$text .= '<td colspan='.(count($users)+1-$users_no_awards).' style="background-color: '.get_award_color($award['id']).';  color: black; border: 1px solid black;">'.$award['title'].'</td></tr>'."\n";
		
		$award_rows[$award['title']]['head'] = $text;
			
		foreach ($users as $user)
		{
			if (!array_key_exists('award_count',$user) or $user['award_count'] == 0)
			{
				continue;
			}
			
			if (array_key_exists($award['id'], $award_data) and array_key_exists($user['id'], $award_data[$award['id']]) and
			    $award_data[$award['id']][$user['id']]['signed_by'])
			{
				if (!array_key_exists('num_completed', $award_rows[$award['title']]))
					$award_rows[$award['title']]['num_completed'] = 1;
				else
					$award_rows[$award['title']]['num_completed']++;
				
				$award_rows[$award['title']][$user['id']]['completed'] = '<td class=yellow style="width: 50px">'.date('M j, Y',strtotime($award_data[$award['id']][$user['id']]['signed_date'])).'</td>';
				
				if ($allow_updates)
				{
					if (!$GLOBALS['award_state_values'])
						$GLOBALS['award_state_values'] = get_enums('user_award', 'state');
					
					$onChangeHandler = 'var el_id = \'presented_\'+this.id.substr(6); document.getElementById(el_id).disabled = (this.value != \'Presented\');';
					
					$presented = ShowSelectList('state_'.$award['id'].'_'.$user['id'], 
					                            $award_data[$award['id']][$user['id']]['state'], 
					                            $GLOBALS['award_state_values'], $onChangeHandler);
					$presented .= '<br/>';
					$presented .= '<input type="text" size="10" name="presented_'.$award['id'].'_'.$user['id'].'" value="'.$award_data[$award['id']][$user['id']]['presented_date'].'"'.($award_data[$award['id']][$user['id']]['state'] == 'Presented' ? '' : ' disabled').'>';
				}
				else
				{
					if ($award_data[$award['id']][$user['id']]['state'] == 'Presented')
					{
						if (strtotime($award_data[$award['id']][$user['id']]['presented_date']))
							$presented = date('M j, Y',strtotime($award_data[$award['id']][$user['id']]['presented_date']));
						else
							$presented = 'Yes, date unknown';
					}
					else
						$presented = '<span style="color: red;">'.$award_data[$award['id']][$user['id']]['state'].'</span>';
				}
				$award_rows[$award['title']][$user['id']]['presented'] = '<td class=yellow style="width: 50px">'.$presented.'</td>';
				//$presented_row .= '<td>'.date('M j, y',strtotime($award_data[$award['id']][$user['id']]['state'])).'</td>';
			}
		}
	}

	//pre_print_r($award_rows);
	
	foreach ($award_rows as $award_title => $award)
	{
		if ($title == 'Merit Badges' and $award['num_completed'] == 0)
			continue;
			
		echo $award['head'];		
		$completed_row = '';
		$presented_row = '';
		foreach ($users as $user)
		{
			if (!array_key_exists('award_count',$user) or $user['award_count'] == 0)
			{
				continue;
			}
			
			if (array_key_exists($user['id'], $award) and array_key_exists('completed', $award[$user['id']]))
			{
				$completed_row .= $award[$user['id']]['completed'];
				$presented_row .= $award[$user['id']]['presented'];
			}
			else
			{
				$completed_row .= '<td>&nbsp;</td>';
				$presented_row .= '<td>&nbsp;</td>';
			}
		}
		if ($_SESSION['show_completed'] == 'T')
		echo '<tr style="color: black; background-color: #A9EBAE;"><td style="padding-left: 25px; font-size: 80%">Completed</td>'.$completed_row.'</tr>'."\n";
		if ($_SESSION['show_presented'] == 'T' or $allow_updates)
			echo '<tr style="color: black; background-color: #A9EBAE;"><td style="padding-left: 25px; font-size: 80%">Presented</td>'.$presented_row.'</tr>'."\n";		
	}
	echo '</table>'."\n";

	if ($allow_updates)
	{
		echo '<input type="submit" value="Save Changes">';
		echo '</form>';
	}

	
	if ($users_no_awards > 0)
	{
		echo '<br/>The following scouts have not completed any '.strtolower($title).':<br/>';
		echo '<div style="padding-left: 25px">';
		foreach ($users as $user)
		{
			if (!array_key_exists('award_count',$user) or $user['award_count'] == 0)
			{
				echo $user['name'].'<br/>';
			}
		}
		echo '</div>'."\n";
	}
	echo '</div>';
}

?>
