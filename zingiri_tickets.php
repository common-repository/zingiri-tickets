<?php
/*
 Plugin Name: Support Tickets Center
 Plugin URI: http://www.zingiri.com/plugins-and-addons/tickets/
 Description: Support Tickets Center adds state of the art ticketing support functionality to your website.
 Author: Zingiri
 Version: 3.0.3
 Author URI: http://www.zingiri.com/
 */

define("ZING_TICKETS_VERSION","3.0.3");

// Pre-2.6 compatibility for wp-content folder location
if (!defined("WP_CONTENT_URL")) {
	define("WP_CONTENT_URL", get_option("siteurl") . "/wp-content");
}
if (!defined("WP_CONTENT_DIR")) {
	define("WP_CONTENT_DIR", ABSPATH . "wp-content");
}

if (!defined("ZING_TICKETS_PLUGIN")) {
	$zing_tickets_plugin=str_replace(realpath(dirname(__FILE__).'/..'),"",dirname(__FILE__));
	$zing_tickets_plugin=substr($zing_tickets_plugin,1);
	define("ZING_TICKETS_PLUGIN", $zing_tickets_plugin);
}

if (!defined("ZING_TICKETS_SUB")) {
	if (get_option("siteurl") == get_option("home"))
	{
		define("ZING_TICKETS_SUB", "wp-content/plugins/".ZING_TICKETS_PLUGIN."/osticket/upload/");
	}
	else {
		define("ZING_TICKETS_SUB", "wordpress/wp-content/plugins/".ZING_TICKETS_PLUGIN."/osticket/upload/");
	}
}
if (!defined("ZING_TICKETS_DIR")) {
	define("ZING_TICKETS_DIR", WP_CONTENT_DIR . "/plugins/".ZING_TICKETS_PLUGIN."/osticket/upload/");
}

if (!defined("ZING_TICKETS_LOC")) {
	define("ZING_TICKETS_LOC", WP_CONTENT_DIR . "/plugins/".ZING_TICKETS_PLUGIN."/");
}

if (!defined("ZING_TICKETS_URL")) {
	define("ZING_TICKETS_URL", WP_CONTENT_URL . "/plugins/".ZING_TICKETS_PLUGIN."/");
}
if (!defined("ZING_TICKETS_LOGIN")) {
	define("ZING_TICKETS_LOGIN", get_option("zing_tickets_login"));
}

if (!defined("BLOGUPLOADDIR")) {
	$upload=wp_upload_dir();
	define("BLOGUPLOADDIR",$upload['path']);
}

if (file_exists(dirname(__FILE__).'/source.inc.php')) require(dirname(__FILE__).'/source.inc.php');

$zing_tickets_version=get_option("zing_tickets_version");
if ($zing_tickets_version) {
	add_action("init","zing_tickets_init");
	add_filter('the_content', 'zing_tickets_content', 10, 3);
	add_action('wp_head','zing_tickets_header');
	add_action('admin_head','zing_tickets_admin_header');
	add_action('admin_notices','zing_tickets_admin_notices');
}
add_action('admin_head','zing_tickets_admin_header_support');

if (zing_tickets_remote()) require_once(dirname(__FILE__) . '/includes/misc2.inc.php');
else require_once(dirname(__FILE__) . '/includes/misc1.inc.php');
require_once(dirname(__FILE__) . '/includes/shared.inc.php');
require_once(dirname(__FILE__) . '/includes/http.class.php');
require_once(dirname(__FILE__) . '/includes/integrator.inc.php');
require_once(dirname(__FILE__) . '/includes/log.inc.php');
require_once(dirname(__FILE__) . '/controlpanel.php');

