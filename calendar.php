<?php
require_once 'include/scout_globals.php';
require_once 'scout_requirements_include.php';
require_once 'report_functions.php';
$is_scoutmaster = isUser('Scoutmaster');
$pt = new ScoutTemplate();
$pt->setPageTitle( SITE_TITLE );
$pt->writeBanner();
if(!$_GET['printpage'])
{
	$pt->writeMenu();
}
else
{
	$pt->writeLoginBanner();
}
echo '<div id="editDiv" style="border: black 1px solid; PADDING: 0px; FONT-SIZE: 10pt; LEFT: 0px; VISIBILITY: hidden; FONT-FAMILY: sans-serif; POSITION: absolute; TOP: 0px; COLOR: black; BACKGROUND-COLOR: #eee; layer-background-color: #eee"></div>';

$mode = 'future';
if (isset($_GET['calendar_mode']))
{
	if ($_GET['calendar_mode'] == 'past' or $_GET['calendar_mode'] == 'future')
		$_SESSION['calendar_mode'] = $_GET['calendar_mode'];
	else
		die('Invalid URL');
}
if($_SESSION['calendar_mode'] == 'past' or $_SESSION['calendar_mode'] == 'future')
{
	$mode = $_SESSION['calendar_mode'];
}
$months = 3;
if (isset($_GET['len']))
{
	$_SESSION['len'] = $_GET['len'];
}
if(isset($_SESSION['len']))
{
	$months = $_SESSION['len'];
}

if($_GET['printpage'])
{
	echo '<script type="text/javascript">window.print();</script>';
}
else
{
	echo '<script type="text/javascript" src="scripts/scout_calendar.js"></script>'."\n";
	echo '<script type="text/javascript" src="scripts/obj_debug.js"></script>'."\n";
	echo '<script type="text/javascript">'."\n";
	echo 'document.write(getCalendarStyles());'."\n";
	echo 'var cal = new CalendarPopup("calendar_div");'."\n";
	echo '</script>';
	echo '<form name="filter">';
	echo '<a href="calendar.php?printpage=1" target="_blank">Print Page</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	//echo '<span style="color: #ABB4FC;">Change Start Date</span> <input name="start_filter" type="text" size="10" value="'.($_REQUEST['start_filter'] ? $_REQUEST['start_filter'] : date('m/d/Y')).'" /><a id="calendar_anchor_filter" name="calendar_anchor_filter" href="#" onclick="cal.select(document.filter.start_filter,\'calendar_anchor_filter\',\'MM/dd/yyyy\'); document.filter.goButton.disabled=\'\'; return false;"> <img style="vertical-align: bottom;" src="images/cal.gif" width=24 border=0 /></a> <button id="goButton" name="goButton" onclick="location.assign(\'calendar.php?start_filter=\'+document.filter.start_filter.value);" disabled="disabled">Go</button><br />';
	echo 'Show <input type="radio" '.($mode=='past' ? 'checked':'').' onclick="location=\'calendar.php?calendar_mode=past&len='.$months.'\'"><a href="calendar.php?calendar_mode=past"> History</a>  ';
	echo '     <input type="radio" '.($mode=='future' ? 'checked':'').' onclick="location=\'calendar.php?calendar_mode=future&len='.$months.'\'"><a href="calendar.php?calendar_mode=future"> Plans</a>  ';
	echo ' for ';
	$options = Array(Array('name' => '1 month', 'value' => 1),
	                 Array('name' => '3 months', 'value' => 3),
	                 Array('name' => '6 months', 'value' => 6),
	                 Array('name' => '1 year', 'value' => 12),
	                 Array('name' => '2 years', 'value' => 24),
	                 Array('name' => 'All Dates', 'value' => 0));
	$onChangeHandler = 'location=\'calendar.php?calendar_mode='.$mode.'&len=\'+this.value;';
	echo ShowSelectList('len', $months, $options, $onChangeHandler);
	echo '</form>';
	echo '<br />';
}

if(!$_SESSION['USER_ID'])
{
	echo 'You must be logged in to view this page!';
	exit;
}

$groups = array_field(fetch_array_data('select scout_group.id from user, scout_group where scout_group.troop_id = user.scout_troop_id and user.id = '.$_SESSION['USER_ID'],'scouts'), 'id');

// set group id
if($_REQUEST['group_id'] && ctype_digit((string)$_REQUEST['group_id']))
{
	if(in_array($_REQUEST['group_id'], $groups))
	{
		$_SESSION['group_id'] = $_REQUEST['group_id'];
	}
}
if($_SESSION['group_id'] && !in_array($_SESSION['group_id'], $groups)) // just in case the session group_id is invalid (previously logged in as a different user)
{
	unset($_SESSION['group_id']);
}
if(!$_SESSION['group_id'])
{
	$_SESSION['group_id'] = do_query('select group_id from user_group where user_id = '.$_SESSION['USER_ID'],'scouts');
	if(!$_SESSION['group_id'])
	{
		$_SESSION['group_id'] = $groups[0];
	}
}
$group_id = $_SESSION['group_id'];

