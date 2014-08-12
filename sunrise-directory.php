<?php
/**
 * @package   Sunrise_Directory
 * @author    Brad Trivers <brad@sunriseweb.ca>
 * @license   GPL-2.0+
 * @link      http://sunriseweb.ca
 * @copyright 2014 Sunrise Solutions Inc.
 *
 * @wordpress-plugin
 * Plugin Name:       Sunrise Directory
 * Plugin URI:        http://sunriseweb.ca
 * Description:       Creates an online directory for managing large organizational structures with real-time export to PDF.  For example the United Church of Canada Conference directories.
 * Version:           1.0.0
 * Author:            Brad Trivers
 * Author URI:        http://sunriseweb.ca
 * Text Domain:       sunrise-directory-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/<owner>/<repo>
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-sunrise-directory.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Sunrise_Directory', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Sunrise_Directory', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Sunrise_Directory', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * If you want to exclude Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {  
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */

if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-sunrise-directory-admin.php' );
	add_action( 'plugins_loaded', array( 'Sunrise_Directory_Admin', 'get_instance' ) );

}

//Add ACF options page - requireds Advanced Custom Fields plugin
if( function_exists('acf_add_options_page') ) {
 
	acf_add_options_page(array(
		'page_title' 	=> __( 'Sunrise Directory Settings', 'sunrise-directory' ),
		'menu_title'	=> __( 'Directory Settings', 'sunrise-directory' ),
		'menu_slug' 	=>  'sunrise-directory',
		'capability'	=> 'manage_options',
		'redirect'		=> false
	));
 
}

/*----------------------------------------------------------------------------*
 * Functions and shortcodes
 *----------------------------------------------------------------------------*/
add_shortcode('directoryOrg', 'Sunrise_Directory::directoryOrg_shortcode');

/**
 * This function trims a trailing ", " from a string         	 
 *
 * @since    1.0.0
 */
if( !function_exists('trimCommaSpace') ) {
  function trimCommaSpace($instring) {
    if(substr($instring,strlen($instring)-2,strlen($instring)) == ', ') {
        $instring = substr($instring,0,strlen($instring)-2);  
    }
    return $instring;
  }
}
  
/**
 * This function adds a trailing space if input string not empty - else returns ''         	 
 *
 * @since    1.0.0
 */
if( !function_exists('sd_ats') ) {
  function sd_ats($instring, $trailer = " ", $starter = "") {
    if( !empty($instring) ) 
      $result = $starter . $instring . $trailer;
    return $result;
  }
}