<?php
/*
Plugin Name: Admin Menu Restriction
Plugin URI: https://www.bilaltas.net
Description: The best and easiest plugin to restrict admin menu items.
Author: Bilal TAS
Author URI: https://www.bilaltas.net
Version: 1.0.0
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once( dirname( __FILE__ ).'/amr-class.php' );
$adminMenuEditor = new AdminMenuRestriction();