if(!$group_id)
{
	echo 'Error: you are not assigned to a group.  This can be done on the Membership page.';
	$pt->writeFooter();
	exit;
}

// display group selection options
$troop_id = do_query('select scout_troop_id from user where id = '.$_SESSION['USER_ID']);

$all_troop_groups = fetch_array_data('SELECT id, group_name FROM scout_group WHERE troop_id = '.$troop_id,'scouts', 'id');
foreach ($all_troop_groups as $id => $group)
{
	if ($group_id == $group['id'])
	{
		echo $group_sep.'<b>[ '.$group['group_name'].' ]</b>';
	}
	else
	{
		echo $group_sep.'<a href="calendar.php?group_id='.$group['id'].'">[ '.$group['group_name'].' ]</a>';
	}
	$group_sep = '&nbsp;&nbsp;';
}
echo '<br />';

//pre_print_r($_REQUEST);
if($_REQUEST['action'] == 'update_event' and isUser('Scoutmaster'))
{
	if($_REQUEST['id'])// and $group_id == do_query('select group_id from calendar where id = '.$_REQUEST['id'],'scouts'))
	{
		// UPDATE
		$_REQUEST['start_date'] = date('Y-m-d',strtotime($_REQUEST['start_date']));
		$_REQUEST['end_date'] = date('Y-m-d',strtotime($_REQUEST['end_date']));
		$sql = 'update calendar set start_date = \''.($_REQUEST['start_date']).'\', end_date = \''.($_REQUEST['end_date']).'\', activity = \''.($_REQUEST['activity']).'\', requirements = \''.($_REQUEST['requirements']).'\' where id = '.$_REQUEST['id'];
		execute_query($sql,'scouts');
	}
	else //if(!$_REQUEST['id'] and $group_id)
	{
		// INSERT
		$_REQUEST['start_date'] = date('Y-m-d',strtotime($_REQUEST['start_date']));
		$_REQUEST['end_date'] = date('Y-m-d',strtotime($_REQUEST['end_date']));
		$sql = 'insert into calendar set start_date = \''.($_REQUEST['start_date']).'\', end_date = \''.($_REQUEST['end_date']).'\', activity = \''.($_REQUEST['activity']).'\', requirements = \''.($_REQUEST['requirements']).'\'';//, group_id = '.$group_id;
		execute_query($sql,'scouts');
		$_REQUEST['id'] = mysql_insert_id();
	}
	
	execute_query('delete from calendar_group where calendar_id = '.$_REQUEST['id'], 'scouts');
	foreach ($_REQUEST['group'] as $id){
		//pre_print_r($id);
		$sql = 'insert into calendar_group(group_id, calendar_id) values('.$id.','.$_REQUEST['id'].')';
		execute_query($sql, 'scouts');
	}
	
}
else if($_REQUEST['action'] == 'delete_event' and isUser('Scoutmaster') and $group_id and $_REQUEST['id'])
{
	$sql = 'delete from calendar where id = '.$_REQUEST['id'];
	execute_query($sql,'scouts');
}

if ($mode == 'past')
{
	$end_date = 'NOW()';
	if ($months == -1)
		$start_date = '2000-01-01';
	else
		$start_date = date('Y-m-d',strtotime("-$months months"));
}
else
{
	$start_date = 'NOW()';
	if ($months == -1)
		$end_date = '2030-01-01';
	else
		$end_date = date('Y-m-d',strtotime("+$months months"));	
}


echo '<br />';
//echo get_event_table($group_id, $is_scoutmaster and !$_REQUEST['printpage'], $_REQUEST['start_filter'] ? date('Y-m-d',strtotime($_REQUEST['start_filter'])) : 'NOW()');
echo get_event_table($group_id, $is_scoutmaster and !$_REQUEST['printpage'], $mode == 'past' ? 'DESC' : 'ASC', $start_date, $end_date);
echo '</div>'; // end of main_content

echo '<script type="text/javascript">'."\n";
echo 'function clickDelete(id)'."\n";
echo '{'."\n";
echo '  if(confirm(\'Are you sure you want to delete this event?\'))'."\n";
echo '  {'."\n";
echo '    location.assign(\'calendar.php?action=delete_event&id=\'+id);'."\n";
echo '  }'."\n";
echo '}'."\n";
//echo 'cal.showNavigationDropdowns();'."\n";
echo '</script>';

