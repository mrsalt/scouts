<?php
require_once 'include/scout_globals.php';
require_once 'include/scout_requirements_include.php';

define ('MB_DATA_PATH', 'merit_badge_data_2009');

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
echo '<html>';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>';
echo '<body>';

//UpdateAllRequirementNumbers('Merit Badge');

pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=CHECK_MB_LIST\">Verify Merit Badge List</a>\n  Verify that the list of merit badges on the internal DB is up to date.");
pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=RETRIEVE_DATA\">Download Merit Badge Data</a>\n  Download merit badge requirements from merit badge.org.");
pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=CREATE_PARSED_DATA\">Extract Requirements Data</a>\n  Extract the requirements portion from the web page data.");
pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=PARSE_REQUIREMENTS\">Parse All Requirements</a>\n  Parse requirement data &lt;dl&gt;&lt;dd&gt; into a PHP structure, save into a .php file.");
pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=PARSE_SPECIFIC\">Parse Specific</a>\n  Pick which merit badge to re-parse.");
pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=EDIT_MB_DATA\">Review Requirements</a>\n  Confirm that parsed data is correct, save changes.");

//pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=COMPARE_REVISION_YEARS\">Show Revision Year</a>\n  Show revision year according to data pulled from merit badge.com and merit badge.org.");
pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=COMPARE_REQUIREMENTS\">Compare Requirements</a>\n  Compare requirements for a specific award between different award versions.");
//pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=COMPARE_AWARDS\">Compare Awards</a>\n  Compare reqs in awards to see if they are the same.");
pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=UPDATE_DATABASE_TEST\">Add To Database (Test)</a>\n  Add merit badges to database that have been reviewed.");
pre_print_r("<a href=\"parse_mb_dot_org.php?cmd=UPDATE_DATABASE\">Add To Database (NOT Test)</a>\n  Add merit badges to database that have been reviewed.");

if (!isset($_GET['cmd']))
{
		exit;
}

//NumberRequirements($award_id=133);

//$command = 'PARSE_REQUIREMENTS';

$command = $_GET['cmd'];

//Not using this code anymore...