function zing_tickets_admin_notices() {
	$messages=array();

	$upload=wp_upload_dir();
	if ($upload['error']) $messages[]=$upload['error'];
	if (session_save_path() && !is_writable(session_save_path())) $messages[]='PHP sessions are not properly configured on your server, the sessions save path '.session_save_path().' is not writable.';
	if (phpversion() < '5')	$messages[]="You are running PHP version ".phpversion().". We recommend you ugprade to PHP version 5.2 or higher.";
	if (ini_get("zend.ze1_compatibility_mode")) $messages[]="You are running PHP in PHP 4 compatibility mode. We recommend to turn this mode off.";
	if (!get_option("zing_tickets_version")) $messages[]='Please proceed with a clean install or deactivate your plugin';
	elseif (get_option("zing_tickets_version") != ZING_TICKETS_VERSION) $messages[]='You downloaded version '.ZING_TICKETS_VERSION.' and need to upgrade your database (currently at version '.get_option("zing_tickets_version").').';
	if (!zing_tickets_remote()) {
		if (!ini_get('short_open_tag')) $messages[]='Short open tag disabled! - osTicket requires it turned ON.';
		if (!is_writable(ZING_TICKETS_DIR.'images/captcha')) $messages[]='If you want to use captcha on ticket submission, make sure the directory osticket/upload/images/captcha in the plugin directory is writable';
	}

	if ($messages) {
		foreach ($messages as $message) {
			echo "<div id='zing-warning' style='background-color:greenyellow' class='updated fade'><p><strong>".$message."</strong> "."</p></div>";
		}
	}

}


function zing_tickets_output($process) {
	global $post;
	global $wpdb;
	global $cfg;
	global $thisuser;
	global $nav;
	global $zing_tickets_loaded,$zing_tickets_mode;

	$content="";

	switch ($process)
	{
		case "content":
			if (isset($_POST['zname'])) {
				$_POST['name']=$_POST['zname'];
				unset($_POST['zname']);
			}
			if (isset($post)) $cf=get_post_custom($post->ID);
			if (isset($_GET['zpage']))
			{
				$to_include=$_GET['zpage'];
				$zing_tickets_mode="client";
			}
			elseif (isset($_GET['zscp']))
			{
				$to_include="scp/".$_GET['zscp'];
				$zing_tickets_mode="admin";
			}
			elseif (isset($_GET['zsetup']))
			{
				$to_include="setup/".$_GET['zscp'];
				$zing_tickets_mode="setup";
			}
			elseif (isset($cf['zing_tickets_page']))
			{
				$zing_tickets_mode="client";
				$to_include=$cf['zing_tickets_page'][0];
			}
			elseif (isset($cf['zing_tickets_page']) && ($cf['zing_tickets_page'][0]=='admin'))
			{
				$to_include="scp/".$_GET['zscp'];
				$zing_tickets_mode="admin";
			}
			elseif ($zing_tickets_mode=='menu')
			{
				$to_include="scp/index";
				$zing_tickets_mode="admin";
			}
			else
			{
				return false;
			}
			if (isset($cf['cat'])) {
				$_GET['cat']=$cf['cat'][0];
			}
			break;
		default:
			return $content;
			break;
	}

	if (get_option("zing_tickets_subscribers") == "Subscribers" && !is_user_logged_in()) {
		$content="Access not allowed, please register or login first";
		return $content;
	}

	zing_tickets_login();
	$http=zing_tickets_http("osticket",$to_include.'.php');
	$news = new zHttpRequest($http,'zingiri-tickets');
	if ($news->live()) {
		$news->follow=true;
		$output=$news->DownloadToString();
		//die($output);
		if ((isset($_REQUEST['zscp']) && $_REQUEST['zscp']=='ajax')) {
			ob_end_clean();
			$content=$output;
			echo $content;
			die();
		} elseif ((isset($_REQUEST['zpage']) && $_REQUEST['zpage']=='attachment') || (isset($_REQUEST['zscp']) && $_REQUEST['zscp']=='attachment')) $content=$output;
		else $content.=zing_tickets_ob($output);
		return $content;
	}
}

function zing_tickets_mainpage() {
	$ids=get_option("zing_tickets_pages");
	$ida=explode(",",$ids);
	return $ida[0];
}

