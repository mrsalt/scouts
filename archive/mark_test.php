<?php
require_once 'include/scout_globals.php';
require_once 'include/scout_requirements_include.php';

$pt = new ScoutTemplate();
$pt->setPageTitle( SITE_TITLE );
$pt->writeBanner();
$pt->writeMenu();
connect_db('scouts');

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
/*
$troop_id = 1;

if (count($_POST))
{
	foreach ($_POST['action'] as $group_name => $change)
	{
		if ($change == 'create group')
			add_troop_group($troop_id, $group_name);
		else if ($change == 'delete group')
			delete_troop_group($troop_id, $group_name);
	}
}

echo '<form method="POST">';
echo get_troop_group_details($troop_id, true);
echo '<input type="submit" value="Submit">';
echo '</form>';
*/


/*
$ranks = Array('Boy Scout','Tenderfoot','Second Class','First Class','Star','Life','Eagle');
foreach ($ranks as $rank)
	pre_print_r('rank='.$rank.', next rank id='.get_next_rank_id($rank));
*/

/*
pre_print_r(get_award_list('Rank Advancement'));
pre_print_r(get_award_list('Merit Badge'));
pre_print_r(get_award_list('Eagle Palm'));
*/

pre_print_r('swimming versions='.GetAwardVersionCount('Swimming'));
pre_print_r('115 versions='.GetAwardVersionCount(115));
pre_print_r('e science versions='.GetAwardVersionCount('Environmental Science'));

$pt->writeFooter();
?>