if ($command == 'UPDATE_DATABASE' and isset($_GET['mb']))
{
	pre_print_r('Adding '. $_GET['mb'].' to database.');
	save_reqs_to_db($_GET['mb'],$test=false);
	unset($_GET['mb']);
}
else if ($command == 'UPDATE_MB' and isset($_GET['mb']))
{
	//pre_print_r($_POST);
	$data['reqs'] = $_POST['req'];
	$data['year'] = $_POST['year'];
	$data['mb_number'] = $_POST['mb_number'];
	
	//pre_print_r($data);
	if (get_magic_quotes_gpc()) {
    function stripslashes_deep($value)
    {
        $value = is_array($value) ?
                    array_map('stripslashes_deep', $value) :
                    stripslashes($value);

        return $value;
    }
    $data = array_map('stripslashes_deep', $data);
	}
	
	if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/'))
		mkdir($_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/');
	$base_file = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/'.$_GET['mb'];
	//pre_print_r($data);
	file_put_contents($base_file.'.php', serialize($data));
	pre_print_r($_GET['mb'].' Saved!');

	// this is just for convenience so we don't have to hit the back button
	$command = 'EDIT_MB_DATA';	
	unset($_GET['mb']);
}
else if ($command == 'EDIT_MB_DATA' and isset($_GET['mb']))
{
	echo '<hr><h2>'.$_GET['mb'].'</h2>';
	
	$editted_file = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/'.$_GET['mb'].'.php';
	$parsed_data_file = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_parsed/'.$_GET['mb'].'.php';
	
	if (file_exists($editted_file))
	{
		if (!$mb_data)
			$mb_data = unserialize(file_get_contents($editted_file));
		$rqd_default = false;
	}
	else
	{
		$mb_data = unserialize(file_get_contents($parsed_data_file));
		//pre_print_r($mb_data);
		$rqd_default = true;
	}
	show_reqs($mb_data['reqs'], $_GET['mb'], $mb_data['year'], $mb_data['mb_number'], $rqd_default, $submit_button = true);
	exit;	
}
else if (($command == 'COMPARE_REQUIREMENTS' or $command == 'COMPARE_AWARDS') and isset($_GET['mb']))
{
	echo '<hr><h2>'.$_GET['mb'].'</h2>';
	
	$file = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/'.$_GET['mb'].'.php';
	
	if (file_exists($file))
	{
		$ps_reqs = unserialize(file_get_contents($file));
	}
	else
	{
		pre_print_r('MB data has not been reviewed yet.');
		exit;
	}
	$db_reqs = Array();
	$db_reqs['reqs'] = load_reqs_from_db($_GET['mb']);
	
	$similiarity = compare_award_versions($db_reqs['reqs'], $ps_reqs['reqs']);
	
	$mb_info = fetch_data('SELECT * FROM award WHERE type = \'Merit Badge\' AND title = \''.$_GET['mb'].'\' ORDER BY req_revision DESC LIMIT 1','scouts');
	
	echo '<hr>Match: '.$similiarity.'</hr>';
	echo '<hr><h3>Recent version from merit badge dot org ('.$ps_reqs['year'].'):</h3><hr>';
	show_reqs($ps_reqs['reqs'], $_GET['mb'], $ps_reqs['year'], $ps_reqs['mb_number'], false, $submit_button = false);
	
	//$current_req_year = get_year_from_html_file($mb_info['mb_number']);

	echo '<hr><h3>Current database version ('.$mb_info['req_revision'].') </h3><hr>';
	//<a href="/mb/'.sprintf('%03d',$mb_info['mb_number']).'.htm">View Original</a>
	
	show_reqs($db_reqs['reqs'], $_GET['mb'], $mb_info['req_revision'], $mb_info['mb_number'], false, $submit_button = false);
	exit;
}

if (array_key_exists('mb',$_GET))
	$merit_badges = Array(Array('title' => $_GET['mb']));
else
	$merit_badges = fetch_array_data('SELECT DISTINCT title FROM award WHERE type = \'Merit Badge\' ORDER BY title','scouts');

//$merit_badges = Array(Array('title' => 'Architecture'));

echo '<h1>'.$command.'</h1>';

if ($command == 'UPDATE_MB_LIST')
{
	if (array_key_exists('mb_list',$_POST))
	{
		$missing = unserialize(stripslashes($_POST['mb_list']));
		foreach ($missing as $mb)
		{
			if (array_key_exists('add',$_POST) && array_key_exists($mb,$_POST['add'])){
				$sql = "INSERT INTO award(`title`,`type`,`is_rqd_mb`,`mb_number`,`req_revision`) VALUES('".addslashes($mb)."', 'Merit Badge', 'F', ".(strlen($_POST['advancement_id'][$mb]) > 0 ? $_POST['advancement_id'][$mb] : 'NULL').", ".$_POST['year'][$mb].")";
				pre_print_r($sql);
				execute_query($sql, 'scouts');
			}
		}
	}
	exit;
}
else if ($command == 'CHECK_MB_LIST')
{
	$file_path = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'/MeritBadgeList';
	if (!file_exists($file_path)){
		$page_data = http_get('www.meritbadge.org','/wiki/index.php/Merit_Badges');
		pre_print_r('Retrieved '.strlen($page_data)." bytes\n");
		file_put_contents($file_path, $page_data);
	}
	$fp = fopen($file_path, 'r');
	$found_list_start = false;
	$missing = Array();
	while (($line = fgets($fp)) !== FALSE)
	{
		if (!$found_list_start)
		{
			if (preg_match('/<h2>.*Lists of Merit Badges.*<\/h2>/', $line, $match))
				$found_list_start = true;
		}
		if ($found_list_start)
		{
			//<a href="/wiki/index.php/Photography" title="Photography">Photography</a>
			if (preg_match( '/<a href="\/wiki\/index\.php\/([a-zA-Z_]+)" title="([a-zA-Z ]+)">([a-zA-Z ]+)<\/a>/m', $line, $match ))
			{
				//pre_print_r($match);
				$data = fetch_array_data('SELECT * FROM award WHERE type = \'Merit Badge\' AND title = \''.addslashes($match[3]).'\' ORDER BY title','scouts');
				//pre_print_r($data);
				if (!$data)
				{
					$missing[] = $match[3];
				}
			}
			else if (preg_match( '/Note: Merit badges displayed in a <b>bold font<\/b>/', $line, $match))
				break;
		}
	}
	fclose($fp);
	if (count($missing))
	{
		echo 'The following '. count($missing).' merit badge(s) are not in the database:<br/>';
		echo 'Do you want to add these merit badges to the database now?  Check each merit badge that should be added and enter the corresponding merit badge ID and requirements revision.';
		echo '<form action="parse_mb_dot_org.php?cmd=UPDATE_MB_LIST" method="POST">';
		echo '<table><tr><th>Add MB?</th><th>Title</th><th>Year Last Revised</th><th>BSA Advancement ID</th></tr>';
		echo '<input type="hidden" id="mb_list" name="mb_list" value="'.htmlentities(serialize($missing)).'"/>';
		foreach ($missing as $mb)
		{
			echo '<tr>';
			echo '<td><input type="checkbox" name="add['.$mb.']" /></td>';
			echo '<td>'.$mb.'</td>';
			echo '<td><input type="text" name="year['.$mb.']" value="2009"/></td>';
			echo '<td><input type="text" name="advancement_id['.$mb.']" value=""/></td>';
			echo '</tr>';
		}
		echo '</table>';
		echo '<input type="submit" value="Add To Database">';
		echo '</form>';
	}
	exit;
}
else if ($command == 'COMPARE_REQUIREMENTS' or $command == 'COMPARE_AWARDS' or $command == 'EDIT_MB_DATA' or $command == 'PARSE_SPECIFIC')
{
	echo '<table><tr><td valign=top><table>';
}
else if ($command == 'COMPARE_REVISION_YEARS')
{
	foreach ($merit_badges as $key => $mb)
	{
		$award_data = unserialize(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/'.$mb['title'].'.php'));
		$merit_badges[$key]['req_revision_new'] = $award_data['year'];
	}
	usort($merit_badges, 'sort_by_year');
	foreach ($merit_badges as $key => $mb)
	{
		//if ($mb['req_revision'] == $mb['req_revision_new'])
		{
			//$sql = 'UPDATE award SET req_revision = '.($mb['req_revision_new'] - 1).' WHERE title = \''.addslashes($mb['title']).'\'';
			//execute_query($sql,'scouts');
			
			echo '<hr><h2><a href="parse_mb_dot_org.php?cmd=COMPARE_AWARDS&mb='.urlencode($mb['title']).'">'.$mb['title'].'</a> #'.$mb['mb_number'].'</h2>';
			echo '<pre>  Current DB Version: '.$mb['req_revision']."\n".
		          "        New Revision: ".$mb['req_revision_new']."</pre>\n";
		}
	}
	exit;
}

$mb_count = 0;

if ($command == 'COMPARE_AWARDS')
{
	foreach ($merit_badges as $key => $mb)
	{
		$file = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/'.$mb['title'].'.php';
		$ps_reqs = null;
		if (file_exists($file))
			$ps_reqs = unserialize(file_get_contents($file));
		if (!is_array($ps_reqs) or !array_key_exists('reqs',$ps_reqs) or count($ps_reqs['reqs']) == 0)
			die('error -- ps reqs for '.$mb['title'].' invalid');
		$db_reqs = Array();
		$db_reqs['reqs'] = load_reqs_from_db($mb['title']);
		//pre_print_r($db_reqs);
		if (!is_array($db_reqs) or !array_key_exists('reqs',$db_reqs) or count($db_reqs['reqs']) == 0)
			die('error -- db reqs for '.$mb['title'].' invalid');
		$similiarity = compare_award_versions($db_reqs['reqs'], $ps_reqs['reqs']);
		$merit_badges[$key]['similiarity'] = $similiarity;
	}
	
	usort($merit_badges, 'sort_by_similiarity');
	pre_print_r($merit_badges);
}

foreach ($merit_badges as $mb)
{
	if ($command == 'RETRIEVE_DATA')
	{
		$file_path = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'/'.$mb['title'];
		pre_print_r('Retrieving '.$mb['title'].'...');
		$page = retrieve_data($mb['title']);
		pre_print_r('got '.strlen($page)." bytes\n");
		file_put_contents($file_path, $page);
	}
	else if ($command == 'CREATE_PARSED_DATA' || $command == 'PARSE_REQUIREMENTS')
	{
		$year_updated = 0;
		if (array_key_exists('year',$_POST))
			$year_updated = intval($_POST['year']);
		else if (array_key_exists('year',$_GET))
			$year_updated = intval($_GET['year']);
		if ($year_updated < 2000 || $year_updated > 2040)
		{
			echo 'Enter a year between 2000 and 2040.<br/><br/>';
			echo '<form action="parse_mb_dot_org.php?cmd='.$command.'" method="POST">Enter last year requirements were updated: <input type="text" name="year" />  <input type="submit" value="Submit"/></form>';
			exit;
		}
		
		// I have editted by hand data in the parsed directory so I don't want to update it automatically...
		$file_path = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'/'.$mb['title'];
		$data = file_get_contents($file_path);

		echo '<hr>';
		echo '<h2>'.$mb['title'].' ('.strlen($data).' bytes)</h2>';
		
		if (preg_match('/<b>BSA Advancement ID:<\/b>.*?<td>\s*(\d{1,4}|\S+).*?<\/td>.*?'.
		               '<b>Requirements Revision:<\/b>.*?<td>\s*(\d{4}|\S+).*?<\/td>.*?'.
		               '<b>Discontinued:<\/b>.*?<td>\s*(\S+).*?<\/td>/s', $data, $match))
		{
			pre_print_r('BSA MB Number: '.$match[1]);
			pre_print_r('Revision: '.$match[2]);
			pre_print_r('Discontinued: '.$match[3]);
			
			$mb_number = intval($match[1]);
			$revision_year = intval($match[2]);
			$discontinued = ($match[3] != 'n/a' && $match[3] != 'N/A');
			
			if ($discontinued)
				echo 'Merit badge discontinued.';
			else if ($revision_year < 1900 || $revision_year > 2040)
				echo 'Revision year invalid.';
			//else if ($revision_year <= $year_updated)
			//	echo 'Up to date.';
			else 
			{
				$parsed_data_path = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_parsed/'.$mb['title'];
				if ($command == 'CREATE_PARSED_DATA')
				{
					if (preg_match('/'.$mb['title'].' Requirements<\/span><\/h2>.*?(<ol>.*<\/ol>).*?border-left: 10px solid blue;">/s', $data, $match))
					{
						//'.*?<table.*?border-left: 10px solid blue;">/s')
						echo $match[1];
						if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_parsed/'))
							mkdir($_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_parsed/');
						file_put_contents($parsed_data_path, $match[1]);
					}
					else
					{
						echo 'No match';
					}
				}
				else if ($command == 'PARSE_REQUIREMENTS')
				{
					/*if ($current_req_year)
					{
						$sql = 'UPDATE award SET req_revision = '.$current_req_year.' WHERE title = \''.addslashes($mb['title']).'\'';
						execute_query($sql,'scouts');
					}*/
		
					$data = file_get_contents($parsed_data_path);
					//pre_print_r($data,true);
					$reqs = parse_reqs($data);
					$mb_data = Array('title' => $mb['title'],
					                 'year' => $revision_year,
									 'mb_number' => $mb_number,
					                 'reqs' => $reqs[0]['sub_reqs']);
					pre_print_r($mb_data);
					file_put_contents($parsed_data_path.'.php', serialize($mb_data));
				}
			}
		}
		else
		{
			echo 'No match';
		}
	}
	else if ($command == 'PARSE_SPECIFIC' or $command == 'EDIT_MB_DATA' or $command == 'COMPARE_REQUIREMENTS' or $command == 'COMPARE_AWARDS')
	{
		if (($mb_count != 0) and $mb_count % 25 == 0)
		{
			echo '</table></td><td valign=top><table>';
		}
		$mb_count++;
		echo '<tr><td>';
		
		if ($command == 'PARSE_SPECIFIC')
			echo '<a href="parse_mb_dot_org.php?cmd=PARSE_REQUIREMENTS&mb='.urlencode($mb['title']).'">'.$mb['title'].'</a>';
		else if (file_exists($_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_parsed/'.$mb['title'].'.php'))
			echo '<a href="parse_mb_dot_org.php?cmd='.$command.'&mb='.urlencode($mb['title']).'">'.$mb['title'].'</a>';
		else
			echo $mb['title'];
		
		if ($command == 'COMPARE_AWARDS')
			echo ' '.number_format($mb['similiarity'],2);
		else if (file_exists($_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/'.$mb['title'].'.php'))
			echo ' (.php)';
		echo '</td></tr>';
	}
	else if ($command == 'UPDATE_DATABASE_TEST' || $command == 'UPDATE_DATABASE')
	{
		if (file_exists($_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/'.$mb['title'].'.php'))
		{
			if ($command == 'UPDATE_DATABASE_TEST')
			{
				pre_print_r('(Test) Saving '.$mb['title'].' to database.');
				save_reqs_to_db($mb['title'],$test=true);
			}
			else
			{
				pre_print_r('Saving '.$mb['title'].' to database.');
				save_reqs_to_db($mb['title'],$test=false,$debug=false);
			}
		}
		else
		{
			pre_print_r('Skipping '.$mb['title']);
		}
	}
}

if ($command == 'PARSE_REQUIREMENTS' or $command == 'COMPARE_REQUIREMENTS')
{
	echo '</table></td></tr></table>';
}

echo '</body></html>';
exit;

function retrieve_data($mb_name)
{
	return http_get('www.meritbadge.org','/wiki/index.php?title='.urlencode($mb_name).'&printable=yes');
}

function http_get($host,$path, $port=80)
{
	$fp = fsockopen($host, $port, $errno, $errstr, 30);
	if (!$fp)
	{
	   echo "$errstr ($errno)<br />\n";
	}
	else
	{
	   $out = "GET ".$path." HTTP/1.1\r\n";
	   $out .= "Host: ".$host."\r\n";
	   $out .= "Connection: Close\r\n\r\n";
	
	   fwrite($fp, $out);
	   while (!feof($fp))
	   {
	       $rvalue .= fgets($fp, 128);
	   }
	   fclose($fp);
	}
	return $rvalue;
}


function parse_reqs($data, &$chars_used = 0, $depth = 0, $rqd_default = true)
{
	$num = 0;
	$requirement_pos = 0;
	$comment_num = 0;
	$reqs = Array();
	$debug = false;
  
	if ($debug)
		pre_print_r(str_repeat('   ', $depth).'data len: '.strlen($data));
  
	for ($i = 0; $i < strlen($data); $i++)
	{
		if ($data{$i} !== '<')
			continue;
			
		$next_three_chars = substr($data,$i,3);
		$next_four_chars = substr($data,$i,4);
		$next_five_chars = substr($data,$i,5);
		
		if ($next_three_chars == '<dl' || $next_three_chars == '<ol')
			$token = 'list_start';
		else if ($next_three_chars == '<dd' || $next_three_chars == '<li')
			$token = 'item_start';
		else if ($next_five_chars == '</dd>' || $next_five_chars == '</li>')
			$token = 'item_end';
		else if ($next_five_chars == '</dl>' || $next_five_chars == '</ol>')
			$token = 'list_end';
		else if ($next_three_chars== '<p>')
			$token = 'para_start';
		else if ($next_four_chars == '</p>')
			$token = 'para_end';
		else
			continue;

		$end_tag_inserted = false;
		//if (strpos($last_token,'start') !== FALSE && strpos($token,'start'))
		if (($last_token == 'item_start' && $token == 'item_start') ||
		    ($last_token == 'item_start' && $token == 'list_end'))
		{
			$token = str_replace( 'start', 'end', $last_token );
			$end_tag_inserted = true;
		}
		
		if ($token == 'list_start' || $token == 'item_end')
		{
			if (!array_key_exists($num, $reqs) or !array_key_exists('title',$reqs[$num]))
			{
				$title = trim(substr($data,$requirement_pos,$i-$requirement_pos));
				// code in show_reqs takes care of this:
				/*if (preg_match('/^([a-zA-Z1-9]{1,3})\.\s+(.*)$/', $title, $match)){
					$title = $match[2];
					$reqs[$num]['user_num'] = $match[1];
				}*/
					
				$reqs[$num]['title'] = $title;
				if ($debug)
					pre_print_r(str_repeat('   ', $depth).'title="'.htmlentities($title).'"');
			}
		}
		
		if ($token == 'list_start')
		{
			if ($debug)
				pre_print_r(str_repeat('   ', $depth).'found dl at '.$i);
			$reqd = 0;
			if (strpos(strtoupper($title),'ONE') !== FALSE)
				$reqd = 1;
			else if (strpos(strtoupper($title),'TWO') !== FALSE)
				$reqd = 2;
			else if (strpos(strtoupper($title),'THREE') !== FALSE)
				$reqd = 3;
			else if (strpos(strtoupper($title),'FOUR') !== FALSE)
				$reqd = 4;
			else if (strpos(strtoupper($title),'FIVE') !== FALSE)
				$reqd = 5;
			else if (strpos(strtoupper($title),'SIX') !== FALSE)
				$reqd = 6;
			else if (strpos(strtoupper($title),'SEVEN') !== FALSE)
				$reqd = 7;
			else if (strpos(strtoupper($title),'EIGHT') !== FALSE)
				$reqd = 8;
			
			$sub_rqd_default = true;
			if ($reqd)
			{
				if (strpos($title,'of the following') !== FALSE){
					$reqs[$num]['num_sub_req_reqd'] = $reqd;
					$sub_rqd_default = false;
				}
				else
					pre_print_r('req '.$num.' may have optional subreqs...');
			}
			$cu = 0;
			$reqs[$num]['sub_reqs'] = parse_reqs(substr($data,$i + 4), $cu, $depth + 1, $sub_rqd_default);
			$i += (4 + $cu);
			
			//pre_print_r(str_repeat('   ', $depth).'dl returned -- "'.htmlspecialchars(substr($data,$i)).'"');
		}
		else if ($token == 'item_start')
		{
			if ($debug)
				pre_print_r(str_repeat('   ', $depth).'found dd at '.$i.' ('.($num+1).')');
			$num++;
			$i += 3;
			$requirement_pos = $i + 1;
			$reqs[$num]['rqd'] = ($rqd_default ? 'true' : 'false');
		}
		else if ($token == 'item_end')
		{
			if ($debug)
				pre_print_r(str_repeat('   ', $depth).'found &lt;/dd&gt; at '.$i);
		}
		else if ($token == 'list_end')
		{
			if ($debug)
				pre_print_r(str_repeat('   ', $depth).'found &lt;/dl&gt; at '.$i);
			$chars_used = $i + 4;
			return $reqs;
		}
		else if ($token == 'para_start')
		{
			$i += 2;
			$comment_pos = $i + 1;
			$comment_num++;
		}
		else if ($token == 'para_end')
		{
			$reqs['comment-'.$comment_num] = substr($data,$comment_pos,$i-$comment_pos);
		}
		
		$last_token = $token;
		if ($end_tag_inserted)
			$i--;
	}
	
	return $reqs;
}

function fix_broken_links($match)
{
	//'/(<a.*?)href="(.*?)"(.*?\/a>)/'
	/*pre_print_r(htmlentities($match[0]));
	pre_print_r(htmlentities($match[1]));
	pre_print_r(htmlentities($match[2]));
	pre_print_r(htmlentities($match[3]));*/
	if (substr($match[2], 0, 4) == 'http')
		$url = $match[2];
	else
		$url = 'http://www.meritbadge.org'.$match[2];

	if (strpos($match[1].$match[3], 'target') == FALSE)
		$link = $match[1].'href="'.$url.'" target="_blank"'.$match[3];
	else
		$link = $match[1].'href="'.$url.'"'.$match[3];
	//pre_print_r(htmlentities($link));
	return $link;
}
				
function show_reqs($data, $mb, $req_revision, $mb_number, $rqd_default, $submit_button, $parent_id = '', $parent_number = 0, $depth = 0)
{
	if ($depth == 0)
	{
		//pre_print_r($data,true);
		if ($submit_button)
			echo '<form action="parse_mb_dot_org.php?cmd=UPDATE_MB&mb='.urlencode($mb).'" method="POST">';
		echo '<table style="font-family: monospace;">';// white-space: pre">';
		
		//echo '<tr><td>Rqd</td><td>Description</td></tr>'."\n";
		echo '<tr><td colspan=2>Revision Year <input type="text" name="year" value="'.$req_revision.'">&nbsp;&nbsp;';
		echo 'BSA MB Number <input type="text" name="mb_number" value="'.$mb_number.'">&nbsp;&nbsp;';
		echo '</td></tr>';
	}
	
	$count = 0;
	foreach ($data as $req_num => $req_data)
	{
		$req_id = $parent_id.'['.$req_num.']';
		
		echo '<tr><td valign=top>';
		if (is_array($req_data))
		{
			$count++;
			
			if ($depth == 0)
				$number = $count;
			else if ($depth == 1)
				$number = $parent_number . chr($count + 70 + 26);
			else if ($depth == 2)
				$number = $parent_number .'-'. ($count);
			else
				$number = $parent_number .'.'. ($count);
			echo '<input type="input" name="req'.$req_id.'[user_num]" value="'.$number.'" style="width: 40px;">';

			$required = $rqd_default;
			if (array_key_exists('rqd',$req_data))
				$required = ($req_data['rqd'] == 'true');
			echo '<input type="checkbox" name="req'.$req_id.'[rqd]"'.($required ? ' checked':'').' value="true">';
			
			if (array_key_exists('sub_reqs',$req_data))
			{
				if (array_key_exists('num_sub_req_reqd',$req_data))
					$num_sub_req_reqd = $req_data['num_sub_req_reqd'];
				else
					$num_sub_req_reqd = 0;
				echo '<select name="req'.$req_id.'[num_sub_req_reqd]">';
				echo '<option value="all"'.($num_sub_req_reqd != 0 ? '':' selected="selected"').'>All</option>';
				for ($i = 1; $i <= count($req_data['sub_reqs']); $i++)
					echo '<option value="'.$i.'"'.($i == $num_sub_req_reqd ? ' selected="selected"':'').'>'.$i.'</option>';
				echo '</select>';
				//echo '<input type="hidden" name="req'.$req_id.'[num_sub_req_reqd]" value="'.$req_data['num_sub_req_reqd'].'">';
			}
			
			echo '</td><td>';
				
			$title = trim($req_data['title']);
			$title = preg_replace_callback('/(<a.*?)href="(.*?)"(.*?\/a>)/','fix_broken_links', $title);
			
			if (preg_match('/^(\d+)\.\s+(.*)$/s',$title,$matches))
			{
				$title = $req_num.'. '.$matches[2];
				$description = trim($matches[2]);
				if ($req_num != $matches[1])
					echo '<span style="color: red">Warning: req nums ('.$req_num.'-'.$matches[1].') out of sync</span>';
			}
			else if (preg_match('/^([a-z])\.\s+(.*)$/s',$title,$matches))
			{
				$n = ord($matches[1]) - ord('a') + 1;
				$description = trim($matches[2]);
				if ($req_num != $n)
					echo '<span style="color: red">Warning: req nums ('.$req_num.'-'.$n.') out of sync</span>';
			}
			else if (preg_match('/^([A-Z])\.\s+(.*)$/s',$title,$matches))
			{
				$n = ord($matches[1]) - ord('A') + 1;
				$description = trim($matches[2]);
				if ($req_num != $n)
					echo '<span style="color: red">Warning: req nums ('.$req_num.'-'.$n.') out of sync</span>';
			}
			else
			{
				//echo '<span style="color: red">Warning: no match "'.htmlspecialchars($title).'"</span>';
				$description = $title;
				$title = $req_num.'. '.$title;
			}
			
			//echo '<input type="hidden" name="req'.$req_id.'[title]" value="'.trim($req_data['title']).'">';	
			echo '<input type="hidden" name="req'.$req_id.'[title]" value="'.htmlspecialchars($description).'">';
			
			echo str_repeat('&nbsp;&nbsp;&nbsp;', $depth).$title;
			echo '</td></tr>'."\n";
			
			if (array_key_exists('sub_reqs',$req_data))
			{
				show_reqs($req_data['sub_reqs'], $mb, $req_revision, $mb_number, $rqd_default, $submit_button, $req_id.'[sub_reqs]', $number, $depth + 1);
			}
		}
		else // it is a comment
		{
			//$req_id = $parent_id.'[comment-'.$req_num.']';
			echo '<input type="hidden" name="req'.$req_id.'" value="'.htmlentities(trim($req_data)).'">';
			
			echo '&nbsp;</td><td>';
			echo str_repeat('&nbsp;&nbsp;&nbsp;', $depth).$req_data;
			echo '</td></tr>'."\n";
		}
		
	}
	
	if ($depth == 0)
	{
		if ($submit_button)
			echo '<tr><td colspan=2><input type="submit" value="Save File"></td></tr>';
		echo '</table>';
		if ($submit_button)
			echo '</form>';
	}	
}

function load_reqs_from_db($mb, &$reqs = null, $parent_id = 0)
{
	if ($reqs == null)
	{
		$award_id = do_query('SELECT id FROM award WHERE title = \''.$mb.'\' ORDER BY req_revision DESC LIMIT 1','scouts');
		$sql = 'SELECT requirement.* FROM requirement, award WHERE award_id = award.id AND award.id = '.$award_id.' ORDER BY number';
		//pre_print_r($sql);
		$reqs = fetch_array_data($sql,'scouts');
		if (count($reqs) == 0)
		{
			echo 'No requirements found ('.$sql.')<br>';
			return null;
		}
		foreach ($reqs as $req)
		{
			if ($req['parent_id'] != 0)
			{
				$reqs['parent_reqs'][$req['parent_id']][] = $req['id'];
			}
		}
	}
	
	$r = Array();
	$count = 0;
	
	foreach ($reqs as $key => $req)
	{
		if ($key == 'parent_reqs')
			continue;
			
		if ($req['parent_id'] == $parent_id)
		{
			$count++;
			if ($req['req_type'] == 'Comment')
			{
				$r[$count] = $req['description'];
			}
			else
			{
				$r[$count]['title'] = $req['description'];
				$r[$count]['rqd']   = ($req['required'] == 'T' ? 'true' : 'false');
				if (array_key_exists('parent_reqs', $reqs) and array_key_exists($req['id'], $reqs['parent_reqs']))
				{
					$r[$count]['num_sub_req_reqd'] = $req['n_required'];
					$r[$count]['sub_reqs'] = load_reqs_from_db($mb, $reqs, $req['id']);
				}
			}
		}
	}
	return $r;
}

function get_year_from_html_file($mb_number)
{
	$current_path = $_SERVER['DOCUMENT_ROOT'].'/mb/'.sprintf('%03d',$mb_number).'.htm';
	$current_reqs = file_get_contents($current_path);
	
	if(preg_match('/.*\, revised (\d+).*/s',$current_reqs,$match))
	{
		return $match[1];
	}
	else
	{
		return null;
	}
}

function get_words_in_reqs($reqs)
{
	$words = '';
	foreach ($reqs as $req)
	{
		if (!is_array($req))
			$words .= $req;  // comment	
		else
		{
			if (array_key_exists('title', $req))
				$words .= $req['title'].' ';
			if (array_key_exists('sub_reqs', $req))
				$words .= get_words_in_reqs($req['sub_reqs']);
		}
	}
	return $words;
}

function compare_award_versions($db_reqs, $ps_reqs)
{
	//pre_print_r($ps_reqs);
	// Get number of words in a and b
	// Get number of words in a not in b
	// Get number of words in b not in a
	$db_words = get_words_in_reqs($db_reqs);
	$ps_words = get_words_in_reqs($ps_reqs);
	
	//pre_print_r($db_words);
	//pre_print_r($ps_words);
	
	$db_wc = str_word_count($db_words, 1);
	$ps_wc = str_word_count($ps_words, 1);
	$db_words = Array();
	$ps_words = Array();
	foreach ($db_wc as $word)
	{
		$word = strtolower($word);
		if (array_key_exists($word, $db_words))
			$db_words[$word]++;	
		else
			$db_words[$word]=1;
	}
	foreach ($ps_wc as $word)
	{
		$word = strtolower($word);
		if (array_key_exists($word, $ps_words))
			$ps_words[$word]++;	
		else
			$ps_words[$word]=1;
	}
	ksort($db_words);
	ksort($ps_words);
	
	foreach ($db_words as $word => $count)
		$total_words += $count;
	foreach ($ps_words as $word => $count)
		$total_words += $count;
		
	$common_words = array_intersect_key($db_words, $ps_words);
	$common_count = 0;
	foreach ($common_words as $word => $c)
		$common_count += min($db_words[$word],$ps_words[$word]);
	
	//pre_print_r($db_words);
	//pre_print_r($ps_words);
	//pre_print_r($common_words);
	//pre_print_r('total words = '.$total_words.', common_count = '.$common_count.', match='.($common_count * 2 / $total_words));
	
	return ($common_count * 2 / $total_words);
}

function sort_by_similiarity($a, $b)
{
	if (number_format($a['similiarity'],3) == number_format($b['similiarity'],3))
		return 0;
	if ($a['similiarity'] > $b['similiarity'])
		return -1;
	else
		return 1;
}


function sort_by_year(&$a, &$b)
{
	return ($a['req_revision_new'] - $a['req_revision']) - ($b['req_revision_new'] - $b['req_revision']);
}

function save_reqs_to_db($mb, $test = true, $echo_debug = true, $award_id = 0, $requirements = null, $parent_id = 0)
{
	if ($requirements == null)
	{
		$mb_data = fetch_data('SELECT * FROM award WHERE title = \''.$mb.'\' ORDER BY req_revision DESC LIMIT 1','scouts');
		
		if ($mb_data['type'] != 'Merit Badge')
			die($mb.' is not a merit badge');
		
		$file = $_SERVER['DOCUMENT_ROOT'].'/'.MB_DATA_PATH.'_editted/'.$mb.'.php';
		
		if (!file_exists($file))
			die($mb.' file not found');
			
		$ps_reqs = unserialize(file_get_contents($file));
				
		$is_rqd = $mb_data['is_rqd_mb'];
		$mb_number = $ps_reqs['mb_number'];
		$revision = $ps_reqs['year'];
		
		$data = fetch_data('SELECT * FROM award WHERE title = \''.addslashes($mb).'\' AND req_revision = '.$revision, 'scouts');
		if ($data)
		{
			$requirement_count = do_query('SELECT COUNT(id) FROM requirement WHERE award_id = '.$data['id'],'scouts');
			if ($requirement_count > 0)
			{
				pre_print_r($mb.' '.$revision.' is already in database!');
				return;
			}
		}
		
		$sql = "INSERT INTO award ( `title`, `type`, `is_rqd_mb`, `mb_number`, `req_revision` ) VALUES ( '".addslashes($mb)."', 'Merit Badge', '".$is_rqd."', $mb_number, $revision)";
		
		if ($echo_debug)
			pre_print_r(htmlentities($sql));
		if (!$test)
		{
			execute_query($sql);
			$award_id = mysql_insert_id();
		}
		else
			$award_id = 180;

		save_reqs_to_db($mb, $test, $echo_debug, $award_id, $ps_reqs['reqs'], 0);
		
		//NumberRequirements($award_id);
	}
	else
	{
		$number = 1;
		foreach ($requirements as $key => $req)
		{
			if (is_array($req))
			{
				$req_type = 'Basic Requirement';
				$description = $req['title'];
				$required = ($req['rqd'] ? 'T' : 'F');
				if (array_key_exists('num_sub_req_reqd', $req) and $req['num_sub_req_reqd'] != 'all')
					$nrqd = $req['num_sub_req_reqd'];
				else
					$nrqd = 0;
				$user_num = $req['user_num'];
			}
			else
			{
				$req_type = 'Comment';
				$description = $req;
				$required = 'F';
				$nrqd = 0;
				$user_num = '?';
			}
 
			$sql = 'INSERT INTO `requirement` ( `award_id` , `number` , `user_number` , `description` , `parent_id` , `required` , `n_required`, `req_type` ) '.
						 'VALUES ('.$award_id.', '.$number.', \''.$user_num.'\', \''.addslashes($description).'\', '.$parent_id.', \''.$required.'\', '.$nrqd.', \''.$req_type.'\')';
			
			if ($echo_debug)
				pre_print_r(htmlentities($sql));
			if (!$test)
			{
				execute_query($sql);
				$req_id = mysql_insert_id();
			}
			else
				$req_id = 2305+$parent_id+$number;
			if (is_array($req) and array_key_exists('sub_reqs', $req))
			{
				save_reqs_to_db($mb, $test, $echo_debug, $award_id, $req['sub_reqs'], $req_id);
			}
			$number++;
		}
	}
}


?>