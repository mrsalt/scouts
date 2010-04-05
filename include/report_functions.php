<?php
require_once 'include/globals.php';

function get_report($award_id, $user_id = 0, $group_id = 0, $view = '')
{
	if($_GET['printpage'])
	{
		$rvalue .= '<script type="text/javascript">window.print();</script>';
	}
	else
	{
		$rvalue .= '<a style="float: left;" href="requirements.php?printpage=1&amp;show='.$_GET['show'].'" target="_blank">Print Page</a>';
		if($view != 'summary' && isUser('Scoutmaster'))
		{
			$rvalue .= '<script type="text/javascript" src="scripts/scout_calendar.js"></script>'."\n";
			$rvalue .= '<script type="text/javascript">'."\n";
			$rvalue .= 'document.write(getCalendarStyles());'."\n";
			$rvalue .= 'var cal = new CalendarPopup("calendar_div");'."\n";
			$rvalue .= '</script>'."\n";
			$rvalue .= '<div style="float: right;">';
			$rvalue .= '<input id="selected_date" type="text" value="'.date('n/j/y').'" style="display: none;" /> ';
			$rvalue .= '<a id="calendar_anchor" name="calendar_anchor" href="#" style="display: none;" onclick="cal.select(document.getElementById(\'selected_date\'),\'calendar_anchor\',\'M/d/yy\');"> <img style="vertical-align: bottom;" src="images/cal.gif" width=24 border=0 /></a>'."\n";
			$rvalue .= '<button onclick="this.disabled=true; enableSignoff(true);">Enable Sign Off</button></div>';
			$rvalue .= '<div id="calendar_div" style="position:absolute; visibility:hidden; background-color: white; layer-background-color:white; z-index: 1000; color: black;"></div>'."\n";
		}
		$rvalue .= '<div style="clear: both;"></div>';
	}
	if($view == 'summary')
	{
		$sql = 'select id from award where type = \'Rank Advancement\' order by id';
		$ranks = array_field(fetch_array_data($sql,'scouts'),'id');
		$rvalue .= '<table>';

		$count = 0;
		foreach ($ranks as $award_id)
		{
			$count++;
			if((($count) % 3) == 1)
			{
				$rvalue .= '<tr>';
			}
			$rvalue .= '<td style="vertical-align: top; padding: 5px; ">';
			$rvalue .= get_award_table($award_id, $user_id, $group_id, $view);
			$rvalue .= '</td>';
			if(((($count) % 3)) == 3)
			{
				$rvalue .= '</tr>';
			}
		}
		if($count % 3 != 3)
		{
			$rvaue .= '</tr>';
		}
		$rvalue .= '</table>';
	}
	else // just view one award
	{
		$rvalue .= get_award_table($award_id, $user_id, $group_id, $view);
		if(isUser('Scoutmaster'))
		{
			$rvalue .= '<br /><button id="signoff_apply" disabled="disabled" style="float: right;" onclick="ajaxRequestApplyChanges();" >Apply Changes</button>';
		}
	}
	return $rvalue;
}

function get_award_color($id)
{
	$colors = Array(1 => '#8A78A3',
	                2 => '#A28DC0',
	                3 => '#729F5C',
	                4 => '#EADD61',
	                5 => '#9580BE',
	                6 => '#BDCDDA');
	if (array_key_exists($id, $colors))
		return $colors[$id];
	return 'white';
}

