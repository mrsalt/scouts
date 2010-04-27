<?php
require_once 'include/scout_globals.php';
require_once 'include/picture_functions.php';

if ($_SERVER['HTTP_HOST'] != SITE_URL)
{
	echo 'This page has moved to <a href="http://'.SITE_URL.'">http://'.SITE_URL.'</a>.  You will be redirected in 5 seconds.';
	echo '<script>setTimeout( "window.location.href = \'http://'.SITE_URL.'\'", 5*1000 );</script>';
	exit;
}

$pt = new ScoutTemplate();

//$pt->addStyle('body','margin: 25px; font-family: Verdana; background: #003030; color: white;');

$pt->setPageTitle( SITE_TITLE );
$pt->addStyleSheet('css/basic.css');
$pt->writeBanner();
$pt->writeMenu();

if (array_key_exists('USER_ID', $_SESSION) and isAdminUser($_SESSION['USER_ID']))
{
	if($_GET['new_user_id'])
	{
		$_SESSION['USER_ID'] = $_GET['new_user_id'];
	}
//	show_pending_users_table();
}

if (array_key_exists('USER_ID', $_SESSION) and $_SESSION['USER_ID'])
{
	$group_id = do_query('select group_id from user, user_group where user.id = user_group.user_id and user.id = '.$_SESSION['USER_ID'],'scouts');
	if($group_id)
	{
		$picture_categories = fetch_array_data('select id as value, name from picture_categories, scout_picture_group where picture_categories.id = scout_picture_group.category_id and scout_picture_group.group_id = '.$group_id.' order by id desc','scouts');
	}
}

