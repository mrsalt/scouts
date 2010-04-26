<?php

require_once 'scout_globals.php';

function ShowRequirementsJS()
{		
	echo "\n\n";
	echo '<script type="text/javascript">'."\n";
	echo "function checkSubRequirement(is_checked){\n";
	echo "  document.getElementById('sub_number').style.display = (is_checked ? 'block' : 'none');\n";
	echo "}\n";
	echo "</script>\n";
}

function ShowGroupScoutSelectionBoxes($troop_id, $scout_id, $is_scout, $group_id, $target_page, $show = '')
{
	if ($_SESSION['USER_ID'] and $troop_id)
	{
		$sql = "SELECT DISTINCT scout_group.id AS value, group_name AS name ".
		       "FROM scout_group, user_group, user ".
		       "WHERE scout_group.troop_id = $troop_id ".
		       "AND scout_group.id = user_group.group_id ".
		       "AND user_group.user_id = user.id ".
		       "AND user._scout = 'T' ".
		       ($is_scout ? "AND user.id = ".$_SESSION['USER_ID'].' ' : '').
		       "AND user.state != 'blocked'";
		$troop_groups = fetch_array_data($sql,'scouts','value');
		
		$user_default_group_found = false;
		foreach ($troop_groups as $gid => $gdata)
		{
			if (!$def_gid)
				$def_gid = $gid;
			if ($gid == $group_id)
				$user_default_group_found = true;
		}
		if (!$user_default_group_found)
		{
			if (count($troop_groups) == 0)
			{
				$group_id = 0;
			}
			else
			{
				$group_id = $def_gid;
				$_SESSION['group_id'] = $group_id;
			}
		}
		if (count($troop_groups) > 1)
		{
			$troop_groups[] = Array('name' => '< All Ages >','value' => implode(',',array_field($troop_groups,'value')));
			$onChange = 'location.assign(\''.$target_page.'?group_id=\'+this.value'.($show ? ' + \'&amp;show='.$show.'\'' : '').');';
			echo ShowSelectList('group_id',$group_id,$troop_groups,$onChange);
		}

		$sql = 'SELECT DISTINCT user.id AS value, user.name '.
		       'FROM user'.($group_id ? ', user_group':'').' '.
		       'WHERE scout_troop_id = '.$troop_id.
		       ' AND state != \'blocked\' '.
		       ($group_id ? ' AND user.id = user_group.user_id AND user_group.group_id IN ('.$group_id.')':'').
		       ($is_scout ? " AND user.id = ".$_SESSION['USER_ID'] : '').
		       ' AND _scout = \'T\' ORDER BY name ASC';

		//pre_print_r($sql);
		$scouts = fetch_array_data($sql,'scouts');
		
		if (is_array($scouts) and count($scouts))
		{
			$all_name = '< All Scouts >';
			if ($troop_groups[$group_id]['value'])
				$all_name = '< All '.$troop_groups[$group_id]['name'].' >';
			if (count($scouts) > 1)
			    $scouts[] = Array('name' => $all_name,'value' => implode(',',array_field($scouts,'value')));
			if (!$scout_id)
			{
				$_SESSION['scout_id'] = $scout_id = $scouts[count($scouts)-1]['value'];
			}
			$onChange = 'location.assign(\''.$target_page.'?scout_id=\'+this.value'.($show ? ' + \'&amp;show='.$show.'\'' : '').');';
			if (count($scouts) > 1)
			    echo ShowSelectList('scout_id',$scout_id,$scouts,$onChange);
		}
	}
}

