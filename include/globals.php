<?php
ini_set('include_path','.:/usr/local/lib/php:include:scouts/include');
require_once 'site_data.php';
session_start();

// Create a handler function
function assert_handler($file, $line, $code) 
{
    // It seems that if there is a GLOBAL variable self reference, 
    // phpinfo will fail, as any attempt to print the key value 
    // pairs in globals will also produce an infinite loop...
    // This has been observed in duration_schedule.php. 
    // Until I find out what is causing this bug, I can't get 
    // variable info or the assert handler will fail and I won't get
    // any info at all.
//    ob_start();
//    phpinfo(INFO_VARIABLES);
//    $php_info = ob_get_contents();
//    ob_end_clean();
       
   
   $msg = "<pre>Assertion Failed:\n  File: $file\n  Line: $line\n  Code: [$code]\n\n" . get_backtrace()."</pre>";
   echo $msg;
   notify_administrator($msg . "\n". $php_info, false);
   ob_flush();
   
   exit;
}

function __autoload($class_name)
{
	require_once $class_name.'Class.php';	
}

function local_date($time_format, $server_time = null)
{
	if ($server_time == null)
		$server_time = time();
	global $server_local_time_diff;
	return date($time_format,$server_time-$server_local_time_diff);
}

function connect_db($database_name)
{
	if($database_name == 'scouts')
	{
		$database_name = 'db127142764';
	}
	$rs_link = mysql_connect ( MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD );
	if (!$rs_link)
		die('Failed to connect to mysql server');
	if (!mysql_select_db ( $database_name, $rs_link ))
		die('Failed to select database '.$database_name);
}

function fetch_array_data($sql, $db = '', $id_field = null)
{
	if ($db)
		connect_db($db);
		
	$rs = mysql_query($sql);
	if ($rs)
	{
	    $rArray = Array();
	    if ($id_field)
	    {
	        while ($data = mysql_fetch_assoc($rs))
        	{
        		$rArray[$data[$id_field]] = $data; 	
        	}
	    }
	    else
	    {
    	    while ($data = mysql_fetch_assoc($rs))
        	{
        		$rArray[] = $data; 	
        	}
        }
	  	return $rArray;
	}
	else
	{
		//debug_string("Query Failed: ".mysql_error().", sql=".$sql);
		$msg = "<pre>mysql error: ".mysql_error()."\nsql=".$sql."\nBacktrace=".get_backtrace()."\n\nREQUEST_URI=".$_SERVER['REQUEST_URI']."</pre>";
  	        trigger_error ($msg, E_USER_ERROR); 
		notify_administrator($msg);
	}
	return false;
}

function array_field($array, $key)
{
	if ($array and is_array($array))
	{
		$rarray = Array();
		foreach ($array as $value)
		{
			$rarray[] = $value[$key];
		}
	}	
	return $rarray;
}

function do_query($sql,$db=null)
{
	if ($db)  
		connect_db($db);
		
	if ($rs = mysql_query($sql))
	{
	  	$row = mysql_fetch_row($rs);
  		return $row[0];
  	}
  	else
  	{
	    $msg = "<pre>mysql error: ".mysql_error()."\nsql=".$sql."\nBacktrace=".get_backtrace()."\n\nREQUEST_URI=".$_SERVER['REQUEST_URI']."</pre>";
  	    notify_administrator(mysql_error());
		trigger_error ($msg, E_USER_ERROR); 
		exit;
  	}
}

function fetch_data($sql, $db = null)
{
	if ($db)
		connect_db($db);

	$rs = mysql_query($sql);
  
	if ($rs)
	{
		return mysql_fetch_assoc($rs);
	}
	else
	{
		$msg = "<pre>mysql error: ".mysql_error()."\nsql=".$sql."\nBacktrace=".get_backtrace()."\n\nREQUEST_URI=".$_SERVER['REQUEST_URI']."</pre>";
		notify_administrator(mysql_error());
		trigger_error ($msg, E_USER_ERROR); 
		exit;
	}
}

function execute_query($sql,$db = null)
{
	if ($db)
		connect_db($db);

	if (!mysql_query($sql))
	{
		$msg = "<pre>mysql error: ".mysql_error()."\nsql=".$sql."\nBacktrace=".get_backtrace()."\n\nREQUEST_URI=".$_SERVER['REQUEST_URI']."</pre>";
        notify_administrator(mysql_error());
		trigger_error ($msg, E_USER_ERROR); 
		exit;
	}
	if ($GLOBALS['write_debug_log'])
		write_log('  '.$sql);
}

