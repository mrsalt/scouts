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

function delete_user_note($table, $record_id, $user_id)
{
	$sql = 'DELETE FROM user_note WHERE table_name = \''.$table.'\' AND record_id = '.$record_id.' AND user_id = '.$user_id;
	execute_query($sql);
}

function format_user_note($notes, $only_user_id = null, $skip_user_id = null)
{
	$text = '';
	foreach ($notes as $note)
	{
		if ($note['user_id'] == $skip_user_id)
			continue;
		if ($only_user_id and $note['user_id'] != $only_user_id)
			continue;
		if (!$only_user_id)
			$text .= $note['name'].', '.date('j M Y', strtotime($note['date_updated'])).":\n";
		$text .= $note['notes'];
		if (!$only_user_id)
			$text .= "\n";
	}
	return $text;
}

?>