<?php
/**
 * Local version
 */
if ($zing_tickets_version) {
	add_filter('upgrader_pre_install', 'zing_tickets_pre_upgrade', 9, 2);
	add_filter('upgrader_post_install', 'zing_tickets_post_upgrade', 9, 3);
}

function zing_tickets_http($module,$to_include="index",$page="",$key="") {
	global $wpdb;

	$vars="";
	if (!$to_include || $to_include==".php") $to_include="index";
	$http=zing_ost_url().'/';
	$http.= $to_include;
	$and="";

	$get=$_GET;
	$get['z']=md5(dirname(dirname(__FILE__)).'/osticket/upload/scp/admin.php');
	$get['zing_admin_email']=base64_encode(get_option('admin_email'));
	$get['zing_secret_salt']=get_option('zing_tickets_secret_salt');
	$get['zing_prefix']=$wpdb->prefix;

	if (count($get) > 0) {
		foreach ($get as $n => $v) {
			if ($n!="zpage" && $n!="page_id" && $n!="zscp" && $n!="page") {
				$vars.= $and.$n.'='.zing_urlencode($v);
				$and="&";
			}
		}
	}

	if ($vars) $http.= '?'.$vars;
	return $http;
}

/**
 * Installation: creation of database tables & set up of pages
 * @return unknown_type
 */
