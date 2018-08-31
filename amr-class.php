<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


class AdminMenuRestriction {

	var $menus_to_delete;
	var $menu_editor_page;


	function __construct() {

		if ( is_admin() )
			$this->adminSideFunctions();

	}

	function adminSideFunctions() {

		// CALL THE EXISTING OPTIONS
		$this->menus_to_delete = get_option( 'amr_admin_menu', array() );


		// SETTINGS MENU
		add_action( 'admin_menu', array($this, 'settingsMenu') );


		// SETTINGS LINK IN THE PLUGINS PAGE
		add_filter('plugin_action_links', array($this, 'pluginsPageLink'), 2, 2 );


		// CALL THE JAVASCRIPT AND CSS FILES
		add_action( 'admin_enqueue_scripts', array($this, 'menu_editor_jscripts') );


		// APPLY SAVED DATA TO MENU
		add_action( 'admin_head', array($this, 'apply_data') );


		// SAVE THE OPTIONS
		add_action('admin_init', array($this,'admin_menu_saver') );


		// RESTRICT
		add_action( 'admin_menu', array($this, 'restrict'), 99999999999 );

	}

	function pluginsPageLink($actions, $file) {

		if(false !== strpos($file, 'admin-menu-restriction') && current_user_can('administrator'))
			$actions['settings'] = '<a href="options-general.php?page=admin-menu-restriction">Settings</a>';

		return $actions;

	}

	function settingsMenu() {

		$this->menu_editor_page = add_submenu_page(
			'options-general.php',  // admin page slug
			'Admin Menu Restriction', // page title
			'Admin Menu Editor', // menu title
			'administrator',               // capability required to see the page
			'admin-menu-restriction',                // admin page slug, e.g. options-general.php?page=amr_options
			array($this, 'settingsPage')            // callback function to display the options page
		);

	}

	function settingsPage() {


		if( !isset($_GET['role']) ) {

			// 1. Show the role selector first
			$this->showRoleSelector();

		} else {

			// 2. Show the menu items for selected role
			$this->showMenuEditor();

		}


	}

	function showRoleSelector() {

		echo '<div class="wrap">';
		echo "<h2>".esc_html( get_admin_page_title() )."</h2>";

		echo '<div id="poststuff">
	            	<div id="post-body">
		            	<div id="post-body-content">

		            		<span style="font-weight: bold;">Select a user role to edit access: </span>';
							$this->showRoleSelectBox();

		echo '			</div> <!-- end post-body-content -->
					</div> <!-- end post-body -->
				</div> <!-- end poststuff -->
			</div> <!-- end wrap -->';

	}

	function showRoleSelectBox() {
		global $wp_roles;


		echo '<select name="role" onChange="window.location.href=this.value">
				<option value="">Select a user group</option>';

				foreach ( $wp_roles->roles as $role => $role_details ) {

					// Avoid Editing Admins
					if ($role == "administrator") continue;


					// Is already selected?
					if (isset($_REQUEST['role']) && $_REQUEST['role'] == $role) { $selected = 'selected="selected"'; } else { $selected = ""; }


					// Check registered restrictions !!!
					if ( isset($this->menus_to_delete[$role]) && count($this->menus_to_delete[$role]) > 0 ) {

						if ( array_key_exists('sub_allow', $this->menus_to_delete[$role]) || array_key_exists('top_allow', $this->menus_to_delete[$role]) ) {

							$allow_restrictions = true;
							$allowingurl = 'true';
							$allowing_desc = 'Allowed Menu';

						} elseif ( array_key_exists('sub', $this->menus_to_delete[$role]) || array_key_exists('top', $this->menus_to_delete[$role]) ) {

							$disallow_restrictions = true;
							$allowingurl = 'false';
							$allowing_desc = 'Disallowed Menu';

						}

					} else {
						$allowingurl = 'true';
					}


					// Count registered restrictions !!!
					$count_restriction = 0;
					if (!isset($this->menus_to_delete[$role]) || !is_array($this->menus_to_delete[$role])) $this->menus_to_delete[$role] = array();
					foreach ( $this->menus_to_delete[$role] as $menu_type_counter => $menu_pages_counter ) {

						foreach ($menu_pages_counter as $restrictions_to_count) {

							$count_restriction = $count_restriction + count($restrictions_to_count);

						}

					}

					// Printable registered info
					$count_info = ($count_restriction != 0 ) ? '('.$count_restriction.' '.$allowing_desc.')' : '';

					// Print the roles
					echo '<option value="?page=admin-menu-restriction&role='.$role.'&allowing='.$allowingurl.'" '.$selected.'>'.$role_details['name'].' '.$count_info.'</option>';

				} // End of the loop


		echo '</select>';

	}