function get_award_table($award_id, $user_id = 0, $group_id = 0, $view = 'summary')
{
	
//	pre_print_r($requirements);
	if($user_id)
	{
		$users[0] = $user_id;
	}
	else
	{
		if ($group_id)
			$users = array_field(fetch_array_data('select user_id from user_group, user where user.id = user_group.user_id and _scout = \'T\' and state = \'active\' and group_id IN ('.$group_id.')','scouts'),'user_id');
		else if ($GLOBALS['troop_id'])
			$users = array_field(fetch_array_data('select id from user where scout_troop_id = '.$GLOBALS['troop_id'].' and state = \'active\' and _scout = \'T\'','scouts'),'id');
	}
	if(!is_array($users))
	{
		echo 'Error: No users selected';
		return '';
	}
	
	$sql = 'select id, title, type, img_url from award where id = '.$award_id;
	$award = fetch_data($sql,'scouts');

	if ($award['type'] == 'Merit Badge') 
	{
	  // For now, we are only keeping multiple versions of merit badges in the database.
		$selected_award_id = null;
		$selected_user_list = Array();
		if (isset($_GET['revision_year']))
			$award_year = $_GET['revision_year'];
		else
			$award_year = null;
		$award_version_table = GetAwardVersionTable($award['title'], $users, '&show=report', $selected_users, $selected_award_id, $award_year);
		// user_list can change after the call to GetAwardVersionTable...
		$users = $selected_users;
		if ($selected_award_id != $award_id)
		{
			$award_id = $selected_award_id;
			$sql = 'select id, title, type, img_url from award where id = '.$award_id;
			$award = fetch_data($sql,'scouts');
		}
	}
	else
	{
		$award_version_table = '';
	}
	
	//	$sql = 'select * from requirement where award_id = '.$award_id.' and parent_id = 0 order by number';
	$sql = 'select * from requirement where award_id = '.$award_id.' order by number';
	$requirements = fetch_array_data($sql,'scouts');

	$rvalue .= '<table class="main" ';
//	if($view == 'summary')
//	{
//		$rvalue .= 'style="width: 300px; "';
//	}
	$rvalue .= '>';
	$rvalue .= '<tr><td style="vertical-align: bottom; background-color: '.get_award_color($award_id).';';
	
	if($award['img_url'])
	{
		$rvalue .= ' background-image: url(';
		if($view == 'summary')
		{
			$rvalue .= 'resize.php?width=200&amp;height=115&amp;picture=';
		}
		$rvalue .= $award['img_url'].'); background-repeat: no-repeat; background-position: top center; ';
	}
	$rvalue .= '" class="header" colspan="2"><center>';
	$rvalue .= $award['title'].' ' . $award['type'];
	if ($award_version_table)
		$rvalue .= '<br/>'.$award_version_table;
	$rvalue .= '</center></td>';
	foreach ($users as $user_id)
	{
		$rvalue .= '<td class="yellow"><img src="http://boyscoutwebsite.com/text_image.php?string='.get_name($user_id).'&amp;background-color=FFFF53&amp;rotate=270&amp;';
		if($view == 'summary')
		{
			$rvalue .= 'x=1&amp;y=1&font-size=2&amp;width=110&amp;height=15';
		}
		else
		{
			$rvalue .= 'x=5&amp;y=3&font-size=3&amp;width=190&amp;height=20';
		}
		$rvalue .= '" /></td>';
	}
	$rvalue .= '</tr>';
	$user_reqs = get_user_reqs($award_id, $users);
	foreach ($requirements as $requirement)
	{
		if($requirement['parent_id'] == 0)
		{
			$rvalue .= get_req_rows($requirement, $requirements, $users, $user_reqs, $view);
		}
	}
	$rvalue .= '</table>';
	$rvalue .= getSignOffJS(get_user_reqs($award_id, $users, false));
	return $rvalue;
}

