<?php
require_once 'globals.php';
require_once 'email_functions_new.php';

function get_validation_script($purpose, $require_email = true, $require_password = true, $require_troop = true, $require_council = true)
{
	echo '<script type="text/javascript">'."\n";
	// Change Password Form
	//   1.  make sure old password field is present.
	//   2.  make sure new password field is valid.
	//   3.  make sure new password fields match.
	//   4.  create hash on old and new, zero out passwords, and then return true.
	// New User Form
	//   1.  validate fields besides passwords.
	//   2.  make sure password field is valid.
	//   3.  make sure password fields match.
	//   4.  create hash, zero out passwords, and then return true.
	
	echo "function validate_login(form){\n";
	if ($purpose == 'new_login')
	{
		//   1.  validate fields besides passwords.
		echo "  if (!form.name.value){\n";
		echo "    alert('Please enter your name');\n";
		echo "    form.name.focus();\n";
		echo "    return false;\n";
		echo "  }\n";
		if($require_council)
		{
			echo "  if (!(form.council_id.value > 0)){\n";
			echo "      alert('Please select your Council');\n";
			echo "    form.council_id.focus();\n";
			echo "    return false;\n";
			echo "  }\n";
		}
		if($require_troop)
		{
			echo "  if (!(form.troop_id.value > 0 || (form.troop_id.value == -1 && form.new_troop_number.value))){\n";
			echo "    if(form.troop_id.value == -1)\n";
			echo "      alert('Please enter your Troop Information');\n";
			echo "    else\n";
			echo "      alert('Please enter your Troop Number');\n";
			echo "    form.troop_id.focus();\n";
			echo "    return false;\n";
			echo "  }\n";
		}
		if ($require_email)
		{
			echo "  if (!form.email.value){\n";
			echo "    alert('Please enter your e-mail address');\n";
			echo "    form.email.focus();\n";
			echo "    return false;\n";
			echo "  }\n";
		}
	}
	if ($require_password)
	{
		//   2.  make sure password field is valid.
		echo "  if (form.password.value.length < 6){\n";
		echo "    alert('Your password must be 6 characters long');\n";
		echo "    form.password.focus();\n";
		echo "    return false;\n";
		echo "  }\n";
	}
	if ($purpose == 'change_password')
	{
		echo "  if (!form.current_password.value){\n";
		echo "    alert('Please type your current password');\n";
		echo "    form.current_password.focus();\n";
		echo "    return false;\n";
		echo "  }\n";
	}
	
	//   3.  make sure password fields match.
	echo "  if (form.password.value && form.password.value != form.re_password.value){\n";
	echo "    alert('Passwords do not match');\n";
	echo "    form.re_password.focus();\n";
	echo "    return false;\n";
	echo "  }\n";
	
	//   4.  if password fields match, create hash, zero out passwords, and then return true.
	if ($require_password)
		echo "  form.pass_hash.value = MD5(form.password.value);\n";
	else
		echo "  if (form.password.value) form.pass_hash.value = MD5(form.password.value);\n";
	echo "  form.password.value = '';\n";
	echo "  form.re_password.value = '';\n";
	if ($purpose == 'change_password')
	{
		echo "  form.curr_pass_hash.value = MD5(form.current_password.value);\n";
		echo "  form.current_password.value = '';\n";
	}
	echo "  return true;\n";
	echo "}\n";
	echo "</script>\n";
}