	function showMenuEditor() {
		global $menu, $submenu, $parent_file;

		// Current status is Allowing?
		$allowing_menu_items = ( isset($_GET['allowing']) && $_GET['allowing'] == "true" ? true : false );

		// Wrapper
		echo '<div class="wrap menu_editorbody'.($allowing_menu_items ? " allowing" : " disallowing").'">';

		// Allowing/Disallowing switcher
		if ( isset($allowing_menu_items) && $allowing_menu_items )
			echo '<a href="options-general.php?page=admin-menu-restriction&role='.$_GET['role'].'&allowing=false"
				style="position: absolute; right: 10px; top: 30px;">Switch to Choose Disallowed Menu Items</a>';
		else
			echo '<a href="options-general.php?page=admin-menu-restriction&role='.$_GET['role'].'&allowing=true"
				style="position: absolute; right: 10px; top: 30px;">Switch to Choose Allowed Menu Items</a>';


		// Updated Message
		if ( isset($_REQUEST['settings-updated']) )
			echo '<div class="updated fade"><p><strong>Menu options saved!</strong></p></div>';


		// The title
		echo "<h2>Admin Menu Access Settings For: ".$this->getRoleNameByRoleSlug( $_GET['role'] )."</h2>";

		echo '<div id="poststuff">
	            	<div id="post-body">
		            	<div id="post-body-content">

		            		<span style="font-weight: bold;">Select a user role to edit access: </span>';
							$this->showRoleSelectBox();

		echo '	            <form method="post" id="menu-editor-form">
								<p class="submit">
									<input type="submit" name="Submit" class="button-primary" value="Save Changes">
								</p>
								<p>
									Choose menu item(s) that you'.(isset($allowing_menu_items) && $allowing_menu_items ? "" : " don't").' want them to access.
								</p><br/>

								<a id="checkall" style="cursor: pointer;">Select All</a> / <a id="uncheckall" style="cursor: pointer;">Unselect All</a><br/><br/>

								<ul id="adminmenu" class="">';


									require_once( dirname(__file__).'/menu_editor_settings.php' );
									_wp_menu_output( $filtred_menu, $filtred_submenu, false );


		echo '					</ul>


								<div class="debug" style="display: none;">
									<h1>DATA</h1>
									<pre>'.print_r($this->menus_to_delete, true).'</pre>

									<h1>TOP MENU</h1>
									<pre>'.print_r($filtred_menu, true).'</pre>

									<h1>SUB MENU</h1>
									<pre>'.print_r($filtred_submenu, true).'</pre>
								</div>

								<input type="hidden" name="action" value="menu_editor_save" />
	                            <p class="submit">
									<input type="submit" name="Submit" class="button-primary" value="Save Changes">
								</p>
	                        </form>';
		echo '			</div> <!-- end post-body-content -->
					</div> <!-- end post-body -->
				</div> <!-- end poststuff -->
			</div> <!-- end wrap -->';

	}

	function getRoleNameByRoleSlug($role) {
		global $wp_roles;

		return $wp_roles->roles[$role]['name'];

	}


	// STYLE AND SCRIPT
	function menu_editor_jscripts($hook) {

		if( $hook != $this->menu_editor_page ) { return; }

			wp_enqueue_script( 'amr-menu-editor-jscripts', plugin_dir_url( __FILE__ ) .'script.js', array(), '1.0.1', true);

			wp_register_style( 'amr-menu-editor-styles', plugin_dir_url( __FILE__ ) .'style.css', false, '1.0.1' );
			wp_enqueue_style( 'amr-menu-editor-styles' );
	}


