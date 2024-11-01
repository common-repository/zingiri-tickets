<?php
/*********************************************************************
    setttings.php

    Static osTicket configuration file. Mainly useful for mysql login info.
    Created during installation process and shouldn't change even on upgrades.
   
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

#Disable direct access.
if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__)) || !defined('ROOT_PATH')) die('kwaheri rafiki!');

#Install flag
define('OSTINSTALLED',TRUE);
if(OSTINSTALLED!=TRUE){
    if(!file_exists(ROOT_PATH.'setup/install.php')) die('Error: Contact system admin.'); //Something is really wrong!
    //Invoke the installer.
    header('Location: '.ROOT_PATH.'setup/install.php');
    exit;
}

unset($_SESSION['abort']);

# Encrypt/Decrypt secret key - randomly generated during installation.
if ($_REQUEST['zing_secret_salt']) define('SECRET_SALT',$_REQUEST['zing_secret_salt']); 
else define('SECRET_SALT','8F913FD8F64C43B');

#Default admin email. Used only on db connection issues and related alerts.
define('ADMIN_EMAIL',base64_decode($_REQUEST['zing_admin_email']));

#Mysql Login info
define('DBTYPE','mysql');
define('ABSPATH', dirname(__FILE__) . '/');
if (!file_exists(dirname(__FILE__).'/../../../../../../wp-config.php')) {
	die('Could not find WP configuration file');
}
require(dirname(__FILE__).'/../../../../../../wp-config.php');
define('DBHOST',DB_HOST); 
define('DBNAME',DB_NAME);
define('DBUSER',DB_USER);
define('DBPASS',DB_PASSWORD);

#Table prefix
//define('TABLE_PREFIX',$table_prefix."zing_ost_");
define('TABLE_PREFIX',$_REQUEST['zing_prefix']."zing_ost_");

?>