function zing_tickets_install() {
	global $wpdb;
	global $current_user;
	global $zing_tickets_options;

	zing_tickets_log();

	zing_tickets_log('Installation/Upgrade');

	$zing_tickets_version=get_option("zing_tickets_version");

	//download
	if (!file_exists(ZING_TICKETS_DIR)) {
		if (!class_exists('ZipArchive')) die('Class ZipArchive doesn\'t exist, try installing it or manually unzip the file osticket.zip in the plugin folder. Then try the upgrade again.');
//unzip_file( $file, $to );
		$to=ZING_TICKETS_LOC.'';
		$file=ZING_TICKETS_LOC.'osticket.zip';	
		$zip = new ZipArchive;
		$res = $zip->open($file);
		if ($res === TRUE) {
			$zip->extractTo($to);
			$zip->close();
			if (file_exists(ZING_TICKETS_LOC.'__MACOSX')) zing_tickets_rrmdir(ZING_TICKETS_LOC.'__MACOSX');
			//unlink($file);
		} else {
			echo 'Failed to install latest copy of osTicket (' . $res . ')';
			die();
		}
	} else {
		//unlink(ZING_TICKETS_LOC.'osticket.zip');
	}

	//create database tables
	$prefix=$wpdb->prefix."zing_ost_";

	if ($handle = opendir(dirname(dirname(__FILE__)).'/db')) {
		while (false !== ($file = readdir($handle))) {
			if (strstr($file,".sql")) {
				//echo $file.'<br />';
				$f=explode("-",$file);
					
				$v=str_replace(".sql","",$f[1]);
				if ($zing_tickets_version < $v) {
					$file_content = file(dirname(dirname(__FILE__)).'/db/'.$file);
					$query = "";
					foreach($file_content as $sql_line) {
						$tsl = trim($sql_line);
						if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#")) {
							$sql_line = str_replace("CREATE TABLE `", "CREATE TABLE `".$prefix, $sql_line);
							$sql_line = str_replace("DELETE FROM `", "DELETE FROM `".$prefix, $sql_line);
							$sql_line = str_replace("INSERT INTO `", "INSERT INTO `".$prefix, $sql_line);
							$sql_line = str_replace("ALTER TABLE `", "ALTER TABLE `".$prefix, $sql_line);
							$sql_line = str_replace("UPDATE `", "UPDATE `".$prefix, $sql_line);
							$sql_line = str_replace("TRUNCATE TABLE `", "TRUNCATE TABLE `".$prefix, $sql_line);
							$query .= $sql_line;

							if(preg_match("/;\s*$/", $sql_line)) {
								//echo $query.'<br />';
								$wpdb->query($query);
								//if (!mysql_query($query)) die('error: '.$query);
								$query = "";
							}
						}
					}
				}
			}
		}
		closedir($handle);
	}

	//default settings
	if ($zing_tickets_version <= '0.1') {
		$query="update ".$prefix."config set admin_email='".get_option('admin_email')."'";
		$query.=",helpdesk_url='".get_option('home')."'";
		$wpdb->query($query);
	}

	//default email
	if ($zing_tickets_version <= '0.1') {
		$query="update ".$prefix."email set email='".get_option('admin_email')."'";
		//$query.=",helpdesk_url='".get_option('home')."'";
		$wpdb->query($query);
	}

	//default user
	if (!$zing_tickets_version) {
		$query="INSERT INTO `".$prefix."staff` (`staff_id`, `group_id`, `dept_id`, `username`, `firstname`, `lastname`, `passwd`, `email`, `phone`, `phone_ext`, `mobile`, `signature`, `isactive`, `isadmin`, `isvisible`, `onvacation`, `daylight_saving`, `append_signature`, `change_passwd`, `timezone_offset`, `max_page_size`, `created`, `lastlogin`, `updated`) VALUES";
		$query.="('".$current_user->data->ID."', 1, 1, '".$current_user->data->user_login."', '".$current_user->first_name."', '".$current_user->last_name."', '".md5($current_user->data->user_pass)."', '".$current_user->data->user_email."', '', '', '', '', 1, 1, 1, 0, 0, 0, 0, 0.0, 0, '".date("Y-m-d")."', NULL, '".date("Y-m-d")."')";
		$wpdb->query($query);
		$query=sprintf("UPDATE `".$prefix."staff` SET `passwd`='%s', `change_passwd`=0 WHERE `username`='%s'",md5($current_user->data->user_pass),$current_user->data->user_login);
		$wpdb->query($query);
	}

	//upgrade osTicket to 1.6 ST
	zing_tickets_login();
	$http=zing_tickets_http("osticket","setup/upgrade.php");
	zing_tickets_log($http);
	$news = new zHttpRequest($http,'zingiri-tickets');
	$news->post=array('step' => 2);
	if ($news->live()) {
		//echo $http;
		$output=$news->DownloadToString();
		zing_tickets_log($output);
	}

	//default options
	if (is_array($zing_tickets_options) && count($zing_tickets_options) > 0) {
		foreach ($zing_tickets_options as $value) {
//			delete_option( $value['id'] );
			if ( isset($value['id']) && isset($value['std']) && !get_option($value['id']) ) update_option( $value['id'], $value['std'] );
		}
	}

	//create standard pages
	if ($zing_tickets_version <= '0.1') {
		$pages=array();
		$pages[]=array("Tickets","tickets","*",0);

		$ids="";
		foreach ($pages as $i =>$p)
		{
			$my_post = array();
			$my_post['post_title'] = $p['0'];
			$my_post['post_content'] = '';
			$my_post['post_status'] = 'publish';
			$my_post['post_author'] = 1;
			$my_post['post_type'] = 'page';
			$my_post['comment_status'] = 'closed';
			$my_post['menu_order'] = 100+$i;
			$id=wp_insert_post( $my_post );
			if (empty($ids)) { $ids.=$id; } else { $ids.=",".$id; }
			if (!empty($p[1])) add_post_meta($id,'zing_tickets_page',$p[1]);
		}
		update_option("zing_tickets_pages",$ids);
	}

	//set comment status to closed
	$ids=get_option("zing_tickets_pages");
	$ida=explode(",",$ids);
	foreach ($ida as $id) {
		$my_post = array();
		$my_post['ID']=$id;
		$my_post['comment_status'] = 'closed';
		wp_update_post($my_post);
	}

	if (!$zing_tickets_version) update_option('zing_tickets_secret_salt',md5(__FILE__.md5(get_option('admin_email'))));

	update_option("zing_tickets_version",ZING_TICKETS_VERSION);

}

/**
 * Uninstallation: removal of database tables
 * @return void
 */