	function apply_data($hook) {

		$screen = get_current_screen()->base;
		if( $screen != $this->menu_editor_page ) { return; }

		$allowing = isset($_GET['allowing']) && $_GET['allowing'] == "true" ? true : false;

	?>
	<script>
		var amr_data_top =
		[<?php
		$data = $this->menus_to_delete[$_REQUEST['role']]['top'.($allowing ? "_allow" : "")];
		if ( isset($data) ) {

			$dataset = "";
			foreach ($data as $top_page => $value) {

				$dataset .= "'$top_page',";

			}
			echo trim($dataset, ",");

		}

		?>];

		var amr_data_sub =
		[<?php
		$data = $this->menus_to_delete[$_REQUEST['role']]['sub'.($allowing ? "_allow" : "")];
		if ( isset($data) ) {

			$dataset = "";
			foreach ($data as $top_page => $sub_pages) {
				foreach ($sub_pages as $page_key => $sub_page) {

					$dataset .= "'$top_page | $sub_page',";

				}
			}
			echo trim($dataset, ",");

		}
		?>];
	</script>
	<?php

	}


	// SAVE THE OPTIONS
	function admin_menu_saver() {
		global $_REQUEST;

		if ( isset($_REQUEST['allowing']) && $_REQUEST['allowing'] == "true" ) { $allowlink = "&allowing=true"; } else { $allowlink = "&allowing=false"; }


		if ( isset($_REQUEST['action']) && $_REQUEST['action'] == "menu_editor_save" ) {

			$current_options = get_option( 'amr_admin_menu', array() );
			$datatata = $this->regular_menu_option( $_REQUEST );


			if( $datatata[ $_REQUEST['role'] ] == "" ) {
				unset($current_options[ $_REQUEST['role'] ]);
			}


			$new_admin_menu_data = array_merge($current_options, $datatata);
			update_option( 'amr_admin_menu', $new_admin_menu_data );
			$this->forward_page("options-general.php?page=admin-menu-restriction&role=".urlencode($_REQUEST['role']).$allowlink."&settings-updated");


			//echo '<pre>'; print_r( $this->regular_menu_option( $_REQUEST ) ); echo '</pre><br/><br/>';
			//echo '<pre>'; print_r( $data['amr_customer']['top'] ); echo '</pre><br/><br/>';


		}

	}


	// SANITIZE THE DATA THAT COME FROM FORMS
	function regular_menu_option( $bring ) {

		$user_role = $bring['role'];

		if ( $bring['allowing'] == "true" ) { $allowing_menu_items = true; }


		unset(
			$bring['page'],
			$bring['role'],
			$bring['rolename'],
			$bring['allowing'],
			$bring['option_page'],
			$bring['action'],
			$bring['_wpnonce'],
			$bring['_wp_http_referer'],
			$bring['Submit'],
			$bring['shortcode_key'],
			$bring['settings-updated']
		);

		$regular_menu_filter = array();


		foreach ($bring as $key => $value) {

			$subpage = explode(" | ", $value);

			if ( substr($key, 0, 9) == "topitem__" ) {

				if ( $allowing_menu_items ) {

					$regular_menu_filter[ $user_role ]['top_allow'][ $subpage[0] ][$key] = $subpage[1];

				} else {

					$regular_menu_filter[ $user_role ]['top'][ $subpage[0] ][$key] = $subpage[1];

				}

			} elseif ( substr($key, 0, 9) == "subitem__" ) {

				if ( $allowing_menu_items ) {

					$regular_menu_filter[ $user_role ]['sub_allow'][ $subpage[0] ][$key] = $this->filterMenuUrl($subpage[1]);

				} else {

					$regular_menu_filter[ $user_role ]['sub'][ $subpage[0] ][$key] = $this->filterMenuUrl($subpage[1]);

				}

			} else {

				if ( $allowing_menu_items ) {

					$regular_menu_filter[ $user_role ]['sub_allow'][ $subpage[0] ][$key] = $subpage[1];
					$regular_menu_filter[ $user_role ]['top_allow'][ $subpage[0] ][$key] = $subpage[1];

				} else {

					$regular_menu_filter[ $user_role ]['sub'][ $subpage[0] ][$key] = $subpage[1];
					$regular_menu_filter[ $user_role ]['top'][ $subpage[0] ][$key] = $subpage[1];

				}


			}

		}

	return $regular_menu_filter;

	}


