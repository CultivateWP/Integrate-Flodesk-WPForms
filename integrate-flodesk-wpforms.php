<?php
/**
 * Plugin Name: Integrate Flodesk and WPForms
 * Plugin URI:  https://cultivatewp.com/our-plugins/integrate-flodesk-wpforms/
 * Description: Create Flodesk signup forms using WPForms
 * Version:     1.0.0
 * Author:      CultivateWP
 * Author URI:  https://cultivatewp.com
 * Text Domain: integrate-flodesk-wpforms
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.  You may NOT assume that you can use any other
 * version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package    Integrate_Flodesk_WPForms
 * @since      1.0.0
 * @copyright  Copyright (c) 2017, Bill Erickson
 * @license    GPL-2.0+
 */

 // Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INTEGRATE_FLODESK_WPFORMS_FILE', plugin_basename( __FILE__ ) );
define( 'INTEGRATE_FLODESK_WPFORMS_URL', plugin_dir_url( __FILE__ ) );
define( 'INTEGRATE_FLODESK_WPFORMS_PATH', __DIR__ );
define( 'INTEGRATE_FLODESK_WPFORMS_VERSION', '1.0.0' );

/**
 * Load the class
 */
function integrate_flodesk_wpforms() {
    require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-flodesk-wpforms.php';
}
add_action( 'wpforms_loaded', 'integrate_flodesk_wpforms' );