function get_enums($table_name, $field_name)
{
	$sql = 'SHOW COLUMNS FROM `'.$table_name.'` LIKE \''.$field_name.'\'';
	$result = fetch_data($sql);
	if ($result['Field'] == $field_name)
	{
		if (preg_match('/enum\(\'(.+)\'\)/',$result['Type'],$matches))
			return explode("','", $matches[1]);
	}
	return false;
}
	
function pre_print_r($text)
{
	echo '<pre>'."\n";
	print_r($text);
	echo '</pre>'."\n";
}

function write_log($message, $log_file = null)
{
	if (!$log_file)
		$log_file = $_SERVER['DOCUMENT_ROOT'].'/logs/default.log';
	$handle = fopen($log_file,'a');
	if ($handle)
	{
		$data = $_SERVER['REMOTE_ADDR'].'|'.date('Y-m-d h:i A').'|'.$message."\r\n";
		fwrite($handle, $data);
		fclose($handle);
	}
}

function get_backtrace($levels_to_skip = 1)
{
	$trace = debug_backtrace();
	$text = '';
//	$first = true;
//	foreach ($trace as $stack)
//	{
//		$prev_file_line = $file_line;
//		$file_line = $stack['file'].', line '.$stack['line'];
//		$function_ars = $stack['function'].'('.implode(', ',$stack['args']).")";
//		if (!$first)
//		{
//			$text .= '  '.$function_ars."\r\n";
//			$text .= '    '.$prev_file_line."\r\n";
//		}
//		$first = false;
//		$count++;
//	}
//	$text .= '    '.$file_line."\r\n";
	$level = 0;
	foreach ($trace as $stack)
	{
		$level++;
		if ($level <= $levels_to_skip)
			continue;
		if (substr($stack['file'],0,46) == '/homepages/21/d117448133/htdocs/reederhome.net')
			$file = substr($stack['file'],46);
		else
			$file = $stack['file'];
		$text .= '  '.$stack['function'].'('.implode(', ',$stack['args']).") called from ".$file.', line '.$stack['line']."\r\n";
	}
	//$text .= '    '.$file_line."\r\n";
	return $text;
}

function ShowSelectList($name, $value, $options, $onChangeHandler = false, $isDisabled = false, $size = 1, $style = null, $title_text = null, $include_form_modified_check = false)
{	
	$text = "\n<select id=\"$name\" name=\"$name\" size=\"$size\"".
	        ($isDisabled ? ' disabled':'').
	        ($style ? ' style="'.$style.'"':'').
	        ($title_text ? ' title="'.$title_text.'"':'').
	        " onchange=\"";
	if ($include_form_modified_check)
	    $text .= "if (document.forms[0] &amp;&amp; document.forms[0].modified) document.forms[0].modified.value = 'true';";
		
	if ($onChangeHandler)
	  $text .= $onChangeHandler;
	$text .= "\">\n";
	
	if (is_array($options))
	{
		$value_found = false;
		foreach($options as $option)
		{
			if (!strcmp($option['value'], $value))
			{
				$value_found = true;	
			}
		}
		
		foreach($options as $option)
		{
			if (is_array($option))
			{
				$text .= "<option value=\"".( array_key_exists('value', $option) ? $option['value'] : $option['name'] )."\"";
				
	//			if ($name == 'machine_id')
	//				debug_string("option[value]=[".$option['value']."]\nvalue=[".$value."]\noption[name]=[".$option['name']."]\n!strcmp(option[value],value) = [".(!strcmp($option['value'],$value) ? 'true' : 'false')."]\noption[name]===value = [".($option['name'] === $value ? 'true' : 'false').']');
				
				if (($value_found and !strcmp($option['value'], $value)) or (!$value_found and $option['name'] === $value))
				{
				  $text .= "selected=\"selected\"";
				}
				$text .= ">".$option['name']."</option>\n";
			}
			else
			{
				$text .= "<option value=\"$option\"";
				if ($option == $value)
				  $text .= "selected=\"selected\"";
				$text .= ">$option</option>\n";
			}
		}
	}
  $text .= '</select>'."\n";
	return $text;
}

function get_administrator_email()
{
	if($_SERVER['SERVER_NAME'] == 'boyscoutwebsite.com')
	{
		if ($_SESSION['USER_ID'] == 1)
			$email = 'Mark Salisbury <salisbm@hotmail.com>';
		else if ($_SESSION['USER_ID'] == 8)
			$email = 'Randy Reeder <randy@reederhome.net>';
		else
			$email = 'Mark Salisbury <salisbm@hotmail.com>, Randy Reeder <randy@reederhome.net>';
	}
	else
	{
		$email = 'Randy Reeder <randy@reederhome.net>';
	}
	return $email;
}