function zing_tickets_ob($buffer) {
	global $current_user,$zing_tickets_mode;

	if (in_array($buffer,array('NEW','UPGRADE'))) return $buffer;
	
	$admin=get_option('siteurl').'/wp-admin';

	$pageID=zing_tickets_mainpage();
	if (get_option('permalink_structure')){
		$homePage = get_option('home');
		$wordpressPageName = get_permalink($pageID);
		$wordpressPageName = str_replace($homePage,"",$wordpressPageName);
		$home=$homePage.$wordpressPageName;
		if (substr($home,-1) != '/') $home.='/';
		$and='?';
	}else{
		$home=get_option('home').'/?page_id='.$pageID;
		$and='&';
	}

	//login
	if (strpos($buffer,'loginBody')===false) $loginPage=false;
	else $loginPage=true;

	$buffer=zing_integrator_tags($buffer,"ost");
	$buffer=str_replace('"style.css"','"'.zing_ost_url().'/setup/style.css"',$buffer);

	$buffer=str_replace('width="940"','width="100%"',$buffer);

	//admin
	if ($zing_tickets_mode=="admin") {
		/*
		foreach (array('login','scp','main','style','tabs','autosuggest_inquisitor') as $css) {
			$buffer=str_replace('css/'.$css.'.css',ZING_TICKETS_URL.'css/admin/'.$css.'.css',$buffer);
		}
		*/
		$f[]='/<script type="text\/javascript" src="(.*?)">/';
		$r[]='<script type="text/javascript" src="'.zing_ost_url().'scp/$1">';

		$f[]='/<link rel="stylesheet" type="text\/css" href="(.*?)"\/>/';
		$r[]='<link rel="stylesheet" type="text/css" href="'.zing_ost_url().'scp/$1">';
		$buffer=preg_replace($f,$r,$buffer);

		foreach (array('index','admin','tickets','kb','directory','profile','logout','attachment','dashboard','canned','settings','logs','helptopics','filters','slas','apikeys','emails','banlist','templates','emailtest','staff','teams','groups','departments','categories','faq') as $page) {
			if (is_admin()) {
				$buffer=str_replace('"'.$page.'.php?','"'.$admin.'/admin.php?page=zingiri-tickets-admin&zscp='.$page.'&',$buffer);
				$buffer=str_replace('"'.$page.'.php"','"'.$admin.'/admin.php?page=zingiri-tickets-admin&zscp='.$page.'"',$buffer);
				$buffer=str_replace("'".$page.'.php?',"'".$admin.'/admin.php?page=zingiri-tickets-admin&zscp='.$page.'&',$buffer);
				$buffer=str_replace("'".$page.'.php"',"'".$admin.'/admin.php?page=zingiri-tickets-admin&zscp='.$page.'"',$buffer);

			} else {
				$buffer=str_replace('"'.$page.'.php?','"'.$home.'/index.php?page_id='.$pid.'&zscp='.$page.'&',$buffer);
				$buffer=str_replace('"'.$page.'.php"','"'.$home.'/index.php?page_id='.$pid.'&zscp='.$page.'"',$buffer);
				$buffer=str_replace("'".$page.'.php?',"'".$home.'/index.php?page_id='.$pid.'&zscp='.$page.'&',$buffer);
				$buffer=str_replace("'".$page.'.php"',"'".$home.'/index.php?page_id='.$pid.'&zscp='.$page.'"',$buffer);
			}
		}
		$buffer=preg_replace('/.a id="logo".*a>/','',$buffer);
		$buffer=str_replace("src='images/","src='".zing_ost_url()."/scp/images/",$buffer);
		$buffer=str_replace('src="images/','src="'.zing_ost_url().'/scp/images/',$buffer);
		$buffer=str_replace('src="./images/','src="'.zing_ost_url().'/scp/images/',$buffer);
		$buffer=str_replace('src="autocron.php"','src="'.zing_ost_url().'/scp/autocron.php"',$buffer);

		//remove Change password option
		if (get_option('zing_tickets_login') == "WP") zing_integrator_cut($buffer,'<li><a class="userPasswd"','</li>');

		//missing div causes themes to break
		if (!zing_tickets_remote() && !$loginPage) $buffer='<div>'.$buffer;

		$buffer=str_replace('src="../captcha.php"','src="'.$home.$and.'zpage=captcha&zscp=ajax"',$buffer);

		$buffer=zing_tickets_translate($buffer);

		//client
	} elseif ($zing_tickets_mode=="client") {
		$buffer=str_replace('name="name"','name="zname"',$buffer);
		foreach (array('tickets','open','view','logout','attachment','kb/index') as $page) {
			$buffer=str_replace("'".$page.'.php?',"'".$home.$and.'zpage='.$page.'&',$buffer);
			$buffer=str_replace('"'.$page.'.php?','"'.$home.$and.'zpage='.$page.'&',$buffer);
			$buffer=str_replace('"'.$page.'.php"','"'.$home.$and.'zpage='.$page.'"',$buffer);
		}
		if (isset($_REQUEST['zpage']) && substr($_REQUEST['zpage'],0,3) == 'kb/') {
			foreach (array('index','faq') as $page) {
				$buffer=str_replace("'".$page.'.php?',"'".$home.$and.'zpage=kb/'.$page.'&',$buffer);
				$buffer=str_replace('"'.$page.'.php?','"'.$home.$and.'zpage=kb/'.$page.'&',$buffer);
				$buffer=str_replace('"'.$page.'.php"','"'.$home.$and.'zpage=kb/'.$page.'"',$buffer);
			}
		}
		foreach (array('main','colors') as $css) {
			$buffer=str_replace('./styles/'.$css.'.css',ZING_TICKETS_URL.'css/client/'.$css.'.css',$buffer);
		}
		$buffer=str_replace('id="header"','id="header" style="display:none"',$buffer);
		$buffer=str_replace('action="login.php"','action="'.$home.$and.'zpage=login"',$buffer);
		$buffer=str_replace("src='images/","src='".zing_ost_url()."/images/",$buffer);
		$buffer=str_replace('src="images/','src="'.zing_ost_url().'/images/',$buffer);
		$buffer=str_replace('src="./images/','src="'.zing_ost_url().'/images/',$buffer);
		$buffer=str_replace('<ul id="nav">','<ul id="ostnav">',$buffer);
		if (get_option("zing_tickets_subscribers") == "Subscribers" && is_user_logged_in()) {
			zing_integrator_cut($buffer,'<a class="log_out"','</a>');
		}
		if (is_user_logged_in()) {
			$f[]='/<input type="text" name="name" size="25" value="">/';
			$r[]='<input type="text" name="name" size="25" value="'.$current_user->data->user_nicename.'">';
			$f[]='/<input type="text" name="email" size="25" value="">/';
			$r[]='<input type="text" name="email" size="25" value="'.$current_user->data->user_email.'">';
			$buffer=preg_replace($f,$r,$buffer,-1,$count);
		}
		$buffer=str_replace('src="captcha.php"','src="'.$home.$and.'zpage=captcha&zscp=ajax"',$buffer);

		$buffer=zing_tickets_translate($buffer);
	}

	$buffer=str_replace('<div id="content"','<div',$buffer);

	return '<!--buffer:start-->'.$buffer.'<!--buffer:end-->';
}