function authenticate(&$user_id, $email, $password, $store_cookie, &$error_message, $do_redirect = true, $redirect_url = '')
{
	$sql = 'SELECT id FROM user WHERE email = \''.$email.'\' AND password = \''.$password.'\'';

//write_log('login_include, sql='.$sql,$_SERVER['DOCUMENT_ROOT'].'/logs/debug.log');
	
	if ($id = do_query($sql,'scouts'))
	{
//write_log('login_include, id='.$id,$_SERVER['DOCUMENT_ROOT'].'/logs/debug.log');
		$user_state = do_query('SELECT state FROM user WHERE id = '.$id,'scouts');
		if ($user_state == 'active')
		{
			$user_id = $id;
			$sql = 'UPDATE user SET login_count = login_count + 1, last_login = NOW() WHERE id = '.$id;
			mysql_query($sql);
			if ($store_cookie)
			{
				setcookie('hashp',$password,time()+60*60*24*90);
				setcookie('email',$email,   time()+60*60*24*90);
			}
			if ($do_redirect)
			{
				if ($redirect_url)
					$location = $redirect_url;
				else if (isset($_POST['redirect']))
					$location = $_POST['redirect'];
				else
					$location = '/index.php';
				ob_end_clean();
				header('location: '.$location);
				exit;
			}
			return true;
		}
		else
		{
			$error_message = 'Sorry, your login is not valid until it has been reviewed by the scoutmaster or site administrator.  Please try again later.';
			return false;
		}
	}
	else
	{
		$error_message = 'Login Failed.  Make sure caps lock is off.';
		return false;
	}
}

function HandleForgotPassword($email, $site_title, $additional_message, $from_address, $from_name)
{
	$sql = "SELECT id FROM user WHERE email = '$email'";
	if ($id = do_query($sql,'scouts'))
	{
		$new_pass = substr(md5(time()),11,8);
		//pre_print_r("new_pass=$new_pass");
		$new_pass_hash = md5($new_pass);
		$sql = 'UPDATE user SET password = \''.$new_pass_hash.'\' WHERE id = '.$id;
		if (!mysql_query($sql))
		{
			die ('Error.  Failed to reset password');
		}
		$subject = 'Password has been reset';
		$message = "Hello,\nYour password at ".$site_title." has been reset to ".$new_pass."\n".
		           "Please change your password soon.";
		$message .= $additional_message;
		$recipient = $email;
		send_email($from_address, $recipient, $subject, $message);
//		send_mail($subject, $message, $recipient, $format = 'plain', $from_address, $from_name);
		// need to show message
		echo '<br><div style="font-size: larger">A new password has been created.  This password has been e-mailed to your e-mail address ('.$email.')</div>';
		echo '<br>Please check your e-mail and then change your password to one you will be able to remember.';
		return true;
	}
	else
	{
		echo '<br><div style="font-size: larger">No user with e-mail address '.$email.' exists in our database.  Did you use a different e-mail address when you registered?</div>';
		$_GET['action'] = 'forgot_password';
		return false;
	}
}


function HandleChangePassword(&$user_id, &$message, $email, $new_pass_hash, $site_title, $curr_pass_hash = '', $pt = null)
{
	//write_log('HandleChangePassword called, pt ? '.($pt ? 'true' : 'false'));
	$result = false;
	if (!isset($user_id))
	{
		if (!authenticate($user_id, $email, $curr_pass_hash, $store_cookie = isset($_COOKIE['hashp']), $error_message, $do_redirect = false))
		{
			//echo '<br><div style="font-size: larger">'.$error_message.'</div>';
			$message = '<script type="text/javascript">'.
			          "alert('$error_message');".
			          '</script>';
			if ($pt)
				echo $message;
			$_GET['action'] = 'change_password';
		}
	}
	if (isset($user_id))
	{
		if ($pt)
		{			
			//write_log('HandleChangePassword called, pt is set...');
			ob_end_clean();
			// Since the user is now logged in, let's restart this page (the menu should now say Logout instead of Login).
			//$pt->addScript('scripts/md5.js');
			//$pt->setPageTitle( $site_title . ' - Login' );
			$pt->writeBanner();
			$pt->setLink('login','Logout','logout.php');
			$pt->writeMenu();
		}
		$sql = 'UPDATE user SET password = \''.$new_pass_hash.'\' WHERE id = '.$user_id;
		connect_db('scouts');
		if (mysql_query($sql))
		{
			$message = 'Your password has been updated successfully.';
			$result = true;
		}
		else
			$message = 'An error occured when attempting to update your password.';
		if ($pt)
		{
			echo '<br><div style="font-size: larger">'.$message.'</div>';
			$pt->writeFooter();
			exit;
		}
	}
	return $result;
}

