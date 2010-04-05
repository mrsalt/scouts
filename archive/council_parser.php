<?php

//require_once '../include/globals.php';
require_once '../include/scout_globals.php';
require_once '../../include/http_post.php';

//$filename = 'council_html';
//$fhandle = fopen($filename,'r');
//$page = fread($fhandle, filesize($filename));

for($i=65; chr($i) < 'z'; $i++)
{
	$letter = chr($i);
	echo 'Working on letter '.$letter.'<br />';
	$remote_server = 'usscouts.org';
	$url = "http://www.usscouts.org/databases/ci.cgi?action=search&search_terms=" . $letter;
	$page = http_get($remote_server, $url);
	
	$lines = split("\n", $page);
	
	foreach ($lines as $line)
	{
		if(preg_match('/c_num=(\d+)">(.*?)</', $line, $match))
		{
			if(!do_query('select id from council where id = '.$match[1].' and name = \''.addslashes($match[2]).'\'','scouts'))
			{
				echo 'Council Number = '.$match[1];
				echo ', Council Name = '.$match[2] . '<br />';
				execute_query('insert into council set id = '.$match[1].', name = \''.addslashes($match[2]).'\'','scouts');
			}
		}
	}
}

//fclose($fhandle);

?>