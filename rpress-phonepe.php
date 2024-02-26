<?php
/**
 * Plugin Name:       Restropress - PhonePe gateway
 * Description:       Through this plugin you can able to pay using PhonePe payment gateway.
 * Version:           1.0.0
 * Author:            magnigenie
 * Author URI:        https://restropress.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rp-phonepe
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/admin-phonepe.php';

