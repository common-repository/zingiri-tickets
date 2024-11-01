<?php
$zing_tickets_name = "Support Tickets Center";
$zing_tickets_shortname = "zing_tickets";
$install_type = array("Clean" );
$zing_login_type = array("osTickets","WP");

$zing_tickets_options[]=array(  "name" => "General settings",
            "type" => "heading",
			"desc" => "This section manages the Support Tickets Center settings.");
if (zing_tickets_remote()) {
	$zing_tickets_options[]=array(	"name" => "API key",
			"desc" => "This plugin uses remote web services to provide mailing list functionality. This API key has been automatically generated for you. Once you click on Install, the API key, in combination with your web site address <strong>".home_url()."</strong> will create an account on our servers allowing the plugin to access the remote web services.<br />The combination of API key and your web site address uniquely identifes you so please make sure to keep it in a safe place.",
			"id" => $zing_tickets_shortname."_key",
			"type" => "text");
	$zing_tickets_options[]=array(	"name" => "License key",
			"desc" => "If you wish to activate the Pro features of Support Tickets Center, enter your license key here. You can purchase a license key <a href=\"https://go.zingiri.com/cart.php?a=add&pid=131\" target=\"_blank\">here</a>.",
			"id" => $zing_tickets_shortname."_lic",
			"type" => "text");
}
if (!zing_tickets_remote()) {
	$zing_tickets_options[]=array(	"name" => "Type of integration",
			"desc" => "Select the way you want to login. If you want to use your Wordpress users, select 'WP'. <br />If you want to use the osTickets users, select 'osTickets'",
			"id" => $zing_tickets_shortname."_login",
			"std" => "WP",
			"type" => "select",
			"options" => $zing_login_type);
	$zing_tickets_options[]=array(	"name" => "Type of access",
			"desc" => "In case of Wordpress integration, select the way you want guests to access the ticketing system. <br />You can restrict this to Subscribers only or leave it open to everyone",
			"id" => $zing_tickets_shortname."_subscribers",
			"std" => "WP",
			"type" => "select",
			"options" => array("Subscribers","Everyone"));
} else {
	update_option($zing_tickets_shortname."_login","WP");
	update_option($zing_tickets_shortname."_subscribers","Everyone");
}
$zing_tickets_options[]=array(	"name" => "Language",
			"desc" => "Language of ticketing system (customer front end only)",
			"id" => $zing_tickets_shortname."_lang",
			"std" => "en",
			"type" => "selectwithkey",
			"options" => array("en"=>"English","br"=>"Brazilian Portuguese","bg" => "Bulgarian", "es"=>"Spanish","he"=>"Hebrew","nl"=>"Dutch","no"=>"Norwegian"));