function zing_tickets_uninstall() {
	global $wpdb;

	$tables=array();
	$a='api_key,config,department,email,email_banlist,email_template,groups,help_topic,kb_premade,staff,syslog,ticket,ticket_attachment,ticket_lock,ticket_message,ticket_note,ticket_priority,ticket_response,timezone';
	$tables=explode(",",$a);
	$prefix=$wpdb->prefix."zing_ost_";
	foreach ($tables as $table) {
		$query="drop table ".$prefix.$table;
		$wpdb->query($query);
	}
	$ids=get_option("zing_tickets_pages");
	$ida=explode(",",$ids);
	foreach ($ida as $id) {
		wp_delete_post($id);
	}
	delete_option("zing_tickets_version");
	delete_option("zing_tickets_pages");
	delete_option('zing_tickets_secret_salt');
	delete_option('zing_tickets_remote');
}

function zing_tickets_active_users() {
	global $wpdb;

	echo '<h3>The following Wordpress users are active osTicket users</h3>';

	$prefix=$wpdb->prefix."zing_ost_";
	if (isset($wpdb->base_prefix)) $wpPrefix=$wpdb->base_prefix; else $wpPrefix=$wpdb->prefix;
	$query="select * from `##users`";
	$query=str_replace("##",$wpPrefix,$query);
	$sql = mysql_query($query) or die(mysql_error());
	while ($row = mysql_fetch_array($sql)) {
		if (!isset($row['first_name'])) $row['first_name']='';
		if (!isset($row['last_name'])) $row['last_name']=$row['display_name'];
		if (user_can($row['ID'],'activate_plugins')) { //administrator role
			$query2="REPLACE INTO `".$prefix."staff` (`staff_id`, `group_id`, `dept_id`, `username`, `firstname`, `lastname`, `passwd`, `email`, `phone`, `phone_ext`, `mobile`, `signature`, `isactive`, `isadmin`, `isvisible`, `onvacation`, `daylight_saving`, `append_signature`, `change_passwd`, `timezone_offset`, `max_page_size`, `created`, `lastlogin`, `updated`) VALUES";
			$query2.="('".$row['ID']."', 1, 1, '".$row['user_login']."', '".$row['first_name']."', '".$row['last_name']."', '".md5($row['user_pass'])."', '".$row['user_email']."', '', '', '', '', 1, 1, 1, 0, 0, 0, 0, 0.0, 0, '".date("Y-m-d")."', NULL, '".date("Y-m-d")."')";
			$wpdb->query($query2);
			$query2=sprintf("UPDATE `".$prefix."staff` SET `passwd`='%s', `isadmin`=1, `change_passwd`=0 WHERE `username`='%s'",md5($row['user_pass']),$row['user_login']);
			$wpdb->query($query2);
			$level[$row['user_login']]=8;
		} elseif (user_can($row['ID'],'edit_pages')) { //editor role
			$query2="REPLACE INTO `".$prefix."staff` (`staff_id`, `group_id`, `dept_id`, `username`, `firstname`, `lastname`, `passwd`, `email`, `phone`, `phone_ext`, `mobile`, `signature`, `isactive`, `isadmin`, `isvisible`, `onvacation`, `daylight_saving`, `append_signature`, `change_passwd`, `timezone_offset`, `max_page_size`, `created`, `lastlogin`, `updated`) VALUES";
			$query2.="('".$row['ID']."', 1, 1, '".$row['user_login']."', '".$row['first_name']."', '".$row['last_name']."', '".md5($row['user_pass'])."', '".$row['user_email']."', '', '', '', '', 1, 0, 1, 0, 0, 0, 0, 0.0, 0, '".date("Y-m-d")."', NULL, '".date("Y-m-d")."')";
			$wpdb->query($query2);
			$query2=sprintf("UPDATE `".$prefix."staff` SET `passwd`='%s', `isadmin`=0, `change_passwd`=0 WHERE `username`='%s'",md5($row['user_pass']),$row['user_login']);
			$wpdb->query($query2);
			$level[$row['user_login']]=5;
		} else {
			$query2=sprintf("DELETE FROM `".$prefix."staff` WHERE `username`='%s'",$row['user_login']);
			$wpdb->query($query2);
			$level[$row['user_login']]=1;
		}
	}
	$query="select * from `".$wpPrefix."users`,`".$prefix."staff` where `".$wpPrefix."users`.`user_login`=`".$prefix."staff`.`username`";
	$sql = mysql_query($query) or die(mysql_error());
	while ($row = mysql_fetch_array($sql)) {
		echo $row['user_login'].' - '.$row['user_email'];
		if ($level[$row['user_login']] >= 8) echo ' - admin';
		elseif ($level[$row['user_login']] >= 5) echo ' - staff';

		if (md5($row['user_pass']) != $row['passwd']) echo '!Password not synchronised';
		echo '<br />';
	}

	echo '<p>Users are not synced automatically so please sync the users whenever you create a new user or update a user (change of password, etc).</p>';
	echo '<form method="post">';
	echo '<p class="submit"><input class="button-primary" name="install" type="submit" value="Sync" /> <input type="hidden" name="action" value="install" /></p>';
	echo '</form>';

}

