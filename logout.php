<?php
unset($_SESSION['USER_ID']);

require_once 'include/scout_globals.php';
require_once 'login_include.php';

ob_start();
unset($_SESSION['USER_ID']);
$pt = new ScoutTemplate();
$pt->addScript('scripts/md5.js');
$pt->setPageTitle( SITE_TITLE.' - Logged Out' );
$pt->writeBanner();
$pt->writeMenu();
echo '<br><div style="font-size: larger">You are now logged out from '.SITE_TITLE.'.</div>';
ShowLoginPage(null);
$pt->writeFooter();
?>