function ShowRequirementsNavigationBar($can_add_awards, $can_edit_progress, $is_scout, $award_id, $req_view, $troop_id, $group_id, $scout_id, $show='',$show_eagle_palms=false)
{
	ShowRequirementsJS();	

	echo '<table cellpadding=5>';
	echo '<tr><td align="left" valign="top" '.($req_view == 'Rank Advancement' ? ' style="background: white"' : '').'>';
	echo '<a href="requirements.php?req_view=Rank Advancement">Rank Advancement</a></td></tr>';
	echo '<tr><td align="left" valign="top" '.($req_view == 'Merit Badge' ? ' style="background: white"' : '').'>';
	echo '<a href="requirements.php?req_view=Merit Badge">Merit Badges</a></td></tr>';
	echo '<tr><td align="left" valign="top">';
	
	ShowGroupScoutSelectionBoxes($troop_id, $scout_id, $is_scout, $group_id, 'requirements.php', $show);	

	echo '</td></tr>'."\n";
	echo '<tr><td align="left" valign="top" style="padding: 0px;">';
	//echo '$req_view = '.$req_view;
	if ($req_view == 'Rank Advancement' or $req_view == 'Merit Badge')
	{
		//i.e. can handle overflow-y, firefox can't...
		echo '<div style="background: white; padding: 8px; overflow: auto; height: 320px; width: 210px;">'; // border: 1px solid black;
		if ($req_view == 'Rank Advancement' and $show_eagle_palms)
		{
			$awards = get_award_list('Eagle Palm');
			$image_scaling = 'width="40px"';
		}
		else
		{
			$awards = get_award_list($req_view);
			$image_scaling = 'height="30px"';
		}
		
		echo '<table border=0 cellspacing='.($req_view == 'Rank Advancement' ? '0':'1').' cellpadding=0>';
		
		if ($_SESSION['USER_ID'])
		{
			echo '<tr style="'.('all' == $award_id ? 'background: #C0C0C0;':'').'"><td></td><td><a style="color: red;" href="requirements.php?award_id=all&req_view='.$req_view.'">Show All</a></td><td></td></tr>';
			echo '<tr><td colspan="3"><hr></td></tr>';
		}
		if ($_SESSION['scout_id'] and strpos($_SESSION['scout_id'],',') === FALSE)
		{
			$completion_info = fetch_array_data('SELECT user_award.* FROM user_award WHERE user_id IN ('.$_SESSION['scout_id'].')','scouts','award_id');
		}
		foreach ($awards as $award)
		{
			$background = '';
			if ($completion_info and $completion_info[$award['id']]['signed_by'] != 0)
			{
				$title_text = 'Completed '.$completion_info[$award['id']]['signed_date'];
				$background = 'background: #BBE0BA;';
			}
			else
				$title_text = ($can_edit_progress ? 'Sign off' : 'View').' requirements for '.$award['title'].' '.$req_view;
			
			if ($award['id'] == $award_id)
				$background = 'background: #C0C0C0;';

			echo '<tr id="award_id_'.$award['id'].'" style="'.$background.'">';
			echo '<td>'.($award['img_url'] ? '<a href="requirements.php?award_id='.$award['id'].'&req_view='.$req_view.'"><img src="'.$award['img_url'].'" border=0 '.$image_scaling.'></a>':'').'</td>';			
			echo '<td><a title="'.$title_text.'" style="color: '.($award['is_rqd_mb'] == 'T' ? 'blue' : 'black').';" href="requirements.php?award_id='.$award['id'].'&req_view='.$req_view.'">'.($award['is_rqd_mb'] == 'T' ? '* ' : '').$award['title'].'</a></td>';
			echo '<td valign=top><a title="View printer friendly report for '.$award['title'].' '.$req_view.'" href="requirements.php?award_id='.$award['id'].'&req_view='.$req_view.'&show=report"><img src="images/report_icon.gif" border=0></a></td><tr>'."\n";
		}
		if ($req_view == 'Rank Advancement')
		{
			echo '<tr><td colspan="3"><hr></td></tr>';
			if ($show_eagle_palms)
				echo '<tr><td></td><td><a style="color: red;" href="requirements.php?req_view='.$req_view.'&show_palms=false">Rank Advancements</a></td><td></td></tr>';
			else
				echo '<tr><td></td><td><a style="color: red;" href="requirements.php?req_view='.$req_view.'&show_palms=true">Eagle Palms</a></td><td></td></tr>';
		}
		echo '</table>';
		echo '</div>';
		
		echo "<script type=\"text/javascript\">\n";
		echo "var active_row = document.getElementById('award_id_".$award_id."');\n";
		echo "if (active_row) active_row.scrollIntoView();\n";
		echo "</script>\n";
	}
	echo '</td></tr>'."\n";
	echo '</table>';
}

?>