function zing_ost_url() {
	return ZING_TICKETS_URL.'osticket/upload';
}

function zing_tickets_login() {
	global $current_user,$wpdb;

	if (!file_exists(ZING_TICKETS_DIR)) return false;
	if (current_user_can('activate_plugins')  || current_user_can('edit_pages')) {
		$post['do']='scplogin';
		$post['username']=$current_user->data->user_login;
		$post['passwd']=$current_user->data->user_pass;
		$post['submit']='Login';
		$http=zing_tickets_http('osticket','scp/login.php');
	} elseif (defined("ZING_TICKETS_LOGIN") && ZING_TICKETS_LOGIN=="WP" && is_user_logged_in()) {
		//Guest login extension
		zing_tickets_guest_login($http,$post);
	}
	if (isset($http)) {
		$news = new zHttpRequest($http,'zingiri-tickets');
		$news->post=$post;
		$news->follow=false;
		if ($news->live()) {
			$output=$news->DownloadToString();
		}
	}

}

function zing_tickets_footer() {
	$bail_out = ( ( defined( 'WP_ADMIN' ) && WP_ADMIN == true ) || ( strpos( $_SERVER[ 'PHP_SELF' ], 'wp-admin' ) !== false ) );
	if ( $bail_out ) return;

	//Please contact us if you wish to remove the Zingiri logo in the footer
	$f='<center style="margin-top:0px;font-size:small">';
	$f.='Wordpress and osTicket integration  by <a href="http://www.zingiri.com">Zingiri</a>';
	$f.='</center>';

	return $f;
}

function zing_tickets_guest_login(&$http,&$post)
{
	global $current_user,$wpdb;
	if (isset($current_user)) {
		$query="select `ticketID` from `##zing_ost_ticket` where `email`='".$current_user->data->user_email."' limit 1";
		$query=str_replace("##",$wpdb->prefix,$query);
		$sql = mysql_query($query);
		if ($row = mysql_fetch_array($sql)) {
			$post['lemail']=$current_user->data->user_email;
			$post['lticket']=$row['ticketID'];
			$post['submit']='View Status';
			$http=zing_tickets_http('osticket','login.php');
		} else {
			$http=zing_tickets_http('osticket','logout.php');
		}
	} else {
		$post=array();
	}
}

function zing_tickets_pre_upgrade($success, $hook_extra) {
	if ($success && ($hook_extra['plugin'] == 'zingiri-tickets/zingiri_tickets.php')) {
		echo '<p>Backing up osTickets folder</p>';
		zing_tickets_recurse_copy(ZING_TICKETS_LOC.'osticket',BLOGUPLOADDIR.'osticket.tmp');
	}
}

