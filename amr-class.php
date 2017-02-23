<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


class AdminMenuRestriction {

	var $menus_for_delete;
	var $amr_menu_editor_page;


	function __construct() {

		if ( is_admin() )
			$this->adminSideFunctions();

	}

	function adminSideFunctions() {

		// CALL THE EXISTING OPTIONS
		$this->menus_for_delete = get_option( 'amr_admin_menu', array() );


		// SETTINGS MENU
		add_action( 'admin_menu', array($this, 'settingsMenu') );


		// SETTINGS LINK IN THE PLUGINS PAGE
		add_filter('plugin_action_links', array($this, 'pluginsPageLink'), 2, 2 );


		// CALL THE JAVASCRIPT AND CSS FILES
		add_action( 'admin_enqueue_scripts', array($this, 'amr_menu_editor_jscripts') );


		// SAVE THE OPTIONS
		add_action('admin_init', array($this,'amr_admin_menu_saver') );


		// RESTRICT
		add_action( 'admin_menu', array($this, 'restrict'), 99999999999 );

	}

	function pluginsPageLink($actions, $file) {

		if(false !== strpos($file, 'admin-menu-restriction') && current_user_can('administrator'))
			$actions['settings'] = '<a href="options-general.php?page=admin-menu-restriction">Settings</a>';

		return $actions;

	}