function getSignOffJS($user_reqs)
{
	$original_values =  json_encode($user_reqs);
	$rvalue  = "\n<script type=\"text/javascript\" src=\"scripts/ajax.js\"></script>\n";
	$rvalue .= "\n<script type=\"text/javascript\">\n";
	$rvalue .= "var request = null;\n";
//	$rvalue .= "var original_report_values = eval('(".$original_values.")');\n";
	$rvalue .= "var original_report_values = ".$original_values.";\n";
	$rvalue .= "var signoff_enabled = false;\n";
	$rvalue .= "var selected_signed_by = ".$_SESSION['USER_ID'].";\n";
	
	$rvalue .= "function enableSignoff(value)\n";
	$rvalue .= "{\n";
	$rvalue .= "	signoff_enabled = value;\n";
	$rvalue .= "	document.getElementById('selected_date').style.display = '';\n";
	$rvalue .= "	document.getElementById('calendar_anchor').style.display = '';\n";
//	$rvalue .= "	document.getElementById('signoff_apply').disabled = (value == true ? false : true);\n";
	$rvalue .= "}\n";
	
	$rvalue .= "function signOff(req_id, user_id, date, signed_by)\n";
	$rvalue .= "{\n";
	$rvalue .= "	if(!signoff_enabled)\n";
	$rvalue .= "		return;\n";
	$rvalue .= "	if(!date.match(/^\d{1,2}\/\d{1,2}\/\d{2}$/))\n";
	$rvalue .= "	{ alert('Error: Date must be in format m/d/yy'); return; }\n";
	$rvalue .= "	if(!original_report_values[user_id][req_id])\n";
	$rvalue .= "	{\n";
	$rvalue .= "		original_report_values[user_id][req_id] = {'signed_date':'', 'signed_by':'0', 'new_signed_date':'', 'new_signed_by':'0', 'new_modified':false };\n";
	$rvalue .= "	}\n";
	$rvalue .= "	var cell = document.getElementById('report_req_'+req_id+'_user_'+user_id);\n";
	$rvalue .= "	if((original_report_values[user_id][req_id]['signed_date'] == date && cell.innerHTML == date) || (original_report_values[user_id][req_id]['new_signed_date'] == date && original_report_values[user_id][req_id]['signed_by'] != 0)) // clear \n";
	$rvalue .= "	{\n";
	$rvalue .= "		original_report_values[user_id][req_id]['new_signed_date'] = '';\n";
	$rvalue .= "		original_report_values[user_id][req_id]['new_signed_by'] = 0;\n";
	$rvalue .= "		original_report_values[user_id][req_id]['new_modified'] = true;\n";
	$rvalue .= "		cell.innerHTML =  '_______';\n";
	$rvalue .= "		cell.className = 'yellow-changed';\n";
	$rvalue .= "	}\n";
	$rvalue .= "	else if(cell.innerHTML == '_______' || original_report_values[user_id][req_id]['new_signed_date'] == date) // revert to saved value \n";
	$rvalue .= "	{\n";
	$rvalue .= "		original_report_values[user_id][req_id]['new_signed_date'] = '';\n";
	$rvalue .= "		original_report_values[user_id][req_id]['new_signed_by'] = 0;\n";
	$rvalue .= "		original_report_values[user_id][req_id]['new_modified'] = false;\n";
	$rvalue .= "		cell.innerHTML = original_report_values[user_id][req_id]['signed_by'] != 0 ? original_report_values[user_id][req_id]['signed_date'] : '';\n";
//	$rvalue .= "	alert('signed_by='+original_report_values[user_id][req_id]['signed_by']);\n";
	$rvalue .= "		cell.className = 'yellow';\n";
	$rvalue .= "	}\n";
	$rvalue .= "	else\n";
	$rvalue .= "	{\n";
//	$rvalue .= "		alert('new='+original_report_values[user_id][req_id]['new_signed_date']+', date='+date);\n";
	$rvalue .= "		original_report_values[user_id][req_id]['new_signed_date'] = date;\n";
	$rvalue .= "		original_report_values[user_id][req_id]['new_signed_by'] = signed_by;\n";
	$rvalue .= "		original_report_values[user_id][req_id]['new_modified'] = true;\n";
	$rvalue .= "		cell.innerHTML = date;\n";
	$rvalue .= "		cell.className = 'yellow-changed';\n";
	$rvalue .= "	}\n";
	$rvalue .= "	document.getElementById('signoff_apply').disabled = (getSignOffParams() == '' ? true : false);\n";
	$rvalue .= "}\n";
	
	$rvalue .= "function getSignOffParams()\n";
	$rvalue .= "{\n";
	$rvalue .= "	var params = ''\n";
	$rvalue .= "	var sep = '';\n";
	$rvalue .= "	for(user in original_report_values)\n";
	$rvalue .= "	{\n";
	$rvalue .= "		for(requirement in original_report_values[user])\n";
	$rvalue .= "		{\n";
	$rvalue .= "			if(original_report_values[user][requirement] && original_report_values[user][requirement]['new_modified'])\n";
	$rvalue .= "			{\n";
	$rvalue .= "				params += sep + 'signoff_values['+user+']['+requirement+'][signed_by]='+original_report_values[user][requirement]['new_signed_by'];\n";
	$rvalue .= "				sep = '&';\n";
	$rvalue .= "				params += sep + 'signoff_values['+user+']['+requirement+'][signed_date]='+original_report_values[user][requirement]['new_signed_date'];\n";
	$rvalue .= "			}\n";
	$rvalue .= "		}\n";
	$rvalue .= "	}\n";
	$rvalue .= "	return params;\n";
	$rvalue .= "}\n";
	
	$rvalue .= "function ajaxRequestApplyChanges()\n";
	$rvalue .= "{\n";
	$rvalue .= "	document.getElementById('signoff_apply').disabled = true;\n";
	$rvalue .= "	signoff_enabled = false;\n";
	$rvalue .= "	request = getAjaxObject();\n";
	$rvalue .= "	request.open('POST', 'requirements.php?action=sign_report', true);\n";
	$rvalue .= "	request.onreadystatechange = applyChanges;\n";
	$rvalue .= "	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');\n";
	$rvalue .= "	request.send(getSignOffParams());\n";
	$rvalue .= "}\n";
	
	$rvalue .= "function applyChanges()\n";
	$rvalue .= "{\n";
	$rvalue .= "	if(request.readyState == 4)\n";
	$rvalue .= "	{\n";
	$rvalue .= "		if(request.status == 200)\n";
	$rvalue .= "		{\n";
	$rvalue .= "			var result = request.responseXML.getElementsByTagName('result')[0].childNodes[0].nodeValue;\n";
	$rvalue .= "			if(result == 'success')\n";
	$rvalue .= "			{\n";
	$rvalue .= "				for(user in original_report_values)\n";
	$rvalue .= "				{\n";
	$rvalue .= "					for(requirement in original_report_values[user])\n";
	$rvalue .= "					{\n";
	$rvalue .= "						if(original_report_values[user][requirement] && original_report_values[user][requirement]['new_modified'])\n";
	$rvalue .= "						{\n";
	$rvalue .= "							original_report_values[user][requirement]['signed_by'] = original_report_values[user][requirement]['new_signed_by'];\n";
	$rvalue .= "							original_report_values[user][requirement]['signed_date'] = original_report_values[user][requirement]['new_signed_date'];\n";
	$rvalue .= "							original_report_values[user][requirement]['new_signed_by'] = 0;\n";
	$rvalue .= "							original_report_values[user][requirement]['new_signed_date'] = '';\n";
	$rvalue .= "							original_report_values[user][requirement]['new_modified'] = false;\n";
	$rvalue .= "							var cell = document.getElementById('report_req_'+requirement+'_user_'+user);\n";
	$rvalue .= "							cell.innerHTML = original_report_values[user][requirement]['signed_date'];\n";
	$rvalue .= "							cell.className = 'yellow';\n";
	$rvalue .= "						}\n";
	$rvalue .= "					}\n";
	$rvalue .= "				}\n";
	$rvalue .= "				document.getElementById('signoff_apply').disabled = false;\n";
	$rvalue .= "				signoff_enabled = true;\n";
	$rvalue .= "			}\n";
	$rvalue .= "			else\n";
	$rvalue .= "			{\n";
	$rvalue .= "				alert('Error! error while applying changes');\n";
	$rvalue .= "			}\n";
	$rvalue .= "		}\n";
	$rvalue .= "		else\n";
	$rvalue .= "		{\n";
	$rvalue .= "			alert('Error! failure to apply changes');\n";
	$rvalue .= "		}\n";
	$rvalue .= "	}\n";
	$rvalue .= "}\n";
	$rvalue .= "</script>\n";
	return $rvalue;
}