function notify_administrator($text, $echo_and_exit = true)
{
	$email = get_administrator_email();

	$full_text = "Time: ".local_date('Y-m-d H:i:s')."\nLocation: ".$_SERVER['PHP_SELF'].($_SESSION['USER_ID'] ? "\nUSER_ID=[".$_SESSION['USER_ID'].'] '.get_name($_SESSION['USER_ID']) : '')."\nBacktrace:\n".get_backtrace()."\n\n".$text;
	//write_error_message($full_text, 'admin_log.txt');
	require_once 'email_functions_new.php';

	if ($_SESSION['USER_ID'] != 1)
		$result = send_email(SITE_ADMIN_EMAIL, $email, 'Scout Website Error', $full_text, $wrap = 0);
	else
		pre_print_r($full_text);
	//send_notification_email($email, "Website Error", $full_text, $from_address = false, $echo_email_results);
	if ($echo_and_exit)
	{
		if ($result)
			pre_print_r('The website administrators have been been notified.');
		pre_print_r('Sorry.  An error has occured.');
//		pre_print_r($full_text);  // probably safer if we don't print the error
		exit;
	}
}

function debug($data, $htmlentities = false)
{
	debug_string($data, $htmlentities);	
}

function debug_string($data, $htmlentities = false)
{
	if ($_SESSION['USER_ID'] == 1)
	{
		if ($htmlentities)
		{
			ob_start();
			print_r($data);
			$p_data = ob_get_contents();
			ob_end_clean();
			echo '<pre>'.htmlentities($p_data).'</pre>';
		}
		else
			pre_print_r($data);
	}
}

function browser_is_firefox()
{
	return (strpos($_SERVER['HTTP_USER_AGENT'],'Firefox') !== false ||
	        strpos($_SERVER['HTTP_USER_AGENT'],'Mozilla') !== false);
}

//function WritePHPVarInJS($var, $key_field = '', $var_name = '', $depth = 0)
//{
//    $rtext = '';
//    $rtext .=  str_repeat('  ',$depth);        
//    if (is_array($var))
//    {
//        if (isset($var[$key_field]))
//            $rtext .=  $var[$key_field].' : ';
//        $rtext .=  "{\n";
//        $sep = '';
//        foreach ($var as $key => $element)
//        {
//            $rtext .=  $sep;
//    	    $rtext .= WritePHPVarInJS($element, $key_field, $key, $depth + 1);
//    	    $sep = ",\n";
//    	}
//    	$rtext .=  "}";
//    }
//    else
//    {
//        if ($var_name)
//            $rtext .=  "'$var_name' ".($depth == 0 ? '=' : ':')." ";
//        $rtext .=  "'".addcslashes($var,"'\"\r\n\t\\")."'";
//    }
//    return $rtext;
//}

function WritePHPVarInJS($var, $var_name = null, $depth = 0)
{
	$rtext = '';
	$rtext .=  str_repeat('  ',$depth);        
	if (isset($var_name))
	{
		if ($depth == 0)
			$rtext .=  "$var_name = ";
		else
		{
			if (is_numeric($var_name))
				$rtext .=  "$var_name : ";
			else
				$rtext .=  "'$var_name' : ";
		}
  }
            
	if (is_array($var))
	{
		$rtext .=  "{\n";
		$sep = '';
		foreach ($var as $key => $element)
		{
			$rtext .=  $sep;
			$rtext .= WritePHPVarInJS($element, $key, $depth + 1);
			$sep = ",\n";
		}
		$rtext .=  "}";
	}
	else
	{
		$rtext .= "'".addcslashes($var,"'\"\r\n\t\\")."'";
	}
	return $rtext;
}

function is_whole_number($var){
  return (is_numeric($var)&&(intval($var)==floatval($var)));
}


///////////////////////////////////////////////////////////////////////
//  Begin code execution here
///////////////////////////////////////////////////////////////////////

// Active assert and make it quiet
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);
// Set up the callback
assert_options(ASSERT_CALLBACK, 'assert_handler');

if (strstr($_SERVER['REQUEST_URI'],'login.php') == FALSE and strstr($_SERVER['REQUEST_URI'],'logout.php') == FALSE)
	$_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];

global $server_local_time_diff;
$server_local_time_diff = 2 * 3600;
// server must be hosted in eastern time zone

?>