function get_event_table($group_id, $can_edit, $order, $start_date = 'NOW()', $end_date = null)
{
	$events = get_events($group_id, $order, $start_date, $end_date);
	if(count($events))
	{
		$table = '';
		if($can_edit)
	  {
		  $table .= '<button onclick="clickEdit(0);">Add New Event</button><br /><br />';
	  }
		$table .= '<table class="main">';
		$table .= '<tr>';
		if($can_edit)
		{
			$table .= '<td class="dark-green-header">Action</td>';
		}
		$table .= '<td class="dark-green-header">Date</td>';
		$table .= '<td class="dark-green-header">Activity</td>';
		$table .= '<td class="dark-green-header">Requirements</td>';
		$table .= '</tr>';
		$row = 0;
		foreach ($events as $event)
		{
			$table .= '<tr>';
			if($can_edit)
			{
				$table .= '<td class="dark-green-'.($row % 2).'"><button style="font-size: 60%;" onclick="clickEdit('.$event['id'].');">edit</button> <button style="font-size: 60%;" onclick="clickDelete('.$event['id'].');">delete</button></td>';
			}
			$table .= '<td class="dark-green-'.($row % 2).'">'.date('j M Y (D)',$event['start_date']);
			if($event['start_date'] != $event['end_date'])
			{
				$table .= ' - '.date('j M y (D)',$event['end_date']);
			}
			$table .= '</td>';
			$table .= '<td class="dark-green-'.($row % 2).'">'.nl2br(htmlspecialchars($event['activity'])).'</td>';
			$table .= '<td class="dark-green-'.($row % 2).'">'.nl2br(htmlspecialchars($event['requirements'])).'</td>';
			$table .= '</tr>';
			$row++;
		}
		$table .= '</table>';
		
		$table .= '<script type="text/javascript">';
		$table .= 'var events = Array();'."\n";
		foreach ($events as $event)
		{
			$table .= 'events['.$event['id'].'] = { \'id\' : \''.addslashes($event['id']).'\',';
			$table .= '\'group_ids\': '.json_encode($event['group_ids']).',';
			$table .= '\'start_date\' : \''.addslashes(date('m/d/Y',$event['start_date'])).'\',';
			$table .= '\'end_date\' : \''.addslashes(date('m/d/Y',$event['end_date'])).'\',';
			$table .= '\'activity\' : \''.preg_replace("/\r\n/",'\n',addslashes($event['activity'])).'\',';
			$table .= '\'requirements\' : \''.preg_replace("/\r\n/",'\n',addslashes($event['requirements'])).'\'}'."\n";
		}    
		$table .= '</script>';
	}
	else
	{
		$table .= 'No Events have been entered for this group.<br />';
		$table .= '<script type="text/javascript">';
		$table .= 'var events = Array();'."\n";
		$table .= '</script>';
	}
	$table .= '<div id="calendar_div" style="position:absolute; visibility:hidden; background-color: white; layer-background-color:white; z-index: 1000; color: black;"></div>'."\n";
	if($can_edit)
	{
		$table .= '<br /><button onclick="clickEdit(0);">Add New Event</button>';
	}
	$table .= '<script type="text/javascript">';
	$table .= 'events[0] = Array();'."\n";
	$table .= 'events[0][\'id\'] = \'0\';'."\n";
	$table .= 'events[0][\'group_ids\'] = ["'.$group_id.'"];'."\n";
	$table .= 'events[0][\'start_date\'] = \''.addslashes(date('m/d/Y')).'\';'."\n";
	$table .= 'events[0][\'end_date\'] = \''.addslashes(date('m/d/Y')).'\';'."\n";
	$table .= 'events[0][\'activity\'] = \'\';'."\n";
	$table .= 'events[0][\'requirements\'] = \'\';'."\n";

  $groups = fetch_array_data('SELECT id, group_name FROM scout_group WHERE troop_id = '.$GLOBALS['troop_id']);
  $table .= 'var groups = '.json_encode($groups).';'."\n";

	$table .= '</script>';
	return $table;
}

function get_events($group_id, $order = 'ASC', $start_date = 'NOW()', $end_date = 0)
{
	if($start_date and $start_date != 'NOW()')
	{
		$start_date = '\''.$start_date.'\'';
	}
	if($end_date and $end_date != 'NOW()')
	{
		$end_date = '\''.$end_date.'\'';
	}
	$sql = 'SELECT DISTINCT id, UNIX_TIMESTAMP(start_date) as start_date, UNIX_TIMESTAMP(end_date) as end_date, activity, requirements 
	        FROM calendar, calendar_group 
	        WHERE calendar.id = calendar_group.calendar_id
	         AND (calendar_group.group_id = '.$group_id.') 
	         AND start_date >= '.$start_date;
	if($end_date)
	{
		$sql .= ' AND end_date <= '.$end_date;
	}
	$sql .= ' ORDER BY start_date '.$order;
	$events = fetch_array_data($sql,'scouts', 'id');
	
	if (count($events)){
	  $sql = 'SELECT group_id, calendar_id FROM calendar_group WHERE calendar_id IN (';
	  $sep = '';
	  foreach ($events as $event){
		  $sql .= $sep.$event['id'];
		  $sep = ',';
	  }
	  $sql .= ') ORDER BY calendar_id';
	  $group_ids = fetch_array_data($sql);
	  foreach ($group_ids as $gid){
	  	$events[$gid['calendar_id']]['group_ids'][] = $gid['group_id'];
	  }
	}
	return $events;
}

?>