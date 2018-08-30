<?php



// Create a Temporary User
$user_id = wp_insert_user(array(
    'user_login'  => 'amr_temp_user'.rand(1000,9999),
    'user_pass'   => wp_generate_password( 12, false ),
    'role' 		  => $_REQUEST['role']
));

// If not successful
if ( is_wp_error( $user_id ) ) $user_id = null;




$filtred_submenu = $submenu;
// Loop over submenus and remove pages for which the user does not have privs.
foreach ($submenu as $parent => $sub) {
	foreach ($sub as $index => $data) {

		if (
			$data[1] == "switch_themes" &&
			( user_can($user_id, 'switch_themes') || user_can($user_id, 'edit_theme_options') )
		) continue;


		if ( !user_can($user_id, $data[1]) ) {
			unset($filtred_submenu[$parent][$index]);
		}

	}
	unset($index, $data);

	if ( empty($filtred_submenu[$parent]) )
		unset($filtred_submenu[$parent]);
}
unset($sub, $parent);



/*
 * Loop over the top-level menu.
 * Menus for which the original parent is not accessible due to lack of privileges
 * will have the next submenu in line be assigned as the new menu parent.
 */
$filtred_menu = $menu;
foreach ($menu as $id => $data) {
	if ( empty($submenu[$data[2]]) )
		continue;

	$subs = $submenu[$data[2]];
	$first_sub = reset( $subs );
	$old_parent = $data[2];
	$new_parent = $first_sub[2];


	if ( $new_parent != $old_parent ) {
		$_wp_real_parent_file[$old_parent] = $new_parent;
		$menu[$id][2] = $new_parent;

		foreach ($submenu[$old_parent] as $index => $data) {
			$filtred_submenu[$new_parent][$index] = $submenu[$old_parent][$index];
			unset($filtred_submenu[$old_parent][$index]);
		}
		unset($filtred_submenu[$old_parent], $index);

		if ( isset($_wp_submenu_nopriv[$old_parent]) )
			$_wp_submenu_nopriv[$new_parent] = $_wp_submenu_nopriv[$old_parent];
	}

}
unset($id, $data, $subs, $first_sub, $old_parent, $new_parent);



/*
 * Remove menus that have no accessible submenus and require privileges
 * that the user does not have. Run re-parent loop again.
 */
foreach ( $filtred_menu as $id => $data ) {
	if ( ! user_can($user_id, $data[1]) )
		$_wp_menu_nopriv[$data[2]] = true;

	/*
	 * If there is only one submenu and it is has same destination as the parent,
	 * remove the submenu.
	 */
	if ( ! empty( $filtred_submenu[$data[2]] ) && 1 == count ( $filtred_submenu[$data[2]] ) ) {
		$subs = $filtred_submenu[$data[2]];
		$first_sub = reset( $subs );
		if ( $data[2] == $first_sub[2] )
			unset( $filtred_submenu[$data[2]] );
	}

	// If submenu is empty...
	if ( empty($filtred_submenu[$data[2]]) ) {
		// And user doesn't have privs, remove menu.
		if ( isset( $_wp_menu_nopriv[$data[2]] ) ) {
			unset($filtred_menu[$id]);
		}
	}

	if ($data[4] == "wp-menu-separator") $filtred_menu[$id][4] .= ' '.$data[2];
}
unset($id, $data, $subs, $first_sub);



// Prevent adjacent separators
$prev_menu_was_separator = false;
foreach ( $filtred_menu as $id => $data ) {
	if ( false === stristr( $data[4], 'wp-menu-separator' ) ) {

		// This item is not a separator, so falsey the toggler and do nothing
		$prev_menu_was_separator = false;
	} else {

		// The previous item was a separator, so unset this one
		if ( true === $prev_menu_was_separator ) {
			unset( $filtred_menu[ $id ] );
		}

		// This item is a separator, so truthy the toggler and move on
		$prev_menu_was_separator = true;
	}
}
unset( $id, $data, $prev_menu_was_separator );

// Remove the last menu item if it is a separator.
$last_menu_key = array_keys( $filtred_menu );
$last_menu_key = array_pop( $last_menu_key );
if ( !empty( $filtred_menu ) && 'wp-menu-separator' == $filtred_menu[ $last_menu_key ][ 4 ] )
	unset( $filtred_menu[ $last_menu_key ] );
unset( $last_menu_key );




// Delete the Created Temporary User
wp_delete_user($user_id);