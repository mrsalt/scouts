<?php
require_once 'scout_globals.php';
$pt = new ScoutTemplate();
$pt->setPageTitle( SITE_TITLE.' - PHP INFO' );
$pt->writeBanner();
$pt->writeMenu();

echo 'Site Configuration: '.get_cfg_var ( 'site_host_name' );
phpinfo();
$pt->writeFooter();
?>