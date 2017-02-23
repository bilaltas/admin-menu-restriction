<?php
/*
Plugin Name: Admin Menu Restriction
Plugin URI: http://bilaltas.net
Description: The best and easiest plugin to restrict admin menu items.
Author: Bilal TAS
Author URI: http://bilaltas.net
Version: 0.0.1
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once( dirname( __FILE__ ).'/amr-class.php' );
$adminMenuEditor = new AdminMenuRestriction();