	function settingsMenu() {

		$this->amr_menu_editor_page = add_submenu_page(
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
					if ( isset($this->menus_for_delete[$role]) && count($this->menus_for_delete[$role]) > 0 ) {

						if ( array_key_exists('sub_allow', $this->menus_for_delete[$role]) || array_key_exists('top_allow', $this->menus_for_delete[$role]) ) {

							$allow_restrictions = true;
							$allowingurl = 'true';
							$allowing_desc = 'Allowed Menu';

						} elseif ( array_key_exists('sub', $this->menus_for_delete[$role]) || array_key_exists('top', $this->menus_for_delete[$role]) ) {

							$disallow_restrictions = true;
							$allowingurl = 'false';
							$allowing_desc = 'Disallowed Menu';

						}

					} else {
						$allowingurl = 'true';
					}


					// Count registered restrictions !!!
					$count_restriction = 0;
					if (!isset($this->menus_for_delete[$role]) || !is_array($this->menus_for_delete[$role])) $this->menus_for_delete[$role] = array();
					foreach ( $this->menus_for_delete[$role] as $menu_type_counter => $menu_pages_counter ) {

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
		if ( isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] )
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

								<a id="checkall" style="cursor: pointer;">Check All</a> / <a id="uncheckall" style="cursor: pointer;">Uncheck All</a><br/><br/>';


								require_once( dirname(__file__).'/menu_editor_settings.php' );
								$this->_wp_menu_edit_output( $menu, $submenu );


		echo '					<input type="hidden" name="action" value="amr_menu_editor_save" />
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

	function _wp_menu_edit_output( $menu, $submenu, $submenu_as_parent = true ) {
		global $self, $parent_file, $submenu_file, $plugin_page, $typenow, $_REQUEST;


		$options = get_option( 'amr_admin_menu', array() );

		if ( $_REQUEST['allowing'] == "true" ) { $allowing_menu_items = true; }

		if (isset($allowing_menu_items) && $allowing_menu_items) {
			$allow_ext = "_allow";
		} else {
			$allow_ext = "";
		}


		echo '<ul class="topmenu-items" id="adminmenu">';
			$first = true;
			$amr_item_no = 0;
			// 0 = menu_title, 1 = capability, 2 = menu_slug, 3 = page_title, 4 = classes, 5 = hookname, 6 = icon_url
			foreach ( $menu as $key => $item ) {
				$admin_is_parent = false;
				$class = array();
				$aria_attributes = '';
				$aria_hidden = '';
				$is_separator = false;

				if ( $first ) {
					$class[] = 'wp-first-item';
					$first = false;
				}

				$submenu_items = array();
				if ( ! empty( $submenu[$item[2]] ) ) {
					$class[] = 'wp-has-submenu';
					$submenu_items = $submenu[$item[2]];
				}

				if ( ( $parent_file && $item[2] == $parent_file ) || ( empty($typenow) && $self == $item[2] ) ) {
					$class[] = ! empty( $submenu_items ) ? 'wp-not-current-submenu' : 'current';
				} else {
					$class[] = 'wp-not-current-submenu';
					if ( ! empty( $submenu_items ) )
						$aria_attributes .= 'aria-haspopup="true"';
				}

				if ( ! empty( $item[4] ) )
					$class[] = esc_attr( $item[4] );

				$class = $class ? ' class="topmenu-item ' . join( ' ', $class ) . '"' : '';
				$id = ! empty( $item[5] ) ? ' id="top-'.$amr_item_no.'"' : ' id="top-'.$amr_item_no.'"';
				$img = $img_style = '';
				$img_class = ' dashicons-before';

				if ( false !== strpos( $class, 'wp-menu-separator' ) ) {
					$is_separator = true;
				}

				/*
				 * If the string 'none' (previously 'div') is passed instead of an URL, don't output
				 * the default menu image so an icon can be added to div.wp-menu-image as background
				 * with CSS. Dashicons and base64-encoded data:image/svg_xml URIs are also handled
				 * as special cases.
				 */
				if ( ! empty( $item[6] ) ) {
					$img = '<img src="' . $item[6] . '" alt="" />';

					if ( 'none' === $item[6] || 'div' === $item[6] ) {
						$img = '<br />';
					} elseif ( 0 === strpos( $item[6], 'data:image/svg+xml;base64,' ) ) {
						$img = '<br />';
						$img_style = ' style="background-image:url(\'' . esc_attr( $item[6] ) . '\')"';
						$img_class = ' svg';
					} elseif ( 0 === strpos( $item[6], 'dashicons-' ) ) {
						$img = '<br />';
						$img_class = ' dashicons-before ' . sanitize_html_class( $item[6] );
					}
				}
				$arrow = '<div class="wp-menu-arrow"><div></div></div>';

				$title = wptexturize( $item[0] );

				// hide separators from screen readers
				if ( $is_separator ) {
					$aria_hidden = ' aria-hidden="true"';
				}


		/*		$submenu_items = array_values( $submenu_items );  // Re-index.
				if (!has_allowed_submenu($item[1], $submenu_items, $_REQUEST['role'] )) continue;*/


		// Remove any duplicated separators
		if ( $is_separator ) {
			$separator_found = false;
			//foreach ( $menu as $id => $data ) {
				if ( 0 == strcmp('wp-menu-separator', $item[4] ) ) {
					if (false == $separator_found) {
						$separator_found = 'true';
					} else {
						//unset($menu[$id]);
						$separator_found = 'false';
					}
				} else {
					$separator_found = 'false2';
					continue;
				}
			//}
			//unset($id, $data);
		}


				echo "\n\t<li$class$id$aria_hidden><input type='checkbox' class='topitem' id='".$amr_item_no."' name='topitem__".$amr_item_no."' value='".$item[2]." | ".$key."' ".$this->amr_checkifexist( "top".$allow_ext, $item[2], $key, $options ).">";
		// NEW USAGE: $this->amr_checkifexist( $type = "top", $pg = "users.php", $menuid = 70, $datam );
				$amr_item_no_inherit = $amr_item_no;
				$amr_item_no++;

				if ( $is_separator ) {
					echo '<label for="'.$amr_item_no_inherit.'">Seperator</label>'; // echo "<pre>"; print_r($item); echo "</pre>";
				} elseif ( $submenu_as_parent && ! empty( $submenu_items ) ) {
					$submenu_items = array_values( $submenu_items );  // Re-index.
					$menu_hook = get_plugin_page_hook( $submenu_items[0][2], $item[2] );
					$menu_file = $submenu_items[0][2];
					if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
						$menu_file = substr( $menu_file, 0, $pos );

					if ( ! empty( $menu_hook ) || ( ( 'index.php' != $submenu_items[0][2] ) && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! file_exists( ABSPATH . "/wp-admin/$menu_file" ) ) ) {
						$admin_is_parent = true;
						echo "<label for='$amr_item_no_inherit'$class $aria_attributes>$arrow<div class='wp-menu-image$img_class'$img_style>$img</div><div class='wp-menu-name'>$title</div></label>";/*echo "<pre>"; has_allowed_submenu($submenu_items, $_REQUEST['role'] ); /*print_r($submenu_items); echo "</pre>";*/
					} else {
						echo "\n\t<label for='$amr_item_no_inherit'$class $aria_attributes>$arrow<div class='wp-menu-image$img_class'$img_style>$img</div><div class='wp-menu-name'>$title</div></label>";
					}
				} elseif ( ! empty( $item[2] ) && $this->role_has_capability( $_REQUEST['role'], $item[1] ) ) {
					$menu_hook = get_plugin_page_hook( $item[2], 'admin.php' );
					$menu_file = $item[2];
					if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
						$menu_file = substr( $menu_file, 0, $pos );
					if ( ! empty( $menu_hook ) || ( ( 'index.php' != $item[2] ) && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! file_exists( ABSPATH . "/wp-admin/$menu_file" ) ) ) {
						$admin_is_parent = true;
						echo "\n\t<label for='$amr_item_no_inherit'$class $aria_attributes>$arrow<div class='wp-menu-image$img_class'$img_style>$img</div><div class='wp-menu-name'>{$item[0]}</div></label>";
					} else {
						echo "\n\t<label for='$amr_item_no_inherit'$class $aria_attributes>$arrow<div class='wp-menu-image$img_class'$img_style>$img</div><div class='wp-menu-name'>{$item[0]}</div></label>";
					}
				}
				echo "</li>";
				if ( ! empty( $submenu_items ) ) {
					echo "\n\t<ul id='sub-".$amr_item_no_inherit."' class='submenu-items wp-submenu wp-submenu-wrap' style='padding: 0; z-index: 0; position: relative; top: auto; left: auto; right: auto; bottom: auto;'>";
					echo "<li class='wp-submenu-head'>{$item[0]}</li>";

					$first = true;

					// 0 = menu_title, 1 = capability, 2 = menu_slug, 3 = page_title, 4 = classes
					foreach ( $submenu[$item[2]] as $sub_key => $sub_item ) {
						if ( ! $this->role_has_capability( $_REQUEST['role'], $sub_item[1] ) )
							continue;

						$class = array();
						if ( $first ) {
							$class[] = 'wp-first-item';
							$first = false;
						}

						$menu_file = $item[2];

						if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
							$menu_file = substr( $menu_file, 0, $pos );

						// Handle current for post_type=post|page|foo pages, which won't match $self.
						$self_type = ! empty( $typenow ) ? $self . '?post_type=' . $typenow : 'nothing';

						if ( isset( $submenu_file ) ) {
							if ( $submenu_file == $sub_item[2] )
								$class[] = 'current';
						// If plugin_page is set the parent must either match the current page or not physically exist.
						// This allows plugin pages with the same hook to exist under different parents.
						} elseif (
							( ! isset( $plugin_page ) && $self == $sub_item[2] ) ||
							( isset( $plugin_page ) && $plugin_page == $sub_item[2] && ( $item[2] == $self_type || $item[2] == $self || file_exists($menu_file) === false ) )
						) {
							$class[] = 'current';
						}

						if ( ! empty( $sub_item[4] ) ) {
							$class[] = esc_attr( $sub_item[4] );
						}

						$class = $class ? ' class="submenu-item ' . join( ' ', $class ) . '"' : ' class="submenu-item"';
						$id = " id='sub-".$amr_item_no."'";

						$menu_hook = get_plugin_page_hook($sub_item[2], $item[2]);
						$sub_file = $sub_item[2];
						if ( false !== ( $pos = strpos( $sub_file, '?' ) ) )
							$sub_file = substr($sub_file, 0, $pos);

						$title = wptexturize($sub_item[0]);
						$sub_item_checkbox = "<input type='checkbox' class='subitem' id='".$amr_item_no."' name='subitem__".$amr_item_no."' value='".$item[2]." | ".$sub_item[2]."' ".$this->amr_checkifexist( "sub".$allow_ext, $item[2], $sub_item[2], $options ).">";

						if ( ! empty( $menu_hook ) || ( ( 'index.php' != $sub_item[2] ) && file_exists( WP_PLUGIN_DIR . "/$sub_file" ) && ! file_exists( ABSPATH . "/wp-admin/$sub_file" ) ) ) {
							// If admin.php is the current page or if the parent exists as a file in the plugins or admin dir
							if ( ( ! $admin_is_parent && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! is_dir( WP_PLUGIN_DIR . "/{$item[2]}" ) ) || file_exists( $menu_file ) )
								$sub_item_url = add_query_arg( array( 'page' => $sub_item[2] ), $item[2] );
							else
								$sub_item_url = add_query_arg( array( 'page' => $sub_item[2] ), 'admin.php' );

							$sub_item_url = esc_url( $sub_item_url );

							echo "<li$class$id>$sub_item_checkbox<label for='$amr_item_no'$class>$title</label></li>";
						} else {
							echo "<li$class$id>$sub_item_checkbox<label for='$amr_item_no'$class>$title</label></li>";
						}
						$amr_item_no++;
					}
					//print_r($submenu[$item[2]]);
					echo "</ul>";
				}
				//echo "</li>";
			}

			//echo '<li id="collapse-menu" class="hide-if-no-js"><div id="collapse-button"><div></div></div>';
			//echo '<span>' . esc_html__( 'Collapse menu' ) . '</span>';
			//echo '</li>';
		echo '</li></ul>';

		//echo "<pre>"; print_r($options); echo "</pre>";
	}

	function role_has_capability($target_role, $has_cap) {
		global $wp_roles;

		$the_role = $wp_roles->roles[$target_role]['capabilities'];


		if ( isset($the_role[$has_cap]) && $the_role[$has_cap] == 1 ) {
			return true;
		} else {
			return false;
		}

	}


	// CHECKS THE BOXES IF APPLIED BEFORE
	function amr_checkifexist($type, $page, $menuid, $datam ) {
		global $_GET;

			$pulldatatop = isset($datam[ $_GET['role'] ][$type]) ? $datam[ $_GET['role'] ][$type] : "";

		if (!is_array($pulldatatop)) $pulldatatop = array();

	$result = "";
		foreach ($pulldatatop as $pagename => $idarray ) {

			if ( isset($type) && $type == "top_allow" ) {

				if ( $pagename == $page || $pagename == $this->filterMenuUrl($page) ) {

					$result .= "checked byallow";
					break;

				}

			} elseif ( isset($type) && $type == "sub_allow" ) {

				foreach ($idarray as $itemid => $menuid_data) {

					if ( $menuid_data == $menuid || $menuid_data == $this->filterMenuUrl($menuid) ) {
						$result .= "checked byallow";
						break;
					}elseif ( substr($menuid_data, 0, 13) == "customize.php" && substr($menuid_data, 0, 13) == substr($menuid, 0, 13) ) {
						$result .= "checked byallow";
						break;
					}

				}

			} elseif ( isset($type) && $type == "top" ) {

				if ( $pagename == $page || $pagename == $this->filterMenuUrl($page) ) {

					$result .= "checked";
					break;

				}

			} elseif ( isset($type) && $type == "sub" ) {

				foreach ($idarray as $itemid => $menuid_data) {

					if ( $menuid_data == $menuid || $menuid_data == $this->filterMenuUrl($menuid) ) {
						$result .= "checked";
						break;
					}elseif ( substr($menuid_data, 0, 13) == "customize.php" && substr($menuid_data, 0, 13) == substr($menuid, 0, 13) ) {
						$result .= "checked";
						break;
					}

				}

			}

		}
	return $result;

	}


	// STYLE AND SCRIPT
	function amr_menu_editor_jscripts($hook) {

		if( $hook != $this->amr_menu_editor_page ) { return; }

			wp_enqueue_script( 'amr-menu-editor-jscripts', plugin_dir_url( __FILE__ ) .'script.js', array(), '1.0.0', true);

			wp_register_style( 'amr-menu-editor-styles', plugin_dir_url( __FILE__ ) .'style.css', false, '1.0.0' );
			wp_enqueue_style( 'amr-menu-editor-styles' );
	}


	// SAVE THE OPTIONS
	function amr_admin_menu_saver() {
		global $_REQUEST;

		if ( isset($_REQUEST['allowing']) && $_REQUEST['allowing'] == "true" ) { $allowlink = "&allowing=true"; } else { $allowlink = "&allowing=false"; }


		if ( isset($_REQUEST['action']) && $_REQUEST['action'] == "amr_menu_editor_save" ) {

				$current_options = get_option( 'amr_admin_menu', array() );

				$datatata = $this->regular_menu_option( $_REQUEST );


			if( $datatata[ $_REQUEST['role'] ] == "" ) {
				unset($current_options[ $_REQUEST['role'] ]);
			}


				$new_admin_menu_data = array_merge($current_options, $datatata);
				update_option( 'amr_admin_menu', $new_admin_menu_data );
				$this->amr_forward_page("options-general.php?page=admin-menu-restriction&role=".urlencode($_REQUEST['role']).$allowlink."&settings-updated");


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


		//<input type='checkbox' class='subitem' id='".$amr_item_no."' name='subitem__".$amr_item_no."' value='".$item[2]." | ".$sub_key." | ".$sub_item[2]."' ".amr_checkifexist( "sub", $item[2], $sub_key, $options ).">";


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
	function find_sub_key($menu_page, $menu_item, $allowthis = false) {
		global $submenu, $allowed_subs;


		if ( !is_array($submenu[ $menu_page ]) ) $submenu[ $menu_page ] = array();

		foreach ( $submenu[ $menu_page ] as $sub_key_on_current => $sub_menu_details_on_current ) {

			$sub_menu_details_on_current[2] = isset($sub_menu_details_on_current[2]) ? $sub_menu_details_on_current[2] : null;

			if ( ($this->filterMenuUrl($sub_menu_details_on_current[2]) == $menu_item || $sub_menu_details_on_current[2] == $menu_item) ) {

				$sub_menu_key = $sub_key_on_current;

			}

			// CUSTOMIZE.PHP EXCEPTION
			if ( substr($sub_menu_details_on_current[2], 0, 13) == "customize.php" && substr($menu_item, 0, 13)  == "customize.php" ) {

				if ($allowthis == true ) {

					$allowed_subs[] = 'themes.php'.$sub_key_on_current.$menu_item;

				} else {

					unset($submenu[ 'themes.php' ][ $sub_key_on_current ]);

				}

			}


		}


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
	function amr_forward_page($direction) {
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

			// REMOVE THE PAGE
			if ( !remove_submenu_page( $menu_page, $menu_item ) ) unset( $submenu[ $menu_page ][ $sub_menu_key ] );

			// BLOCK THE PAGE ACCESS
			$this->block_page_access($menu_item, "sub", $dontdo, $itemunique);

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
			$this->block_page_access($menu_page, "top");

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
		foreach ($this->menus_for_delete as $user => $menu_types) {


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
								$sub_menu_key = $this->find_sub_key($menu_page, $menu_item, true);

								// ADD TO ALLOWED ITEMS
								$allowed_subs[] = $menu_page.$sub_menu_key.$menu_item;
								$allowed_tops[] = $menu_page;

							}
						}


					} elseif ( $menu_type == "top_allow" ) { // TOP ALLOWING


						foreach ($menu_pages as $menu_page => $menu_items) {
							foreach ($menu_items as $menu_item) {

								// ADD TO ALLOWED ITEMS
								$allowed_tops[] = $menu_page;
								$allowed_subs[] = $menu_page."0".$menu_page;

							}
						}


					} elseif ( $menu_type == "sub" ) { // SUB DELETION


						foreach ($menu_pages as $menu_page => $menu_items) {
							foreach ($menu_items as $menu_item) {

								// DO SUB DELETION
								$this->block_subpage( $menu_page, $sub_menu_key, $menu_item );

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
						(( array_key_exists('sub_allow', $menu_types) || array_key_exists('top_allow', $menu_types) ) && ($i == $len - 1)) ||
						( !array_key_exists('sub_allow', $menu_types) && !array_key_exists('top_allow', $menu_types) && !array_key_exists('top', $menu_types) && !array_key_exists('sub', $menu_types) )
					) {


						// SUB ALLOW ====================================================================================
						foreach ($submenu as $page_menu => $items_menu ) {
							foreach ($items_menu as $item_number_menu => $item_details_menu) {

								$item_details_menu[2] = isset($item_details_menu[2]) ? $item_details_menu[2] : null;

								$this->block_subpage( $page_menu, $item_number_menu, $item_details_menu[2], $allowed_subs );

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

?>