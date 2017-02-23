<?php

if( defined( 'ABSPATH') && defined('WP_UNINSTALL_PLUGIN') ) {

	//Remove the plugin's settings
	if ( get_option( 'amr_admin_menu' ) ) {
		delete_option( 'amr_admin_menu' );
		delete_site_option( 'amr_admin_menu' );
	}

}

?>