function zing_tickets_post_upgrade($success, $hook_extra, $result) {
	if ($success && ($hook_extra['plugin'] == 'zingiri-tickets/zingiri_tickets.php')) {
		echo '<p>Restoring osTickets folder</p>';
		zing_tickets_recurse_copy(BLOGUPLOADDIR.'osticket.tmp',ZING_TICKETS_LOC.'osticket');
		zing_tickets_rrmdir(BLOGUPLOADDIR.'osticket.tmp');
	}

}

function zing_tickets_recurse_copy($src,$dst) {
	$dir = opendir($src);
	if (!file_exists($dst)) mkdir($dst);
	while(false !== ( $file = readdir($dir)) ) {
		if (!in_array($file,array('.','..','.svn'))) {
			if ( is_dir($src . '/' . $file) ) {
				zing_tickets_recurse_copy($src . '/' . $file,$dst . '/' . $file);
			}
			else {
				copy($src . '/' . $file,$dst . '/' . $file);
			}
		}
	}
	closedir($dir);
}

function zing_tickets_rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") zing_tickets_rrmdir($dir."/".$object);
				else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}

function zing_tickets_attachment($output) {
	header('Location:'.$output);
	die();
}

function zing_tickets_admin_header()
{
	global $zingiri_tickets_head;

	if (!isset($_REQUEST['page']) || ($_REQUEST['page'] != 'zingiri-tickets-admin')) return;

	//stylesheets and javascripts
	echo '<script type="text/javascript" language="javascript">';
	echo "var ajaxurl='".ZING_TICKETS_URL."osticket/upload/scp/';";
	echo "var ajaxurlloc='".get_admin_url()."admin.php?page=zingiri-tickets-admin&zscp=ajax&';";
	echo '</script>';
	echo $zingiri_tickets_head;
}

function zing_tickets_ostickets_content()
{
	global $zing_tickets_content;
	global $zing_tickets_menu,$zing_tickets_submenu,$zing_tickets_active_menu;
	global $zing_tickets_post,$zingiri_tickets_head;

	if (!class_exists('simple_html_dom')) require_once(dirname(__FILE__) . '/simple_html_dom.php');

	if (isset($_POST) && isset($zing_tickets_post)) {
		$_POST=array_merge($_POST,$zing_tickets_post);
	}

	$output=zing_tickets_output("content");
	//die($output);

	if ((isset($_REQUEST['zpage']) && $_REQUEST['zpage']=='attachment') || (isset($_REQUEST['zscp']) && $_REQUEST['zscp']=='attachment')) {
		zing_tickets_attachment($output);
		die();
	}
	if (!$output) {
		$zing_tickets_content='';
		return;
	}
	zing_integrator_cut($output,'<div id="footer">','</div>');
	$zingiri_tickets_head=zing_integrator_cut($output,'<div id="osthead">','</div>',true);

	//start menu
	$html = new simple_html_dom();
	$html->load($output);
	$i=0;
	foreach ($html->find('p[id=info] a') as $m) {
		if (!empty($m->innertext) && (strstr($m->href,'zscp=index') || strstr($m->href,'zscp=admin'))) {
			$zing_tickets_menu[]=array($m->innertext,$m->href);
			$i++;
		}
	}

	$zing_tickets_active_menu=0;

	foreach ($html->find('ul[id=main_nav] li a') as $m) {
		if (!empty($m->innertext)) {
			$zing_tickets_menu[]=array($m->innertext,$m->href);
			if ($m->class == 'active') $zing_tickets_active_menu=$i;
			$i++;
		}
	}
	foreach ($html->find('ul[id=sub_nav] li a') as $m) {
		$zing_tickets_submenu[]=array($m->innertext,$m->href);
	}
	$menu=zing_integrator_cut($output,'<div id="nav">','</div>');

	zing_integrator_cut($output,'<div id="header"','</div>');
	$zing_tickets_content=$output;
	$zing_tickets_content.=zing_tickets_footer();

}

function zing_tickets_header() {
	global $zingiri_tickets_head;

	zing_tickets_ostickets_content();

	echo $zingiri_tickets_head;
	echo '<link rel="stylesheet" type="text/css" href="' . ZING_TICKETS_URL . 'zing.css" media="screen" />';
}