function get_req_rows($requirement, $all_reqs, $users, $user_reqs, $view)
{
	$rvalue .= get_req_row($requirement, $users, $user_reqs, $view);
//	$sub_reqs = fetch_array_data('select * from requirement where parent_id = '.$requirement['id'].' order by number','scouts');
	$sub_reqs = get_sub_reqs($all_reqs, $requirement['id']);
	if(count($sub_reqs))
	{
		foreach ($sub_reqs as $sub_req)
		{
			$rvalue .= get_req_rows($sub_req, $all_reqs, $users, $user_reqs, $view);
		}
	}
	return $rvalue;
}

function get_req_row($requirement, $users, $user_reqs, $view)
{
	if(!$requirement['description'])
	{
		return '';
	}
	$rvalue .= '<tr>';
	$rvalue .= '<td class="value">'.$requirement['user_number'].'</td>';
	$rvalue .= '<td class="value" ';
	if($view == 'summary' and (strlen($requirement['description']) > 40))
	{
		$rvalue .= ' title="'.$requirement['description'].'" >'.substr($requirement['description'],0,37).'...';
	}
	else
	{
		$rvalue .= '>'.nl2br($requirement['description']);
	}
	$rvalue .= '</td>';
	foreach ($users as $user_id)
	{
		$rvalue .= '<td id="report_req_'.$requirement['id'].'_user_'.$user_id.'" class="yellow" onclick="signOff('.$requirement['id'].', '.$user_id.', document.getElementById(\'selected_date\').value, selected_signed_by);" >';
//		if($signed_date = do_query('select UNIX_TIMESTAMP(signed_date) as signed_date from user_req where signed_by != 0 and req_id = '.$requirement['id'].' and user_id = '.$user_id,'scouts'))
		if($user_reqs[$user_id][$requirement['id']]['signed_by'] && 
		  ($user_reqs[$user_id][$requirement['id']]['signed_by'] != 0))
		{
//			$rvalue .= '<center>';
			if($view == 'summary')
			{
				$rvalue .= 'X';
			}
			else
			{
			//	$rvalue .= date('n/y',$signed_date);
				$rvalue .= date('n/j/y',$user_reqs[$user_id][$requirement['id']]['signed_date']);
			}
//			$rvalue .= '</center>';
		}
		$rvalue .= '</td>';
	}
	$rvalue .= '</tr>';
	return $rvalue;
}

