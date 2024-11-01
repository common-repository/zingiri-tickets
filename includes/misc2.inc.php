<?php
/**
 * Remote version
 */

if ($zing_tickets_version) {
	add_action("init","zing_tickets_init_remote");
	add_action('wp_ajax_tickets', 'zing_tickets_ajax_callback');
	add_action('wp_ajax_nopriv_tickets', 'zing_tickets_ajax_callback');
	add_action('wp_ajax_ticketsadmin', 'zing_ticketsadmin_ajax_callback');
}

function zing_tickets_http($module,$to_include="",$page="",$key="") {
	global $wpdb,$current_user;

	if (!$to_include || $to_include==".php") {
		if (is_admin()) $to_include="index.php";
		else $to_include="open.php";
	}

	$t=explode('/',$to_include);
	if (count($t)==2) {
		$http=zing_ost_url().$t[0].'/api.php';
	} else {
		$http=zing_ost_url().'api.php';
	}
	$vars='pg='.urlencode($to_include);
	$and='&';

	$get=$_GET;

	if (count($get) > 0) {
		foreach ($get as $n => $v) {
			if ($n!="zpage" && $n!="page_id" && $n!="zscp" && $n!="page") {
				$vars.= $and.$n.'='.zing_urlencode($v);
				$and="&";
			}
		}
	}

	$wp=array();
	if (is_user_logged_in()) {
		$wp['login']=$current_user->data->user_login;
		$wp['email']=$current_user->data->user_email;
		$wp['first_name']=isset($current_user->data->first_name) ? $current_user->data->first_name: $current_user->data->display_name;
		$wp['last_name']=isset($current_user->data->last_name) ? $current_user->data->last_name : $current_user->data->display_name;
		$wp['roles']=$current_user->roles;
	}
	$wp['default_page']=zing_tickets_default_page();
	$wp['lic']=get_option('zing_tickets_lic');
	$wp['gmt_offset']=get_option('gmt_offset');
	$wp['siteurl']=home_url();
	$wp['sitename']=get_bloginfo('name') ? get_bloginfo('name') : 'unknown';
	$wp['pluginurl']=ZING_TICKETS_URL;
	if (is_admin()) {
		$wp['mode']='b';
		$wp['pageurl']=get_admin_url().'admin.php?page=bookings&';
	} else {
		$wp['mode']='f';
		$wp['pageurl']=zing_tickets_home();
	}

	$wp['time_format']=get_option('time_format');
	$wp['admin_email']=get_option('admin_email');
	$wp['key']=get_option('zing_tickets_key');
	$wp['lang']=get_option('zing_tickets_lang'); //get_bloginfo('language');
	$wp['client_version']=ZING_TICKETS_VERSION;

	$vars.='&wp='.urlencode(base64_encode(json_encode($wp)));

	if ($vars) $http.= '?'.$vars;
	//echo $http;
	return $http;
}

function zing_tickets_home() {
	global $post,$page_id;

	$pageID = $page_id;

	if (get_option('permalink_structure')){
		$homePage = get_option('home');
		$wordpressPageName = get_permalink($pageID);
		$wordpressPageName = str_replace($homePage,"",$wordpressPageName);
		$home=$homePage.$wordpressPageName;
		if (substr($home,-1) != '/') $home.='/';
		$home.='?';
	}else{
		$home=get_option('home').'/?page_id='.$pageID.'&';
	}

	return $home;
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

	//default options
	if (is_array($zing_tickets_options) && count($zing_tickets_options) > 0) {
		foreach ($zing_tickets_options as $value) {
			if ( !empty($value['id']) && !get_option($value['id']) ) update_option( $value['id'], $value['std'] );
		}
	}

	//create standard pages
	if (!$zing_tickets_version) {
		$pages=array();
		$pages[]=array("Tickets","open","*",0);

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

		//set comment status to closed
		$ids=get_option("zing_tickets_pages");
		$ida=explode(",",$ids);
		foreach ($ida as $id) {
			$my_post = array();
			$my_post['ID']=$id;
			$my_post['comment_status'] = 'closed';
			wp_update_post($my_post);
		}
	}

	if (!$zing_tickets_version) {
		$http=zing_tickets_http("osticket","setup/api.php");
		$news = new zHttpRequest($http,'zingiri-tickets');
		//$news->post=array('step' => 2);
		if ($news->live()) {
			$output=$news->DownloadToString();
			zing_tickets_log($output);
		}
	}

	if (!$zing_tickets_version) update_option('zing_tickets_secret_salt',md5(__FILE__.md5(get_option('admin_email'))));

	//update version
	update_option("zing_tickets_version",ZING_TICKETS_VERSION);

}

/**
 * Uninstallation: removal of database tables
 * @return void
 */
