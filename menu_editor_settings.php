<?php


$_wp_submenu_nopriv = array();
$_wp_menu_nopriv = array();
// Loop over submenus and remove pages for which the user does not have privs.
foreach ($submenu as $parent => $sub) {
	foreach ($sub as $index => $data) {
		if ( ! $this->role_has_capability($_REQUEST['role'],$data[1]) ) {
			unset($submenu[$parent][$index]);
			$_wp_submenu_nopriv[$parent][$data[2]] = true;
		}
	}
	unset($index, $data);

	if ( empty($submenu[$parent]) )
		unset($submenu[$parent]);
}
unset($sub, $parent);

/*
 * Loop over the top-level menu.
 * Menus for which the original parent is not accessible due to lack of privileges
 * will have the next submenu in line be assigned as the new menu parent.
 */
foreach ( $menu as $id => $data ) {
	if ( empty($submenu[$data[2]]) ) continue;
	$subs = $submenu[$data[2]];
	$first_sub = reset( $subs );
	$old_parent = $data[2];
	$new_parent = $first_sub[2];
	/*
	 * If the first submenu is not the same as the assigned parent,
	 * make the first submenu the new parent.
	 */
	if ( $new_parent != $old_parent ) {
		/*$_wp_real_parent_file[$old_parent] = $new_parent;
		$menu[$id][2] = $new_parent;

		foreach ($submenu[$old_parent] as $index => $data) {
			$submenu[$new_parent][$index] = $submenu[$old_parent][$index];
			unset($submenu[$old_parent][$index]);
		}
		unset($submenu[$old_parent], $index);*/

		if ( isset($_wp_submenu_nopriv[$old_parent]) )
			$_wp_submenu_nopriv[$new_parent] = $_wp_submenu_nopriv[$old_parent];
	}
}
unset($id, $data, $subs, $first_sub, $old_parent, $new_parent);


/*
 * Remove menus that have no accessible submenus and require privileges
 * that the user does not have. Run re-parent loop again.
 */
foreach ( $menu as $id => $data ) {
	if ( ! $this->role_has_capability($_REQUEST['role'],$data[1]) )
		$_wp_menu_nopriv[$data[2]] = true;

	/*
	 * If there is only one submenu and it is has same destination as the parent,
	 * remove the submenu.
	 */
	if ( ! empty( $submenu[$data[2]] ) && 1 == count ( $submenu[$data[2]] ) ) {
		$subs = $submenu[$data[2]];
		$first_sub = reset( $subs );
		if ( $data[2] == $first_sub[2] )
			unset( $submenu[$data[2]] );
	}

	// If submenu is empty...
	if ( empty($submenu[$data[2]]) ) {
		// And user doesn't have privs, remove menu.
		if ( isset( $_wp_menu_nopriv[$data[2]] ) ) {
			unset($menu[$id]);
		}
	}
}
unset($id, $data, $subs, $first_sub);




/**
 * Filter whether to enable custom ordering of the administration menu.
 *
 * See the 'menu_order' filter for reordering menu items.
 *
 * @since 2.8.0
 *
 * @param bool $custom Whether custom ordering is enabled. Default false.
 */


// Remove the last menu item if it is a separator.
$last_menu_key = array_keys( $menu );
$last_menu_key = array_pop( $last_menu_key );
if ( !empty( $menu ) && 'wp-menu-separator' == $menu[ $last_menu_key ][ 4 ] )
	unset( $menu[ $last_menu_key ] );
unset( $last_menu_key );