function get_merit_badge_report($user_id = 0, $group_id = 0)
{
	if($_GET['printpage'])
	{
		$rvalue .= '<script type="text/javascript">window.print();</script>';
	}
	else
	{
		$rvalue .= '<a href="requirements.php?printpage=1" target="_blank">Print Page</a><br />';
	}
	
	if($user_id)
	{
		$users[0] = $user_id;
	}
	else
	{
		$users = array_field(fetch_array_data('select user_id from user_group, user where user.id = user_group.user_id and _scout = \'T\' and group_id IN ('.$group_id.')','scouts'),'user_id');
	}
	if(!is_array($users))
	{
		echo 'Error: No users selected';
		return '';
	}

	$sql = 'select * from award where type = \'Merit Badge\' order by is_rqd_mb, title';
	$merit_badges = fetch_array_data($sql,'scouts');
	
	if (count($users) > 1)
		$qualifier = '<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>by one or more scouts</em>';
	$rvalue .= '<table class="main">';
	$rvalue .= '<tr><td colspan="2">';
	$rvalue .= '<script type="text/javascript">'."\n";
	$rvalue .= "function changeView(el){\n";
	$rvalue .= "  location='/requirements.php?'+el.name+'='+(el.checked ? 'T' : 'F');\n";
	$rvalue .= "}</script>\n";

	if($_GET['show_earned'])
		$_SESSION['show_earned']=$_GET['show_earned'];
	if (!$_SESSION['show_earned'])
		$_SESSION['show_earned']='T';

	if($_GET['show_in_progress'])
		$_SESSION['show_in_progress']=$_GET['show_in_progress'];
	if (!$_SESSION['show_in_progress'])
		$_SESSION['show_in_progress']='T';

	if($_GET['show_all'])
		$_SESSION['show_all']=$_GET['show_all'];
	if (!$_SESSION['show_all'])
		$_SESSION['show_all']='F';

	$rvalue .= '<input type="checkbox" name="show_earned" '.($_SESSION['show_earned'] == 'T'? 'checked' : '').' onClick="changeView(this)"> Show Merit Badges Earned '.$qualifier.'<br>';
	$rvalue .= '<input type="checkbox" name="show_in_progress" '.($_SESSION['show_in_progress'] == 'T'? 'checked' : '').' onClick="changeView(this)"> Show Merit Badges In Progress '.$qualifier.'<br>';
	$rvalue .= '<input type="checkbox" name="show_all" '.($_SESSION['show_all'] == 'T'? 'checked' : '').' onClick="changeView(this)"> Show All Merit Badges <br>';
	
	$rvalue .= '</td>';
	
	foreach ($users as $user_id)
	{
		$rvalue .= '<td class="yellow" rowspan="2"><img src="http://boyscoutwebsite.com/text_image.php?string='.get_name($user_id).'&amp;background-color=FFFF53&amp;rotate=270&amp;';
		if($view == 'summary')
		{
			$rvalue .= 'x=1&amp;y=1&font-size=2&amp;width=110&amp;height=15';
		}
		else
		{
			$rvalue .= 'x=5&amp;y=3&font-size=3&amp;width=190&amp;height=20';
		}
		$rvalue .= '" /></td>';
	}
	$rvalue .= '</tr>'."\n";
	$rvalue .= '<tr><td class="value" style="font-weight: bold; background: gray;">Merit Badge</td><td class="value" style="font-weight: bold; background: gray;">Notes</td></tr>';
	
	$sql = 'SELECT * FROM user_req WHERE user_id IN ('.implode(',',$users).') AND merit_badge_id != 0 AND signed_by != 0';
	$user_reqs = fetch_array_data($sql,'scouts');
	$pass_offs = Array();
	foreach ($user_reqs as $req)
	{
		$pass_offs[$req['user_id']][$req['merit_badge_id']] = $req;
	}
	$sql = 'SELECT DISTINCT award.id, user_req.user_id FROM user_req, requirement, award WHERE user_req.user_id IN ('.implode(',',$users).') AND user_req.req_id = requirement.id AND requirement.award_id = award.id AND award.type = \'Merit Badge\' AND user_req.signed_by != 0';
	$user_reqs = fetch_array_data($sql,'scouts');

	$in_progress = Array();
	foreach ($user_reqs as $req)
	{
		$in_progress[$req['user_id']][$req['id']] = true;
	}
	
	//debug($merit_badges);
	$t = Array();
	foreach ($merit_badges as $award)
	{
		if (!array_key_exists($award['title'], $t))
			$t[$award['title']] = Array('is_rqd_mb' => $award['is_rqd_mb'], 'mb_number' => $award['mb_number'], 'newest_revision' => 0, 'newest_id' => 0);
		$t[$award['title']]['versions'][$award['req_revision']] = $award['id'];
		if ($award['req_revision'] > $t[$award['title']]['newest_revision'])
		{
			$t[$award['title']]['newest_revision'] = $award['req_revision'];
			$t[$award['title']]['newest_id'] = $award['id'];
		}
	}
	//debug($t);
	$merit_badges = $t;

	foreach ($merit_badges as $title => $award)
	{
		$_earned = 0;
		$_in_progress = 0;
		$row = '';
		$row .= '<tr>';
		$row .= '<td class="value">'.$title.'</td>';
		$row .= '<td class="value">'.($award['is_rqd_mb'] == 'T' ? 'Required for Eagle' : '').'</td>';
		foreach ($users as $user_id)
		{
			$mb_link = 'requirements.php?award_id='.$award['newest_id'].'&scout_id='.$user_id;
			$info = '';
			foreach ($award['versions'] as $year => $award_id)
			{
				if($pass_offs[$user_id][$award_id])
				{
					$_earned++;
					$info .= '<center>';
					if(!$user_id)
						$info .= 'X';
					else
						$info .= date('n/d/y',strtotime($pass_offs[$user_id][$award_id]['signed_date']));
					$info .= '</center>';
					$mb_link = 'requirements.php?award_id='.$award_id.'&scout_id='.$user_id.'&revision_year='.$year;
				}
				else if ($in_progress[$user_id][$award_id])
				{
					$_in_progress++;
					//$row .= '<center style="font-weight: bold">';
					$info .= '<span style="color: red; font-weight: bold;">I.P.</span>';
					//$row .= '</center>';
					$mb_link = 'requirements.php?award_id='.$award_id.'&scout_id='.$user_id.'&revision_year='.$year;
				}
			}			
			$row .= '<td class="yellow" onClick="location=\''.$mb_link.'\'" style="cursor: pointer">';
			$row .= $info;
			$row .= '</td>';
		}
		$row .= '</tr>';
		
		if (($_SESSION['show_earned']=='T' and $_earned) or ($_SESSION['show_in_progress']=='T' and $_in_progress) or ($_SESSION['show_all'] == 'T'))
			$rows .= $row;
	}
	if ($rows)
		$rvalue .= $rows;
	else
	{
		$rvalue .= '<tr><td colspan="'.(2+count($users)).'">';
		if ($_SESSION['show_earned']=='T')
			$rvalue .= 'No Merit Badges Earned.<br>';
		if ($_SESSION['show_in_progress']=='T')
			$rvalue .= 'No Merit Badges In Progress.<br>';
		$rvalue .= '</td></tr>';
	}
	$rvalue .= '</table>';
	
	$rvalue .= '<br><br><div><table class="main">'.
	                    '<tr><td colspan=2 class="value" style="font-weight: bold; background: gray;">Key:</td></tr>'.
	                    '<tr><td class=value style="color:red; font-weight: bold;">I.P.</td><td class=value>In Progress (click to view details)</td></tr>'.
	                    '<tr><td class=value>MM/YY</td><td class=value>Passed Off in Month MM of Year YY</td></tr>'.
	                    '</table></div>';
	
	return $rvalue;
}

function get_user_reqs($award_id, $users, $unix_timestamp = true)
{
	$sql = 'select user_id, req_id, signed_by, UNIX_TIMESTAMP(signed_date) as signed_date from user_req, requirement where requirement.id = user_req.req_id and requirement.award_id = '.$award_id.' and user_req.user_id in ('.join(',',$users).')';
	$tmp_user_reqs = fetch_array_data($sql,'scouts');
	$user_reqs = Array();
	foreach ($tmp_user_reqs as $value)
	{
		$user_reqs[$value['user_id']][$value['req_id']] = Array('signed_by' => $value['signed_by'], 'signed_date' => ($unix_timestamp ? $value['signed_date'] : date('n/d/y',$value['signed_date'])));
	}
	return $user_reqs;
}

function get_sub_reqs($all_reqs, $req_id)
{
	$sub_reqs = Array();
	foreach($all_reqs as $req)
	{
		if($req['parent_id'] == $req_id)
		{
			$sub_reqs[] = $req;
		}
	}
	return $sub_reqs;
}

?>