function zing_tickets_uninstall() {
	global $wpdb;

	$http=zing_tickets_http("osticket","deactivate.php");
	$news = new zHttpRequest($http,'zingiri-tickets');
	if ($news->live()) {
		$output=$news->DownloadToString();
		zing_tickets_log($output);
	}

	$ids=get_option("zing_tickets_pages");
	$ida=explode(",",$ids);
	foreach ($ida as $id) {
		wp_delete_post($id);
	}
	delete_option("zing_tickets_version");
	delete_option("zing_tickets_key");
	delete_option("zing_tickets_pages");
	delete_option('zing_tickets_secret_salt');
	delete_option('zing_tickets_remote');
	delete_option("zing_tickets_login");
	delete_option("zing_tickets_subscribers");
}

function zing_tickets_active_users() {
	global $wpdb;

	$syncTime=date('Y-m-d H:i:s');
	echo '<h3>The following Wordpress users are active Support Tickets Center users</h3>';
	$blogusers = get_users();
	foreach ($blogusers as $usr) {
		$usr->first_name=get_user_meta($usr->ID,'first_name',true);
		$usr->last_name=get_user_meta($usr->ID,'last_name',true);
		$row=(array)$usr->data;
		$user=array();
		if (!isset($row['first_name'])) $row['first_name']='';
		if (!isset($row['last_name'])) $row['last_name']=$row['display_name'];
		$user=array_combine(
		array('group_id', 'dept_id', 'username', 'firstname', 'lastname', 'email', 'phone', 'phone_ext', 'mobile', 'signature', 'isactive', 'isvisible', 'daylight_saving', 'append_signature', 'change_passwd', 'timezone_offset'),
		array(1, 1, $row['user_login'],$row['first_name'] ? $row['first_name'] : $row['user_login'],$row['last_name'] ? $row['last_name'] : $row['user_login'],$row['user_email'], '', '', '', '', 1, 1, 0, 0, 0, 0.0));
		if (user_can($row['ID'],'activate_plugins')) { //administrator role
			echo $row['user_login'].' '.$row['first_name'].' '.$row['last_name'].': Admin<br />';
			$user['isadmin']=1;
			$user['isactive']=1;
		} elseif (user_can($row['ID'],'edit_pages')) { //editor role
			echo $row['user_login'].' '.$row['first_name'].' '.$row['last_name'].': Staff<br />';
			$user['isadmin']=0;
			$user['isactive']=1;
		} else {
			$user['isactive']=0;
		}
		if ( isset($_REQUEST['installed']) && $_REQUEST['installed']=='Sync' ) {
			$http=zing_tickets_http("osticket","scp/sync.php");
			$news = new zHttpRequest($http,'zingiri-tickets');
			$news->post=array('user'=>$user,'mode'=>1,'time'=>$syncTime);
			if ($news->live()) {
				$output=$news->DownloadToString();
				echo $output;
				zing_tickets_log($output);
			}
		}
	}

	echo '<p>Users are not synced automatically so please sync the users whenever you create a new user or update a user (change of password, etc).</p>';
	echo '<form method="post">';
	echo '<p class="submit"><input class="button-primary" name="install" type="submit" value="Sync" /> <input type="hidden" name="action" value="install" /></p>';
	echo '</form>';


}

function zing_ost_url() { //URL end point for web services stored on Zingiri servers
	if (defined('ZING_OST_URL')) return ZING_OST_URL;
	return 'http://eu1.tickets.clientcentral.info/';
}

function zing_tickets_login() {
}

function zing_tickets_footer() {
}

function zing_tickets_attachment($output) {
	header('Location:'.$output);
	die();
}

function zing_tickets_admin_header() {
	if (!isset($_REQUEST['page']) || ($_REQUEST['page'] != 'zingiri-tickets-admin')) return;

	echo '<script type="text/javascript" language="javascript">';
	echo "var ticketsUrl='".zing_ost_url()."';";
	echo "var ticketsAjaxUrl=ajaxurl;";
	echo '</script>';
}