function HandleNewLogin( $name, $email, $pass_hash, &$message, $site_title, $pt = null, $send_mail = false, $recipient = '', $from_address = '', $from_name = '', $troop_id, $new_troop_number, $council_id)
{
	$result = false;
	if(!$pass_hash)
	{
		echo 'Error.  Password hashing failed.  Please contact administrator.';
		$pt->WriteFooter();
	}
	if (!isset($name) or !isset($email) or !isset($pass_hash))
	{
		if ($pt)
		{
			echo 'Error.  Form data not complete';
			$pt->writeFooter();
			exit;
		}
		return false;
	}
	$email_in_use = false;
	$name_in_use = false;
	
	$sql = "SELECT * FROM user WHERE email = '$email'";
	$data = fetch_data($sql,'scouts');
	if ($data and $data['email'] != 'salisbm@hotmail.com')
	{
		$message = 'Error.  You may not create an account with the specified e-mail address ("'.$email.'").  This address is already used by another user.';
		$email_in_use = true;		
	}

	if(!($council_id and ctype_digit($council_id)))
	{
		$message .= '<br>Error.  Invalid Council.  council_id = '.$council_id.', is_numeric='.ctype_digit($council_id);
		$invalid_council = true;
	}
	if(!($troop_id > 0 or ($troop_id == -1 and $new_troop_number and ctype_digit($new_troop_number))))
	{
		$message .= '<br />Error.  Invalid Troop.  troop_id = '.$troop_id.', new_troop_number = '.$new_troop_number.', council_id = '.$council_id.', is_numeric = '.ctype_digit($new_troop_number);
		$invalid_troop = true;
	}
	else if($troop_id == -1) // New Troop number
	{
		$tmp_troop_id = do_query('select id from troop where troop_number = '.$new_troop_number.' and council_id = '.$council_id,'scouts');
		if($tmp_troop_id)
		{
			$invalid_troop = true;
		}
	}
	else
	{
		$sql = 'SELECT id FROM user WHERE name = \''.addslashes($name).'\' AND scout_troop_id = '.$troop_id;
		if (do_query($sql,'scouts'))
		{
			$name_in_use = true;
			$message .= '<br />Error.  A user with the name '.$name.' exists in this troop.  Please add a middle initial, etc. to make the name unique.';
		}
	}
	
	if (!$name_in_use and !$email_in_use and !$invalid_troop and !$invalid_council)
	{
		if($troop_id == -1) // new troop
		{
			$new_troop = true;
			$sql = 'INSERT INTO troop set troop_number = '.$new_troop_number.', council_id = \''.$council_id.'\'';
			connect_db('scouts');
			if (!mysql_query($sql))
			{
				die('Error.  Failed to create troop.');
				exit;
			}
			$troop_id = mysql_insert_id();
		}
		
		$sql = "INSERT INTO user set name = '".addslashes($name)."', email = '".addslashes($email)."', password = '".addslashes($pass_hash)."', create_date = NOW(), scout_troop_id = ".$troop_id;
		if($new_troop)
		{
			$sql .= ", _scoutmaster = 'T', state = 'active'";
		}
		connect_db('scouts');
		if (!mysql_query($sql))
		{
			die('Error.  Failed to create user.');
			exit;
		}
		$result = true;
		$message = '<br><div style="font-size: larger">Thank you for registering with '.$site_title.'</div>';
		if($new_troop)
		{
			$message .= '<br />Your account has been created and activated.';
		}
		else
		{
			$message .= '<br />Your account has been created.<br /><br />It will become active after it has been reviewed by the Scoutmaster of your troop.';
		}
//		           '<br>Your account has been created.<br><br>It will become active after it has been reviewed by the site administrator.  You will receive e-mail notification when this happens.<br><br>However, it is likely the notification will classified as spam, so be sure to check your junk mail folder and/or add '.$from_address.' to your "known contacts" list.';
		if($send_mail)
		{
			$id = mysql_insert_id();
			if(!$new_troop)
			{
				$admin_emails = fetch_array_data('select email from user where _scoutmaster = \'T\' and scout_troop_id = '.$troop_id,'scouts');
				if(is_array($admin_emails))
				{
					$recipient .= ','.join(',',array_field($admin_emails,'email'));
				}
			}
			$troop_info = fetch_data('select troop_number, council.name as council from troop, council where troop.council_id = council.id and troop.id = '.$troop_id,'scouts');
			$subject = 'New User Registered ('.$name.') for Troop '.$troop_info['troop_number'];
//			$body = "Hello,\nA new user has registered (".$name.") in Troop ".$troop_id.". http://boyscoutwebsite.com/admin.php?action=show_details&entity=user&id=".$id;		
			$body = "Hello,\nA new user has registered (".$name.") in Troop ".$troop_info['troop_number'].".\n";
			$body .= "Council: ".$troop_info['council']."\n\n";
			$body .= "http://boyscoutwebsite.com/membership.php?todo=edit%20member&id=".$id;		
			send_email($from_address, $recipient, $subject, $body);
//			send_mail($subject, $body, $recipient, $format = 'plain', $from_address, $from_name);
		}
		if ($pt)
		{
			echo $message;
			$pt->writeFooter();
			exit;
		}
	}
	else
	{
		if ($pt)
			echo '<br><div style="color: red; font-size: larger;">'.$message.'</div>';
		$_GET['action'] = 'new_login';
	}
}

