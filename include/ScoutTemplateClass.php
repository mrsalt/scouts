<?php

require_once 'scout_globals.php';
require_once 'PageTemplateClass.php';

class ScoutTemplate extends PageTemplate
{	
	function ScoutTemplate()
	{
		$this->PageTemplate();
		$this->setLink('homepage','Home','index.php');
		//PageTemplate::setLink('link1','Picture Gallery','/gallery.php');
		//PageTemplate::setLink('link2','Book Club','bookclub.php');
		$this->setLink('requirements','Requirements','requirements.php');
		if (isset($_SESSION['USER_ID']))
		{
			$this->setLink('awards','Awards','awards.php');
			$this->setLink('calendar','Calendar','calendar.php');
			$this->setLink('membership','Membership','membership.php');
			$this->setLink('pictures','Pictures','pictures.php');
			if(isAdminUser($_SESSION['USER_ID']))
			{
				$this->setLink('todo','Admin','todo.php');
			}
			$this->setLink('login','Logout','logout.php');
		}
		else
			$this->setLink('login','Login','login.php');
		//PageTemplate::setLink('link2','Default Link 2','nowhere2.com');
		$this->menu_color = '#ACACAC';
		$this->addStyle('a','color: red; line-height: 1.5em; text-decoration: none; font-weight: bold;');
		$this->addStyle('body','margin: 5px 5px 5px 5px; font-family: Verdana; background: #003030; color: white;');
		//margin-left: 10%; margin-right: 10%;
  	$this->addStyle('h1','margin-left: -30px;');
  	$this->addStyle('h2,h3,h4,h5,h6','margin-left: -15px;');
  	$this->addStyle('.scout_report','padding: 5px 25px 5px 25px; background: white; color: black; border: solid black;');
  	$this->addStyle('.scout_report.h1,.scout_report.h2,.scout_report.h3,.scout_report.h4,.scout_report.h5','margin-left: 0px; color: black;');
  	$this->addStyle('table.main','border-collapse: collapse; empty-cells: show;');
  	$this->addStyle('.dark-green-header','background-color: #620002; font-weight: bold; padding: 3px; text-align: center; font-variant: small-caps; border-left: 1px solid black; border-right: 1px solid black;');
  	$this->addStyle('.dark-green-0','background-color: #006A6A; padding: 3px; border: 1px solid black; ');
  	$this->addStyle('.dark-green-1','background-color: #009393; padding: 3px; border: 1px solid black; ');
  	$this->addStyle('table.scout_form','padding: 5px 25px 5px 25px; background: white; color: black; border: 1px solid black;');
  	
  	if (browser_is_firefox())
	{
  		$this->addStyle('li.requirement','padding-left: 1.5em; text-indent: -1.5em;'); 	
  		$this->addStyle('li.req_edit','padding-left: 1.5em; text-indent: -1.5em; cursor: pointer;');
  	}
  	else  // assume IE
  	{     // IE treats text-indent differently than firefox does
  		$this->addStyle('li.requirement','text-indent: -1.5em;'); 	
  		$this->addStyle('li.req_edit','text-indent: -1.5em; cursor: pointer;');
  	}
  	
  	$this->addStyle('td.menu_node','border: 1px solid black; padding: 5px 6px 5px 6px; -moz-border-radius: 7px 7px 0px 0px;');
  	//$this->setBodyStyle('margin', '5, 5, 5, 5');
//		$this->setBodyStyle('font-family', 'Verdana');
//		$this->setBodyStyle('background', '#003030');
//		$this->setBodyStyle('color', 'white');
	}
		
	function writeBanner()
	{
		$text = '';		
		//$this->setBodyTag('onLoad','onLoadHandler()');
		//$this->setBodyTag('onunload','stop()');
		//$this->addScript('scripts/crayola.js');
		PageTemplate::startHTML();
		PageTemplate::writeHead();
		PageTemplate::startBody();

		if(!$_GET['printpage'])
		{
			echo '<table cellspacing=0 cellpadding=0 width=100% style="border: 1px solid black;">';
			//echo '<tr style="background-image: url(\'images/header_blank.jpg\'); background-repeat: repeat; width: 100%;">';
			echo '<tr>';
			/* Removed for copyright infringement
			echo '<td style="width: 114px;">';
			
				echo '<img src="images/34886.jpg" border="0">';
			echo '</td>';
			*/
			if (isset($_SESSION['USER_ID']))
				$troop_image_file = 'images/scout_header_'.do_query('SELECT scout_troop_id FROM user WHERE id = '.$_SESSION['USER_ID'],'scouts').'.jpg';
			if (isset($_SESSION['USER_ID']) and file_exists($troop_image_file))
				echo '<td><img src="'.$troop_image_file.'" border="0" id="header_image" /></td>';
			else
				echo '<td><img src="images/scout_header_general.jpg" border="0" id="header_image" /></td>';
			//echo '<td><img src="images/website.jpg" border="0">';
			//echo '<div style="position: relative; top:30; left:10;"><a href="javascript: toggle_image_cycle();" id="cycle_image_link" style="font-size: 8pt;">Stop Animation</a></div>';
			//echo '</td>';
			echo '</tr></table>';
		}
	}
	
	function writeMenu()
	{
		PageTemplate::writeMenu();
		if ($GLOBALS['troop_id'])
		{
			$this->writeLoginBanner();
		}
	}
	
	function writeLoginBanner()
	{
		if($GLOBALS['troop_id'])
		{
			echo '<div style="font-size: larger; background: black; padding-left: 10px; padding-right: 10px; padding-top: 2px; padding-bottom: 2px;">';
			echo '<table width="100%"><tr><td>Boy Scout Troop '.$GLOBALS['troop_info']['troop_number'].', '.$GLOBALS['troop_info']['council'];
			echo '</td><td style="text-align: right;">Welcome '.$GLOBALS['user_info']['name'].'</td></tr></table>';
			echo '</div>';
		}
	}
}

?>