	// FILTER THE MENU ITEM URL
	function filterMenuUrl($menurl) {
		return str_replace('&', '&amp;', $menurl);
	}


	// FIND THE RIGHT SUB KEY
	function find_sub_key($menu_page, $menu_item) {
		global $submenu;

		////error_log("FIND SUB KEY: ".$menu_page." -> ".$menu_item);

		if ( !is_array($submenu[ $menu_page ]) ) $submenu[ $menu_page ] = array();

		foreach ( $submenu[ $menu_page ] as $sub_key_on_current => $sub_menu_details_on_current ) {

			$sub_menu_details_on_current[2] = isset($sub_menu_details_on_current[2]) ? $sub_menu_details_on_current[2] : null;

			if ( ($this->filterMenuUrl($sub_menu_details_on_current[2]) == $menu_item || $sub_menu_details_on_current[2] == $menu_item) ) {

				$sub_menu_key = $sub_key_on_current;

			}

			// CUSTOMIZE.PHP EXCEPTION
			if ( substr($sub_menu_details_on_current[2], 0, 13) == "customize.php" && substr($menu_item, 0, 13)  == "customize.php" ) {

				$sub_menu_key = $sub_key_on_current;

			}


		}

		////error_log("SUB KEY FOUND: ".$sub_menu_key);


		return $sub_menu_key;

	}


	// FIND THE RIGHT TOP KEY
	function find_top_key($menu_page) {
		global $menu;


		if ( !is_array($menu) ) $menu = array();

		foreach ( $menu as $top_key_on_current => $top_menu_item_details_on_current ) {

			if ( $this->filterMenuUrl($top_menu_item_details_on_current[2]) == $menu_page || $top_menu_item_details_on_current[2] == $menu_page ) {

				$top_menu_key = $top_key_on_current;
				break;

			}

		}

		return $top_menu_key;

	}


	// PAGE FORWARDER
	function forward_page($direction) {
		if (!headers_sent()) {
			wp_redirect($direction);
			exit;
		} else {
			print '<script type="text/javascript">';
			print 'window.location.href="' . $direction . '";';
			print '</script>';
			print '<noscript>';
			print '<meta http-equiv="refresh" content="0;url=' . $direction . '" />';
			print '</noscript>';
		}
	}


	// SUB PAGE BLOCK
	function block_subpage( $menu_page, $sub_menu_key, $menu_item, $dontdo = array() ) {
		global $_SERVER, $submenu;

		$itemunique = $menu_page.$sub_menu_key.$menu_item;


		if ( !in_array($itemunique, $dontdo) && !in_array($this->filterMenuUrl($itemunique), $dontdo) ) {

			$sub_menu_key = $this->find_sub_key($menu_page, $menu_item);


			// CHANGE THE PERMISSION
			$submenu[ $menu_page ][ $sub_menu_key ][1] = 'administrator';


			// REMOVE THE PAGE !!!
			if ( !remove_submenu_page( $menu_page, $menu_item ) ) unset( $submenu[ $menu_page ][ $sub_menu_key ] );


			// BLOCK THE PAGE ACCESS
			//$this->block_page_access($menu_item, "sub", $dontdo, $itemunique);

			//error_log( 'Deleted Sub: '.print_r($menu_page." - ".$sub_menu_key." - ".$menu_item, true) );

		}


	}


	// TOP PAGE BLOCK
	function block_toppage( $menu_page, $dontdo = array() ) {
		global $_SERVER, $menu;

		if ( !in_array($menu_page, $dontdo) && !in_array($this->filterMenuUrl($menu_page), $dontdo) ) {

			$top_menu_key = $this->find_top_key($menu_page);

			// CHANGE THE PERMISSION
			$menu[ $top_menu_key ][1] = 'administrator';

			// REMOVE THE PAGE
			if ( !remove_menu_page($menu_page) ) unset( $menu[ $top_menu_key ] );

			// BLOCK THE PAGE ACCESS
			//$this->block_page_access($menu_page, "top");

			//error_log( 'Deleted Top: '.print_r($menu_page, true) );

		}

	}