if (zing_tickets_remote()) {
	$zing_tickets_options[]=array(  "name" => "Before you install",
            "type" => "heading",
			"desc" => '<div style="text-decoration:underline;display:inline;font-weight:bold">IMPORTANT:</div> Support Tickets Center uses web services stored on Zingiri\'s servers. In doing so, personal data is collected and stored on our servers. 
					This data includes amongst others your admin email address as this is used, together with the API key as a unique identifier for your account on Zingiri\'s servers.
					We have a very strict <a href="http://www.zingiri.com/privacy-policy/" target="_blank">privacy policy</a> as well as <a href="http://www.zingiri.com/terms/" target="_blank">terms & conditions</a> governing data stored on our servers.
					<div style="font-weight:bold;display:inline">By installing this plugin you accept these terms & conditions.</div>');
}

function zing_tickets_add_admin() {
	global $zing_tickets_name, $zing_tickets_shortname, $zing_tickets_options, $zing_tickets_menu, $zing_tickets_mode,$zing_tickets_submenu,$zing_tickets_active_menu,$zing_tickets_content;

	if (isset($_GET['page']) &&  ($_GET['page'] == 'zingiri-tickets-admin') ) {

		if (isset($_REQUEST['action']) && ('update' == $_REQUEST['action']) ) {
			foreach ($zing_tickets_options as $value) {
				if( isset( $value['id'] ) && isset( $_REQUEST[ $value['id'] ] ) ) {
					update_option( $value['id'], $_REQUEST[ $value['id'] ]  );
				}
			}
			zing_tickets_install();
			header("Location: options-general.php?page=zingiri-tickets-admin&installed=".$_REQUEST['install']);
			die;
		} elseif (isset($_REQUEST['action']) && ('install' == $_REQUEST['action']) ) {
			foreach ($zing_tickets_options as $value) {
				if( isset($value['id']) && isset( $_REQUEST[ $value['id'] ] ) ) {
					update_option( $value['id'], $_REQUEST[ $value['id'] ]  );
				}
			}
			zing_tickets_install();
			header("Location: options-general.php?page=zingiri-tickets-admin&installed=".$_REQUEST['install']);
			die;
		} elseif (isset($_REQUEST['action']) && ('uninstall' == $_REQUEST['action']) ) {
			zing_tickets_uninstall();
			foreach ($zing_tickets_options as $value) {
				if( isset($value['id']) ) delete_option( $value['id']);
			}
			header("Location: options-general.php?page=zingiri-tickets-admin&uninstalled=true");
			die;
		}

	}
	if (!isset($_GET['zscp']) || !$_GET['zscp']) {
		$zing_tickets_mode='menu';
	}
	if (get_option('zing_tickets_version')) zing_tickets_ostickets_content();
	if ($zing_tickets_content=='NEW') {
		header("Location: options-general.php?page=zingiri-tickets-admin&installed=Install");die;
	} elseif ($zing_tickets_content=='UPGRADE') {
		header("Location: options-general.php?page=zingiri-tickets-admin&installed=Upgrade");die;
	}
	if (current_user_can('activate_plugins')) {
		add_menu_page($zing_tickets_name, $zing_tickets_name, 'edit_pages', 'zingiri-tickets-admin','zing_tickets_admin');
		add_submenu_page('zingiri-tickets-admin', $zing_tickets_name.'- Integration', 'Integration', 'activate_plugins', 'zingiri-tickets-admin', 'zing_tickets_admin');
	} else {
		add_menu_page($zing_tickets_name, $zing_tickets_name, 'edit_pages', 'zingiri-tickets-admin','zing_ost_admin');
	}
	if (count($zing_tickets_menu) > 0) {
		if (zing_tickets_remote()) {
			foreach ($zing_tickets_menu as $i => $menu) {
				$link=preg_replace('/.*page=zingiri-tickets-admin&(.*?)/','$1',$menu);
				if (is_array($menu)) {
					$first=true;
					foreach ($menu as $j => $submenu) {
						$link=preg_replace('/.*page=zingiri-tickets-admin&(.*?)/','$1',$submenu);
						if ($first) {
							add_submenu_page('zingiri-tickets-admin', $zing_tickets_name.'-'.$i, $i, 'edit_pages', 'zingiri-tickets-admin&'.$link, 'zing_ost_admin');
						}
						if ($i != $j) add_submenu_page('zingiri-tickets-admin', $zing_tickets_name.'-'.$j, '- '.$j, 'edit_pages', 'zingiri-tickets-admin&'.$link, 'zing_ost_admin');
						$first=false;
					}
				} else {
					add_submenu_page('zingiri-tickets-admin', $zing_tickets_name.'-'.$i, $i, 'edit_pages', 'zingiri-tickets-admin&'.$link, 'zing_ost_admin');
				}
			}
		} else {
			foreach ($zing_tickets_menu as $i => $menu) {
				$link=preg_replace('/.*page=zingiri-tickets-admin&(.*?)/','$1',$menu[1]);
				if (!current_user_can('activate_plugins') && strstr($link,'index'))
				add_submenu_page('zingiri-tickets-admin', $zing_tickets_name.'-'.$menu[0], $menu[0], 'edit_pages', 'zingiri-tickets-admin', 'zing_ost_admin');
				else
				add_submenu_page('zingiri-tickets-admin', $zing_tickets_name.'-'.$menu[0], $menu[0], 'edit_pages', 'zingiri-tickets-admin&'.$link, 'zing_ost_admin');
				if ($i == $zing_tickets_active_menu) {
					foreach ($zing_tickets_submenu as $submenu) {
						$link=preg_replace('/.*page=zingiri-tickets-admin&(.*?)/','$1',$submenu[1]);
						add_submenu_page('zingiri-tickets-admin', $zing_tickets_name.'-'.$submenu[0], '- '.$submenu[0], 'edit_pages', 'zingiri-tickets-admin&'.$link, 'zing_ost_admin');
					}
				}
			}
		}
	}
}

function zing_ost_admin() {
	global $zing_tickets_content;
	global $zing_tickets_menu;

	echo '<div style="width:100%;float:left;position:relative">';
	echo $zing_tickets_content;
	echo '</div>';
}

function zing_tickets_admin() {
	global $zing_tickets_name, $zing_tickets_shortname, $zing_tickets_options, $wpdb;

	if (!get_option('zing_tickets_key')) update_option('zing_tickets_key',md5(time().sprintf(mt_rand(),'%10d')));

	if (isset($_GET['zscp']) && $_GET['zscp']) {
		zing_ost_admin();
		return;
	}

	$controlpanelOptions=$zing_tickets_options;
	if ( isset($_REQUEST['installed']) && $_REQUEST['installed']=='Install' ) echo '<div id="message" class="updated fade"><p><strong>'.$zing_tickets_name.' installed.</strong></p></div>';
	elseif ( isset($_REQUEST['installed']) && $_REQUEST['installed']=='Upgrade' ) echo '<div id="message" class="updated fade"><p><strong>'.$zing_tickets_name.' upgraded.</strong></p></div>';
	elseif ( isset($_REQUEST['installed']) && $_REQUEST['installed']=='Update' ) echo '<div id="message" class="updated fade"><p><strong>'.$zing_tickets_name.' updated.</strong></p></div>';
	elseif ( isset($_REQUEST['installed']) && $_REQUEST['installed']=='Sync' ) echo '<div id="message" class="updated fade"><p><strong>'.$zing_tickets_name.' synced.</strong></p></div>';
	elseif ( isset($_REQUEST['uninstalled']) && $_REQUEST['uninstalled'] ) echo '<div id="message" class="updated fade"><p><strong>'.$zing_tickets_name.' uninstalled.</strong></p></div>';

	?>
<div class="wrap">
	<div
		style="width: 75%; float: left; position: relative; min-height: 500px;">
		<h2>
			<b>Support Tickets Center</b>
		</h2>
		<div style="float: left; width: 50%">
		<?php
		$zing_tickets_version=get_option("zing_tickets_version");

		?>
			<form method="post">
			<?php require(dirname(__FILE__).'/includes/cpedit.inc.php')?>

			<?php if (!$zing_tickets_version) { ?>
				<p class="submit">
					<input class="button-primary" name="install" type="submit"
						value="Install" /> <input type="hidden" name="action"
						value="install" />
				</p>

				<?php } elseif ($zing_tickets_version != ZING_TICKETS_VERSION) { ?>
				<p class="submit">
					<input class="button-primary" name="install" type="submit"
						value="Upgrade" /> <input type="hidden" name="action"
						value="install" />
				</p>


				<?php } else { ?>

				<p class="submit">
					<input class="button-primary" name="install" type="submit"
						value="Update" /> <input type="hidden" name="action"
						value="update" />
				</p>

				<?php } ?>
			</form>

			<?php if ($zing_tickets_version) { ?>
			<form method="post">
				<p class="submit">
					<input name="uninstall" type="submit" value="Uninstall" /> <input
						type="hidden" name="action" value="uninstall" />
				</p>
			</form>
			<?php }?>
		</div>
		<div style="float: left; width: 50%">
		<?php if ($zing_tickets_version) {
			zing_tickets_active_users();
		}?>
		</div>
	</div>
	<?php 	require(dirname(__FILE__).'/includes/support-us.inc.php');
	zing_support_us('tickets','zingiri-tickets','zingiri-tickets-admin',ZING_TICKETS_VERSION,false,ZING_TICKETS_URL);
	?>
</div>
	<?php
}
add_action('admin_menu', 'zing_tickets_add_admin');
?>