function zing_tickets_init_remote() {
	if (!is_admin()) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('tickets_multifile',zing_ost_url().'js/jquery.multifile.js',array('jquery'));
		wp_enqueue_script('tickets_ost',zing_ost_url().'js/osticket.js',array('jquery'));
		wp_enqueue_style('tickets_ost',zing_ost_url().'css/osticket.css');
		wp_enqueue_style('tickets_theme',zing_ost_url().'assets/default/css/theme.css');
		//wp_enqueue_style('tickets_print',zing_ost_url().'assets/default/css/print.css');
		wp_enqueue_style('tickets_ost',zing_ost_url().'css/osticket.css');
		wp_enqueue_style('zing',ZING_TICKETS_URL.'zing.css');
	} elseif (is_admin() && isset($_REQUEST['zscp'])) {
		wp_enqueue_script('jquery');
		wp_enqueue_script(array('jquery-ui-core','jquery-ui-datepicker'));
		wp_enqueue_script('tickets_multifile',zing_ost_url().'js/jquery.multifile.js',array('jquery'));
		//wp_enqueue_script('tickets_tips',zing_ost_url().'scp/js/tips.js',array('jquery'));
		wp_enqueue_script('tickets_nicEdit',zing_ost_url().'scp/js/nicEdit.js',array('jquery'));
		wp_enqueue_script('tickets_bootstrap-typeahead',zing_ost_url().'scp/js/bootstrap-typeahead.js',array('jquery'));
		wp_enqueue_script('tickets_scp',zing_ost_url().'scp/js/scp.js',array('jquery'));
		wp_enqueue_style('tickets_scp',zing_ost_url().'scp/css/scp.css');
		wp_enqueue_style('tickets_typeahead',zing_ost_url().'scp/css/typeahead.css');
		wp_enqueue_style('tickets_font',zing_ost_url().'css/font-awesome.min.css');
		wp_enqueue_style('tickets_dropdown',zing_ost_url().'scp/css/dropdown.css');
		wp_enqueue_style('tickets_lightness',zing_ost_url().'css/ui-lightness/jquery-ui-1.8.18.custom.css');
		wp_enqueue_script('tickets_dropdown',zing_ost_url().'scp/js/jquery.dropdown.js',array('jquery'));
	}
}

function zing_tickets_ostickets_content()
{
	global $zing_tickets_content;
	global $zing_tickets_menu,$zing_tickets_submenu,$zing_tickets_active_menu;
	global $zing_tickets_post,$zingiri_tickets_head;

	if (isset($_POST) && isset($zing_tickets_post)) {
		$_POST=array_merge($_POST,$zing_tickets_post);
	}

	$output=zing_tickets_output("content");
	if (in_array($output,array('NEW','UPGRADE'))) {
		$zing_tickets_content=$output;
		return;
	}

	if ((isset($_REQUEST['zpage']) && $_REQUEST['zpage']=='attachment') || (isset($_REQUEST['zscp']) && $_REQUEST['zscp']=='attachment')) {
		zing_tickets_attachment($output);
		die();
	}
	if (!$output) {
		$zing_tickets_content='';
		return;
	}

	require(dirname(__FILE__).'/phpQuery/phpQuery.php');

	zing_integrator_cut($output,'<div id="footer">','</div>');
	$zingiri_tickets_head=zing_integrator_cut($output,'<div id="osthead">','</div>',true);

	//start menu
	phpQuery::newDocumentHTML($output);
	foreach (pq('div[id=header] p[id=info] a:first') as $a) {
		$zing_tickets_menu[pq($a)->html()]=pq($a)->attr('href');
	}
	$K=false;
	foreach (pq('ul[id=nav]')->children('li') as $li) {
		$k=pq($li)->children('a')->html();
		if (pq($li)->hasClass('active')) $K=$k;
		if (count(pq($li)->find('ul li')) > 0) {
			foreach (pq($li)->find('ul li') as $l2) {
				$zing_tickets_menu[$k][pq($l2)->children('a')->html()]=pq($l2)->children('a')->attr('href');
			}
		} else {
			$zing_tickets_menu[$k]=pq($li)->children('a')->attr('href');
		}
	}
	if ($K) {
		if (count(pq('ul[id=sub_nav] li a')) > 0) {
			$zing_tickets_menu[$K]=array();
			foreach (pq('ul[id=sub_nav] li a') as $a) {
				$zing_tickets_menu[$K][pq($a)->html()]=pq($a)->attr('href');
			}
		}
	}

	pq('div[id=header]')->remove();
	pq('ul[id=sub_nav]')->remove();
	pq('ul[id=nav]')->remove();
	$zing_tickets_content=pq('div[class=ostbody]');
	$zing_tickets_content.=zing_tickets_footer();
}

function zing_tickets_header() {
	zing_tickets_ostickets_content();
	echo '<script type="text/javascript">';
	echo "var ticketsAjaxUrl = '".admin_url('admin-ajax.php')."';";
	echo "var ticketsUrl='".zing_ost_url()."';";
	echo '</script>';
}

function zing_tickets_ajax_callback() {
	$url=$_REQUEST['url'];
	zing_tickets_login();
	$http=zing_tickets_http("osticket",'ajax.php');
	$news = new zHttpRequest($http,'zingiri-tickets');
	if ($news->live()) {
		$news->follow=false;
		$output=$news->DownloadToString();
		echo $output;
	}
	die();
}

function zing_ticketsadmin_ajax_callback() {
	$url=$_REQUEST['url'];
	zing_tickets_login();
	$http=zing_tickets_http("osticket",'scp/ajax.php');
	$news = new zHttpRequest($http,'zingiri-tickets');
	if ($news->live()) {
		$news->follow=false;
		$output=$news->DownloadToString();
		echo $output;
	}
	die();
}