if (array_key_exists('USER_ID', $_SESSION) and $_SESSION['USER_ID'] and $group_id and count($picture_categories))
{
	$gallery = new PictureGallery($group_id, isUser('scoutmaster'), 'pictures', 'picture_categories', 'scout_picture_group', 'scouts');

	echo '<div style="height: 600px;"><center>'.$gallery->getSlideShow(0, 'pictures.php', 10).'</center></div>';
}
else
{
	echo "\n";
	if (1)//$_SESSION['USER_ID'] == 1)
	{
		//echo '<div style="width: 240px; font-size: 80%" style="float: left; border-right: 1px dotted white; border-bottom: 1px dotted white; padding: 10px; margin-right: 25px; text-align: justify;">'."\n";
		echo '<div style="color: black; background-color: white; width: 280px; font-size: 80%; float: left; border-right: 2px dotted yellow; border-bottom: 2px dotted yellow; padding: 10px; margin-right: 25px; text-align: justify;">'."\n";
		echo '<span style="font-weight: bold; font-size: 120%;">News:</span><br />'."\n";
		echo '<hr />'."\n";
		
		 
		$news_items = Array(Array('date' => 'April 25th, 2010',
								  'title' => 'Rank Advancement Requirements Updated',
								  'info' => '+ The Scout Rank Advancement was added.  <b>When you pass off a rank advancement for a boy you will see their rank revert to "No Rank" until you pass off the requirements for the Scout Rank Advancement.</b><br/>'.
											'In order to bring the rank advancement requirements up to date, the following requirements were added:<br/>'.
											'+ Using the EDGE method, teach another person how to tie the square knot.  (Tenderfoot req #4c)<br/>'.
											'+ Discuss the principles of Leave No Trace.  (Second class req #2)<br/>'.
											'+ Explain the three R\'s of personal safety and protection.  (Second Class req #9b)<br/>'.
											'+ Describe the three things you should avoid doing related to the use of the Internet. Describe a cyberbully and how you should respond to one. (First Class req #11)<br/>'.
											'+ While a Star Scout, use the EDGE method to teach a younger Scout the skills from ONE of the following six choices... (Life req #6)<br/>'.
											'+ The wording in 24 other requirements was updated too.'),
							Array('date' => 'April 11th, 2010',
		                          'title' => 'User Notes Improved',
		                          'info' => 'User notes on awards have been improved.  Only notes entered by the current troop are displayed.  A \'Delete\' button has been added to remove notes that are no longer relevant.<br/>-Mark'),
							Array('date' => 'March 14th, 2010',
		                          'title' => 'Merit Badge Requirements Updated',
		                          'info' => 'BSA changes requirments from time to time.  The following merit badges had one or more requirements change in 2008-2009 and the site has been updated to reflect those changes: Architecture, Automotive Maintenance, Backpacking, Cinematography, Coin Collecting, Collections, Composite Materials (new in 2006 but missing from boyscoutwebsite.com until today), Drafting, Emergency Preparedness, Engineering, Farm Mechanics, First Aid, Graphic Arts, Hiking, Insect Study, Metalwork, Motorboating, Painting, Pottery, Radio, Sculpture, Swimming, Wilderness Survival, Water Sports.<br/>-Mark'));
								  
		/*$news_items = Array(Array('date' => 'April 19th, 2009',
		                          'title' => 'Calendar Bug Fixed',
		                          'info' => 'I\'ve fixed a defect with the calendar that caused some events from other troops to appear on other troop\'s calendars.  I apologize any inconvenience this may have caused.  If you see a problem you\'d like to report please e-mail us at web-master@boy-scout-website.com.<br/><b>Note:  This e-mail address is intentionally misspelled.  The actual e-mail address contains no dashes.</b>  I\'ve placed dashes in the address to throw off spam bots.  If you\'re curious what I mean, look at <a href="http://en.wikipedia.org/wiki/Spambot">http://en.wikipedia.org/wiki/Spambot</a> for more info.<br/>-Mark'));*/
		/*
		$news_items = Array(Array('date' => 'November 11th, 2007',
		                          'title' => '"Awards" Tab Created',
		                          'info' => 'I\'ve added a tab at the top called "Awards", which should make it easier to track '.
		                                    'awards presented to scouts.  When all requirements are completed, the award will show as '.
		                                    'being completed by the scout that finished the award.  "Presented" will show as "Not Ordered", until '.
		                                    'someone with the scoutmaster privilege (including a board member) clicks the update button and changes '.
		                                    'the status of the award to "Presented" or "Ordered".  The date the award was presented may also be recorded.'),
		                    Array('date' => 'September 25th, 2007',
		                          'title' => 'News Column Created',
		                          'info' => 'Hi,<br/>'.
		                                    'This is Mark, one of the website administrators for this site.  '.
		                                    'I\'m adding a little column here called &quot;news&quot; to help '.
		                                    'communicate information about changes to boyscoutwebsite.com.'),
		                    Array('date' => 'September 25th, 2007',
		                          'title' => 'Merit Badge Requirements Updated',
		                          'info' => 'After our troop came back from scout camp this July I went to '.
		                                    'update our records with merit badges our boys had worked on.  '.
		                                    'I noticed that the requirements for many of our merit badges were out of date on the site. '.
		                                    'After doing more work than you\'d think something like this would take, '.
		                                    'I\'ve updated all requirements to their current versions. '.
		                                    'In addition, I\'ve updated the site so it can track different requirement versions.  '.
		                                    'If the boys in your troop started working on Environmental Science last year, '.
		                                    'they can finish the merit badge using the version they started with.  '.
		                                    'The boys who haven\'t started yet will automatically use the new version.'));*/
		$last_date = '';
		foreach ($news_items as $item)
		{
			if ($last_date != $item['date']){
				echo '<b>'.$item['date'].'</b><br />'."\n";
				$last_date = $item['date'];
			}
			echo '<b style="text-decoration: underline;">'.$item['title'].'</b><br/>'."\n";
			echo '<div style="padding-left: 10px">'.$item['info'].'</div>'."\n";
		}
		echo '</div>'."\n";
	}
	
	echo '<br />The Boy Scout Website is a free site that provides the ability to track the progress of each scout in your troop.<br />'."\n";
	echo 'This site is not affiliated with the Boy Scouts of America, the Boy Scout Association, the British Boy Scouts, or any other scouting association.<br />';
	echo 'To get started, click the <i>Login</i> link above and create a user account.<br />'."\n";
	
	echo '<br /><span style="font-weight: bold; font-size: 120%;">Features:</span><br />'."\n";
	echo '<ul>'."\n";
	echo '<li>Tracks the progress of individual scouts toward rank advancements and merit badges.</li>'."\n";
	echo '<li>Displays the progress of all the boys in one view to be able to see what requirements the boys are in need of at a glance.</li>'."\n";
	echo '<li>Provides a simple interface to quickly sign off requirements.</li>'."\n";
	echo '<li>A calendar page allows you to post what is coming up and what requirements you\'ll be passing off.</li>'."\n";
	echo '</ul>'."\n";
		
	echo '<span style="font-weight: bold; font-size: 120%; ">Screenshots:</span><br /><div style="float: left; width: 60%; background-color: gray;">'."\n";
	$screenshots = Array('requirements','award_report','rank_summary','calendar','membership_list','membership_add_edit');
	$size = 200;
	foreach ($screenshots as $image)
	{
		echo '<div style="float: left; text-align: center; height: '.($size + 65).'px; width: '.($size + 50).'px; padding: 10px;"><a href="images/'.$image.'.jpg"><img border="0" src="resize.php?width='.$size.'&amp;height='.$size.'&amp;picture=images/'.$image.'.jpg" /></a><br /><span style="font-weight: bold;">'.preg_replace('/_/',' ',$image).'</span></div>';
	}
	echo '<div style="clear: both;"></div></div><div style="clear: both;">';
	
}

echo '<br /><span style="font-weight: bold; font-size: 120%;">External Links:</span><br />'."\n";
echo '<a href="http://www.scouting.org/">BSA Homepage</a><br />'."\n";
echo '<a href="http://www.scout.org/">World Scouting Homepage</a>'."\n";

echo '<br /><br />'."\n";
echo 'For more information on this site or help getting started using this site to track your Scouts, contact us at <a href="mailto: '.SITE_ADMIN_EMAIL.'">'.SITE_ADMIN_EMAIL.'</a>'."\n";

$pt->writeFooter();


?>