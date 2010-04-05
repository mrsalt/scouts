<?php

require_once 'include/scout_globals.php';
require_once 'include/picture_functions.php';

$pt = new ScoutTemplate();
$pt->setPageTitle( SITE_TITLE );
$pt->addStyleSheet('css/basic.css');
$pt->addStyle('div.picture','float: left; text-align: center; height: 265px; width: 250px; padding: 10px;');
$pt->addStyle('div.pano','float: left; text-align: center; height: 265px; width: 850px; padding: 10px;');
$pt->addStyle('div.spacer','clear: both;');
$pt->writeBanner();
$pt->writeMenu();

if(!$_SESSION['USER_ID'])
{
	echo 'Error: You must be logged in to view this page.';
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
	echo 'You cannot use the picture page until there is at least one group defined for your troop.';
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
		echo $group_sep.'<b>['.$group['group_name'].']</b>';
	}
	else
	{
		echo $group_sep.'<a href="pictures.php?group_id='.$group['id'].'">['.$group['group_name'].']</a>';
	}
	$group_sep = '&nbsp;&nbsp;';
}
echo '<br />';



$gallery = new PictureGallery($group_id, isUser('scoutmaster'), 'pictures', 'picture_categories', 'scout_picture_group', 'scouts');

$gallery->handleRequest();

if($_REQUEST['picture_id'])
{
	echo $gallery->getPicturePage($_REQUEST['picture_id'], $_SESSION['height'], $_SESSION['width']);
}
else if($_REQUEST['upload_pictures'] == 1)
{
	if($_POST['Upload'])
	{
		$gallery->uploadPictures();
	}
	echo $gallery->getUploadPage($_SESSION['category_id']);
}
else if($gallery->getManageValue() == 'categories')
{
	echo $gallery->getCategoryManagePage();
}
else
{
	echo $gallery->getCategoryPage($_SESSION['category_id']);
}

$pt->writeFooter();

?>