	// BLOCK THE PAGE ACCESS WITH ERROR MESSAGE
	function block_page_access($page, $type) {
		global $_SERVER;


		if ( $type == "sub" ) { // BLOCK THE PAGE ACCESS FOR SUBS

			$current_page_uri = $this->filterMenuUrl($this->filterMenuUrl($_SERVER['REQUEST_URI']));

			$find_data_uri = strpos($this->filterMenuUrl($page),'.php') !== false ? "/wp-admin/".$this->filterMenuUrl($page) : "/wp-admin/options-general.php?page=".$this->filterMenuUrl($page);

			$clean_current_page_uri = str_replace($find_data_uri, "", $current_page_uri);

			if ( $current_page_uri == $find_data_uri || $current_page_uri == $this->filterMenuUrl($find_data_uri) ) { // IF WANTED PAGE

				$is_disallowed_page = true;
				//echo "SOMETHING";

			} elseif ( substr($clean_current_page_uri, 0, 1) == "&" ) { // IF SOMEONE TRIES TO ADD QUERY

				$is_disallowed_page = true;
				//echo "SOMETHING2";

			} else {

				$is_disallowed_page = false;
				//echo "Current: $current_page_uri -> Find: $find_data_uri <br>";
				//print_r($dontdo);

			}


			$cachepage = $this->filterMenuUrl($this->filterMenuUrl('/wp-admin/options-general.php?page=w3tc_dashboard&w3tc_flush_all'));
			$is_cachepage = substr( $current_page_uri, 0, strlen($cachepage) ) === $cachepage ? true : false;

			if ( !$is_cachepage && $is_disallowed_page ) {

				wp_die("You do not have sufficient permissions to access this page. (error code 12)" );

				//wp_die("You do not have sufficient permissions to access this page. (error code 12 - $is_disallowed_page)<br><br>Current: ".$current_page_uri."<br>Find: ".$find_data_uri."<br>Clean: ".$clean_current_page_uri  );

			}

		} else { // BLOCK THE PAGE ACCESS FOR TOPS

			$current_page_uri = $_SERVER['REQUEST_URI'];

			$find_data_uri = strpos($page,'.php') !== false ? "/wp-admin/".$page : "/wp-admin/options-general.php?page=".$page;

			$clean_current_page_uri = str_replace($find_data_uri, "", $current_page_uri);

			if ( $current_page_uri == $find_data_uri || $current_page_uri == $this->filterMenuUrl($find_data_uri) ) { // IF WANTED PAGE

				$is_disallowed_page = true;

			} elseif ( substr($clean_current_page_uri, 0, 1) == "&" ) { // IF SOMEONE TRIES TO ADD QUERY

				$is_disallowed_page = true;

			} else {

				$is_disallowed_page = false;

			}


			$cachepage = '/wp-admin/options-general.php?page=w3tc_dashboard&w3tc_flush_all';
			$is_cachepage = substr( $current_page_uri, 0, strlen($cachepage) ) === $cachepage ? true : false;

			if ( !$is_cachepage && $is_disallowed_page ) {

				wp_die("You do not have sufficient permissions to access this page. (error code 11)" );

				//wp_die("You do not have sufficient permissions to access this page. (error code 11 - $is_disallowed_page)<br><br>".$current_page_uri."<br>".$find_data_uri  );

			}

		}

	}


