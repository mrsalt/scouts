<?php
require_once 'globals.php';

function update_user_note($table, $record_id, $user_id, $notes)
{
	if (get_user_note($table, $record_id, $user_id))
		$sql = 'UPDATE user_note SET notes = \''.addslashes($notes).'\', date_updated = NOW() WHERE table_name = \''.$table.'\' AND record_id = '.$record_id.' AND user_id = '.$user_id;
	else
		$sql = 'INSERT INTO user_note(table_name, record_id, user_id, date_updated, notes) VALUES(\''.$table.'\','.$record_id.','.$user_id.',NOW(),\''.addslashes($notes).'\')';
	execute_query($sql);
}

function delete_user_note($table, $record_id, $user_id)
{
	if (get_user_note($table, $record_id, $user_id))
	{
		$sql = 'DELETE FROM user_note WHERE table_name = \''.$table.'\' AND record_id = '.$record_id.' AND user_id = '.$user_id;
		execute_query($sql);
	}
}

function get_user_note($table, $record_id, $user_id = null)
{
	if ($record_id == 'all') return Array();
	
	$sql = 'SELECT user.name, user_note.* FROM user_note, user WHERE user_note.user_id = user.id AND table_name = \''.$table.'\' ';
	if($record_id != 'all')
	{
		$sql .= 'AND record_id = '.$record_id;
	}
	if ($user_id){
		if (is_array($user_id))
			$sql .= ' AND user_id IN ('.implode(',',$user_id).')';
		else
			$sql .= ' AND user_id = '.$user_id;
	}
	$sql .= ' ORDER BY date_updated DESC';
	return fetch_array_data($sql);
}

function get_my_notes($user_id, $post_url, $table_name, $record_id)
{
	$notes = get_user_note($table_name, $record_id, $user_id);
	$text = 'My Notes:<br/>';
	if (count($notes))
	{
		$text .= 'Last updated '.date('j M Y', strtotime($notes[0]['date_updated'])).'<br/>';
	}
	$text .= '<form method="POST" action="'.$post_url.'?action=update_my_notes">';
	$text .= '<input type="hidden" name="user_id" value="'.$user_id.'">';
	$text .= '<input type="hidden" name="table" value="'.$table_name.'">';
	$text .= '<input type="hidden" name="record_id" value="'.$record_id.'">';
	$text .= '<input type="hidden" name="target" id="_user_note_target" value="save">';
	$text .= '<textarea rows=12 cols=65 name="my_notes" style="BACKGROUND-IMAGE: url(images/underline.gif)">';
	if (count($notes))
		$text .= $notes[0]['notes'];
	$text .= '</textarea>'."\n";
	$text .= '<br/><input type="submit" value="Save">&nbsp;&nbsp;<input type="submit" value="Delete" onclick="javascript: document.getElementById(\'_user_note_target\').value=\'delete\'" /></form>';
	return $text;
}

function get_others_notes($user_ids, $exclude_id, $post_url, $table_name, $record_id)
{
	$notes = get_user_note($table_name, $record_id, $user_ids);
	
	foreach ($notes as $note)
	{
		if ($note['user_id'] == $exclude_id)
			continue;
		if (!$text)
			$text = '<br/>Notes entered by other users:<br/>';
		$text .= $note['name'].', '.date('j M Y', strtotime($note['date_updated'])).":\n<br/>";
		$text .= '<div style="BACKGROUND-IMAGE: url(images/underline.gif); width: 540px; border: 1px solid black;">';
		$text .= '<pre style="word-wrap: break-word; white-space: normal">'.$note['notes'].'</pre>';
		$text .= '</div>'."\n";
		$text .= '<form method="POST" action="'.$post_url.'?action=delete_note">';
		$text .= '<input type="hidden" name="user_id" value="'.$note['user_id'].'">';
		$text .= '<input type="hidden" name="table" value="'.$table_name.'">';
		$text .= '<input type="hidden" name="record_id" value="'.$record_id.'">';
		$text .= '<input type="submit" value="Delete"></form>'."\n";
	}
	return $text;
}


?>