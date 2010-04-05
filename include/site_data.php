<?php

$site_host_name = get_cfg_var ( 'site_host_name' );

if ( $site_host_name == 'qwest_host_2' )
{
	define('MYSQL_SERVER', 'localhost');
	define('MYSQL_USER', 'www');
	define('MYSQL_PASSWORD', 'nj534fgrk');
	define('DEFAULT_DB','scouts');
}
else
{
	define('MYSQL_SERVER', 'db197.perfora.net');
	define('MYSQL_USER', 'dbo127142764');
	define('MYSQL_PASSWORD', 'scoutdbpass');
	define('DEFAULT_DB','db127142764');
}

?>