	// APPLY THE SETTINGS
	function restrict() {
		global $menu, $submenu, $submenu_file, $plugin_page, $self, $self_type, $menu_file, $parent_file, $typenow;



		// RUN THE SCRIPT
		foreach ($this->menus_to_delete as $user => $menu_types) {


			if ( current_user_can($user) && !current_user_can('administrator') ) {


				$i = 0;
				$len = count($menu_types);

				$allowed_subs = array();
				$allowed_tops = array();

				foreach ($menu_types as $menu_type => $menu_pages) {


					if ( $menu_type == "sub_allow" ) { // SUB ALLOWING


						foreach ($menu_pages as $menu_page => $menu_items) {
							foreach ($menu_items as $menu_item) {


								// FIND THE SUBMENU KEY
								$sub_menu_key = $this->find_sub_key($menu_page, $menu_item);

								// Customize.php exception
								if ( substr($menu_item, 0, 13) == "customize.php" ) $menu_item = "customize.php";

								// ADD TO ALLOWED ITEMS
								$allowed_subs[] = $menu_page.$sub_menu_key.$menu_item;
								$allowed_tops[] = $menu_page;

								// Users.php exception
								if ( $menu_page == "users.php" && $menu_item == "profile.php" ) {
									$allowed_subs[] = "profile.php".$sub_menu_key.$menu_item;
									$allowed_tops[] = "profile.php";
								}

								//error_log( 'Allowed Sub: '.print_r($menu_page." - ".$sub_menu_key." - ".$menu_item, true) );
								//error_log( 'Allowed Top: '.print_r($menu_page, true) );

							}
						}

						////error_log( 'Allowed Subs: '.print_r($allowed_subs, true) );


					} elseif ( $menu_type == "top_allow" ) { // TOP ALLOWING


						foreach ($menu_pages as $menu_page => $menu_items) {
							foreach ($menu_items as $menu_item) {

								// ADD TO ALLOWED ITEMS
								$allowed_tops[] = $menu_page;
								$allowed_subs[] = $menu_page."0".$menu_page;

								//error_log( 'Allowed Top: '.print_r($menu_page, true) );

							}
						}


					} elseif ( $menu_type == "sub" ) { // SUB DELETION


						foreach ($menu_pages as $menu_page => $menu_items) {
							foreach ($menu_items as $menu_item) {

								// FIND THE SUBMENU KEY
								$sub_menu_key = $this->find_sub_key($menu_page, $menu_item);

								// DO SUB DELETION
								$this->block_subpage( $menu_page, $sub_menu_key, $menu_item );

								// Users.php exception
								if ( $menu_page == "users.php" && $menu_item == "profile.php" ) {
									$this->block_subpage( "profile.php", $sub_menu_key, $menu_item );
									$this->block_toppage( "profile.php" );
								}

							}
						}


					} elseif ( $menu_type == "top" ) { // TOP DELETION


						foreach ($menu_pages as $menu_page => $menu_items) {
							foreach ($menu_items as $menu_item) {

								//DO TOP DELETION
								$this->block_toppage( $menu_page );

							}
						}


					}

					if (
						(
							( array_key_exists('sub_allow', $menu_types) || array_key_exists('top_allow', $menu_types) ) && ($i == $len - 1)) ||
							(
								!array_key_exists('sub_allow', $menu_types) && !array_key_exists('top_allow', $menu_types) &&
								!array_key_exists('top', $menu_types) && !array_key_exists('sub', $menu_types)
							)
					) {


						// SUB ALLOW ====================================================================================
						foreach ($submenu as $menu_page => $sub_menus ) {
							foreach ($sub_menus as $menu_id => $menu_details) {

								$menu_details[2] = isset($menu_details[2]) ? $menu_details[2] : null;

								// Customize.php exception
								if ( substr($menu_details[2], 0, 13) == "customize.php" ) $menu_details[2] = "customize.php";


								$this->block_subpage( $menu_page, $menu_id, $menu_details[2], $allowed_subs );

							}
						}
						// SUB ALLOW ====================================================================================

						// TOP ALLOW ====================================================================================
						foreach ($menu as $top_menu_key_menu => $top_items_menu ) {

								$this->block_toppage( $top_items_menu[2], $allowed_tops );

						}
						// TOP ALLOW ====================================================================================


					}

				    $i++;
				}


			}


		}


	}


}


/*
function test_admin_head() {
	global $menu, $submenu;

	if ( ! current_user_can('administrator') ) {

		//echo '<pre>' . print_r( get_option( 'amr_admin_menu', array() );, true ) . '</pre>';
		//echo '<pre>' . print_r( $submenu, true ) . '</pre>';
		//echo '<h1>TOP MENU</h1><pre>' . print_r( $menu, true ) . '</pre>';

	}

}
add_action('admin_head', 'test_admin_head');
*/

?>