function zing_tickets_translate($buffer) {
	$lang=get_option("zing_tickets_lang");
	if ($lang=='' or $lang=='en') return $buffer;
	require(dirname(__FILE__).'/langs/en.inc.php');
	require(dirname(__FILE__).'/langs/'.$lang.'.inc.php');
	$c=get_defined_constants(true);
	foreach ($c['user'] as $n => $v) {
		if (strstr($n,'en_') !==false) {
			$m=str_replace('en_',$lang.'_',$n);
			if (defined($m)) {
				$buffer=str_replace($v,constant($m),$buffer);
				$buffer=str_replace(ucwords($v),ucwords(constant($m)),$buffer);
			}
		}
	}
	return $buffer;
}

/**
 * Page content filter
 * @param $content
 * @return unknown_type
 */
function zing_tickets_content($content) {
	global $zing_tickets_content;

	if ($zing_tickets_content) $content=$zing_tickets_content;
	return $content;
}

function zing_tickets_admin_header_support()
{
	echo '<link rel="stylesheet" type="text/css" href="' . ZING_TICKETS_URL . 'admin.css" media="screen" />';
}

/**
 * Initialization of page, action & page_id arrays
 * @return unknown_type
 */
function zing_tickets_init()
{
	global $zing_tickets_post;
	if (isset($_POST['name']) && (isset($_POST['submit_x']) || isset($_POST['submit']))) {
		$zing_tickets_post['name']=$_POST['name'];
		unset($_POST['name']);
	}

	ob_start();
	session_start();
	if (isset($_GET['zpage']) && !isset($_GET['page_id'])) $_GET['page_id']=zing_tickets_mainpage();
}

function zing_tickets_remote() {
	global $wpdb;
	$query="show tables like '".$wpdb->prefix."zing_ost_config'";
	$rows=$wpdb->get_results($query);
	if (count($rows) > 0) return false;
	else return true;
}

function zing_tickets_default_page() {
	$pageID=zing_tickets_mainpage();
	if (get_option('permalink_structure')){
		$homePage = get_option('home');
		$wordpressPageName = get_permalink($pageID);
		$wordpressPageName = str_replace($homePage,"",$wordpressPageName);
		$home=$homePage.$wordpressPageName;
		if (substr($home,-1) != '/') $home.='/';
		$and='?';
	}else{
		$home=get_option('home').'/?page_id='.$pageID;
		$and='&';
	}
	return $home.$and;


}