function ShowLoginPage($action)
{
	if (isset($action))
	{
		if ($action == 'change_password')
		{
			get_validation_script($action);
			echo '<div style="margin-left: 100px; margin-top: 40px;">';
			echo '<h2>Change My Password</h2>';
			echo '<form method="POST" action="login.php" onSubmit="return validate_login(this);">';
			echo '<input type="hidden" name="target" value="change_password">';
			echo '<input type="hidden" name="pass_hash" value="">';
			echo '<input type="hidden" name="curr_pass_hash" value="">';
			echo '<table style="border: thin black;">';
			if (!isset($_SESSION['USER_ID']))
				echo '<tr><td>E-mail Address</td><td><input type="text" name="email" id="email" value="'.(isset($_GET['email']) ? $_GET['email'] : '').'"></td></tr>';  
			echo '<tr><td>Current Password    </td><td><input type="password" name="current_password" id="current_password"></td></tr>';
			echo '<tr><td>New Password        </td><td><input type="password" name="password" id="password"></td></tr>';
			echo '<tr><td>Confirm New Password</td><td><input type="password" name="re_password" id="re_password"></td></tr>';
			echo '<tr><td></td><td><input type="submit" value="Submit"></tr>';
			echo '</table>';
			echo '</form>';
			echo '</div>';
		}
		else if ($action == 'forgot_password')
		{
			echo '<div style="margin-left: 100px; margin-top: 40px;">';
			echo '<h2>Forgot your password?</h2>';
			echo 'Enter your e-mail address and your password will be reset, then e-mailed to you.<br><br>';
			echo '<form method="POST" action="login.php" onSubmit="if(!this.email.value){alert(\'Enter your e-mail address\'); return false;}return true;">';
			echo '<input type="hidden" name="target" value="forgot_password">';
			echo '<table style="border: thin black;">';
			echo '<tr><td>E-mail address</td><td><input type="text" name="email" id="email"></td></tr>';
			echo '<tr><td></td><td><input type="submit" value="Submit"></tr>';
			echo '</table>';
			echo '</form>';
			echo '</div>';
		}
		else if ($action == 'new_login')
		{
			echo "\n";
			//echo '<script type="text/javascript" language="javascript" src="scripts/md5.js"></script>'."\n";		
			get_validation_script($action);
			echo '<script type="text/javascript">'."\n";
			echo "function update_troop_list(council_id)\n";
			echo "{\n";
			echo "  if(council_id != 0)\n";
			echo "  {\n";
//			echo "    alert(fields[i]);\n";
			echo "		document.getElementById('troop_number').style.display = '';\n";
//			echo "		alert('calling enable_disable_new_troop(' + document.login_form.troop_id.value + ')');\n";
			echo "		enable_disable_new_troop(login_form.troop_id.value);\n";
//			echo "alert('setting council to ' + council_id);\n";
			$councils = fetch_array_data('select id, name from council order by name','scouts');
			$initial_options = Array(Array('troop_number' => '<No Troop Selected>', 'id' => 0),
				                         Array('troop_number' => 'New Troop', 'id' => -1));
			$initial_count = 0;
			foreach ($initial_options as $troop)
			{
				echo "			document.login_form.troop_id.options[$initial_count].value = '".$troop['id']."';\n";
				echo "			document.login_form.troop_id.options[$initial_count].text = '".$troop['troop_number']."';\n";
				$initial_count++;
			}
			foreach ($councils as $council)
			{
				echo '    if (council_id == '.$council['id'].") //".addslashes($council['name'])."\n";
				echo "		{\n";
				$count = $initial_count;
				$troops = fetch_array_data('SELECT id, troop_number FROM troop where council_id = '.$council['id'].' order by troop_number','scouts');
				echo '			document.login_form.troop_id.options.length = '.(count($troops) + count($initial_options)).";\n";
				if(is_array($troops))
				{
					foreach ($troops as $troop)
					{
//						echo "alert('setting council to ' + council_id);\n";
						echo "			document.login_form.troop_id.options[$count].value = '".$troop['id']."';\n";
						echo "			document.login_form.troop_id.options[$count].text = '".$troop['troop_number']."';\n";
						$count++;
					}
				}
				echo "		}\n";
			}
			echo "  }\n";
			echo "  else\n";
			echo "  {\n";
			echo "		document.getElementById('troop_number').style.display = 'none';\n";
			echo "		enable_disable_new_troop(0); // hide the new troop field\n";
			echo "  }\n";
			echo "}\n\n";
			
			echo "function enable_disable_new_troop(troop_id)\n";
			echo "{\n";
			echo "  if(troop_id == -1)\n";
			echo "  {\n";
			echo "		document.getElementById('new_troop_number').style.display = '';\n";
			echo "  }\n";
			echo "  else\n";
			echo "  {\n";
			echo "		document.getElementById('new_troop_number').style.display = 'none';\n";
			echo "  }\n";
			echo "}\n";
			echo '</script>';
			
			
			echo '<div style="margin-left: 100px; margin-top: 40px;">';
			echo '<h2>New User Registration</h2>';
			echo '<form name="login_form" method="POST" action="login.php" onSubmit="return validate_login(this);">';
			//echo '<form method="POST" action="mailto: salisbm@hotmail.com" enctype="text/plain">';
			echo '<input type="hidden" name="pass_hash" value="">';
			echo '<input type="hidden" name="target" value="new_login">';
			echo '<table style="border: thin black;">';
			echo '<tr><td>Name</td><td><input type="text" name="name" id="name" value="'.$_REQUEST['name'].'"></td></tr>';
			echo '<tr><td>E-mail address</td><td><input type="text" name="email" id="email" value="'.$_REQUEST['email'].'" /></td></tr>';
			echo '<tr><td>Password</td><td><input type="password" name="password" id="password" /></td></tr>';
			echo '<tr><td>Confirm Password</td><td><input type="password" name="re_password" id="re_password" /></td></tr>';
			echo '<tr><td>Council</td><td>';
			$councils = fetch_array_data('select id as value, name from council order by name','scouts');
			array_unshift($councils, Array('name' => '&lt;No Council Selected&gt;', 'value' => 0));
			echo ShowSelectList('council_id', $_REQUEST['council_id'], $councils, 'update_troop_list(this.value)');
			echo '</td></tr>';
			// need to limit this by council
			if($_REQUEST['council_id'] && ctype_digit($_REQUEST['council_id']))
			{
				$sql = 'select id as value, concat(\'Troop \',troop_number) as name from troop where council_id = '.$_REQUEST['council_id'].' order by troop_number';
				$troops = fetch_array_data($sql,'scouts');
			}
			else
			{
				$troops = Array();
			}
			array_unshift($troops, Array('name' => '&lt;No Troop Selected&gt;', 'value' => 0), Array('name' => 'New Troop', 'value' => -1));
			echo '<tr id="troop_number" style="display: '.($_REQUEST['council_id'] == -1 ? '' : 'none').';"><td>Troop</td><td>';
			echo ShowSelectList('troop_id', $_REQUEST['troop_id'], $troops, 'enable_disable_new_troop(this.value)');
			echo '</td></tr>';
			echo '<tr id="new_troop_number" style="display: '.($_REQUEST['troop_id'] == -1 ? '' : 'none').';"><td>New Troop Number</td><td><input type="text" name="new_troop_number" value="'.$_REQUEST['new_troop_number'].'" /></td></tr>';
//			echo '<tr id="location"  style="display: '.($_REQUEST['troop_id'] == -1 ? '' : 'none').';"><td>City, State</td><td><input type="text" name="location" value="'.$_REQUEST['location'].'" /></td></tr>';
			echo '<tr><td></td><td><input type="submit" value="Submit"></tr>';
			echo '</table>';
			echo '</form>';
//			echo '<button onclick="alert(validate_login(document.login_form))" />Test</button>';
			echo '</div>';
		}
	}
	else  // general login screen
	{
		
		if ((!isset($_POST['target']) || $_POST['target'] != 'login') // don't automatically log me in if I tried to do it manually...
		    and isset($_COOKIE['email']) and isset($_COOKIE['hashp']) and !(preg_match('/logout.php$/',$_SERVER['PHP_SELF'])))
		{
			authenticate($_SESSION['USER_ID'], $_COOKIE['email'], $_COOKIE['hashp'], $store_cookie = false, $error_message, $do_redirect = true, (isset($_SESSION['login_redirect']) ? $_SESSION['login_redirect'] : ''));
		}
		echo "\n";
		echo '<script type="text/javascript">'."\n";
		echo "function validate_login(form){\n";
		echo "  if (!form.email.value){\n";
		echo "    alert('Please enter your e-mail address');\n";
		echo "    form.email.focus();\n";
		echo "    return false;\n";
		echo "  }\n";
		echo "  form.pass_hash.value = MD5(form.password.value);\n";
		echo "  form.password.value = '';\n";
		echo "  return true;\n";
		echo "}\n";
		echo "</script>\n";
		
		echo '<div style="margin-left: 100px; margin-top: 40px;">';
		echo '<h2>Login</h2>';
		echo '<form method="POST" action="login.php" onSubmit="return validate_login(this);">';
		echo '<input type="hidden" name="target" value="login">';
		echo '<input type="hidden" name="pass_hash" value="">';
		if (isset($_REQUEST['redirect']))
			echo '<input type="hidden" name="redirect" value="'.$_REQUEST['redirect'].'">';
		//echo '<table style="border: solid black;">';
		echo '<table>';
		echo '<tr><td>E-mail address</td><td><input type="text" name="email" id="email" value="'.(isset($_POST['email']) ? $_POST['email'] : '').'"></td></tr>';
		echo '<tr><td>Password</td><td><input type="password" name="password" id="password"></td></tr>';		
		echo '<tr><td colspan="2" align="center"><input type="checkbox" name="remember_password"> Remember my password on this computer</td></tr>';
		echo '<tr><td></td><td><input type="submit" value="Login"></tr>';
		echo '</table>';
		echo '</form>';
		echo '<a href="login.php?action=forgot_password">Help! I forgot my password</a><br>';
		echo '<br><a href="login.php?action=change_password">I want to change my password</a>';
		echo '</div>';
		
		echo '<div style="margin-left: 100px; margin-top: 40px;">';
		echo '<h2>New Users</h2>';
		echo '<a href="login.php?action=new_login">Register here</a>';
		echo '</div>';
	}
}

?>