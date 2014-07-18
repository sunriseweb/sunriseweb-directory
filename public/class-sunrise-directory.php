<?php
/**
 * Plugin Name.
 *
 * @package   Sunrise_Directory
 * @author    Brad Trivers <brad@sunriseweb.ca>
 * @license   GPL-2.0+
 * @link      http://sunriseweb.ca
 * @copyright 2014 Sunrise Solutions Inc.
 */

/**
 * Plugin class. This class is used to work with the
 * public-facing side of the WordPress site.
 *
 * Any administrative or dashboard
 * functionality, can be found in `class-sunrise-directory-admin.php`
 *
 * @package Sunrise_Directory
 * @author  Brad Trivers <brad@sunriseweb.ca>
 */
class Sunrise_Directory {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'sunrise-directory';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( '@TODO', array( $this, 'action_method_name' ) );
		add_filter( '@TODO', array( $this, 'filter_method_name' ) );
		
		/* Register Custom Taxonomies and Post Types */
    add_action( 'init', array( $this, 'register_custom_taxonomies_and_post_types' ) );
    
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();

					restore_current_blog();
				}

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

					restore_current_blog();

				}

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}
	
	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

  /**
	 * Registers custom taxonomies:
	 *    	 
	 * 
	 * and custom post types: 
	 *   people
	 *   presbyteries        	 
	 *
	 * @since    1.0.0
	 */
	public function register_custom_taxonomies_and_post_types() {
	   //Directory Orgs custom taxonomy
	   register_taxonomy( 'directory',
        array (
          0 => 'people',
          1 => 'presbyteries',
        ),
        array( 'hierarchical' => true,
        	'label' => 'Directory Orgs',
        	'show_ui' => true,
        	'query_var' => true,
        	'show_admin_column' => false,
        	'labels' => 
              array (
                'search_items' => 'Directory Org.',
                'popular_items' => '',
                'all_items' => '',
                'parent_item' => '',
                'parent_item_colon' => '',
                'edit_item' => '',
                'update_item' => '',
                'add_new_item' => '',
                'new_item_name' => '',
                'separate_items_with_commas' => '',
                'add_or_remove_items' => '',
                'choose_from_most_used' => '',
              )
        ) 
     );
     
     //Conferences custom taxonomy
     register_taxonomy( 'conferences',
        array (
          0 => 'people',
          1 => 'presbyteries',
        ),
        array( 'hierarchical' => false,
        	'label' => 'Conferences',
        	'show_ui' => true,
        	'query_var' => true,
        	'show_admin_column' => false,
        	'labels' => 
              array (
                'search_items' => 'Conference',
                'popular_items' => '',
                'all_items' => '',
                'parent_item' => '',
                'parent_item_colon' => '',
                'edit_item' => '',
                'update_item' => '',
                'add_new_item' => '',
                'new_item_name' => '',
                'separate_items_with_commas' => '',
                'add_or_remove_items' => '',
                'choose_from_most_used' => '',
              )
        ) 
     );  
	   
     //People CPT
     register_post_type('people', 
      array(
        'label' => 'People',
        'description' => 'Used to store information about people.',
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'people', 'with_front' => true),
        'query_var' => true,
        'has_archive' => true,
        'menu_position' => '10',
        'supports' => array('title','excerpt','revisions','thumbnail'),
        'taxonomies' => array('directory', 'conferences'),
        'labels' => 
          array (
            'name' => 'People',
            'singular_name' => 'Person',
            'menu_name' => 'People',
            'add_new' => 'Add Person',
            'add_new_item' => 'Add New Person',
            'edit' => 'Edit',
            'edit_item' => 'Edit Person',
            'new_item' => 'New Person',
            'view' => 'View Person',
            'view_item' => 'View Person',
            'search_items' => 'Search People',
            'not_found' => 'No People Found',
            'not_found_in_trash' => 'No People Found in Trash',
            'parent' => 'Parent Person',
          )
        ) 
      );
      
      //Presbyteries CPT
      register_post_type('presbyteries', 
        array(
          'label' => 'Presbyteries',
          'description' => 'Presbyteries that are part of the Maritime Conference of the United Church of Canada.',
          'public' => true,
          'show_ui' => true,
          'show_in_menu' => true,
          'capability_type' => 'post',
          'map_meta_cap' => true,
          'hierarchical' => true,
          'rewrite' => array('slug' => 'presbyteries', 'with_front' => true),
          'query_var' => true,
          'has_archive' => true,
          'supports' => array('title','editor','excerpt','trackbacks','custom-fields','comments','revisions','thumbnail','author','page-attributes'),
          'taxonomies' => array('directory', 'conferences'),
          'labels' => 
            array (
              'name' => 'Presbyteries',
              'singular_name' => 'Presbytery',
              'menu_name' => 'Presbyteries',
              'add_new' => 'Add Presbytery',
              'add_new_item' => 'Add New Presbytery',
              'edit' => 'Edit',
              'edit_item' => 'Edit Presbytery',
              'new_item' => 'New Presbytery',
              'view' => 'View Presbytery',
              'view_item' => 'View Presbytery',
              'search_items' => 'Search Presbyteries',
              'not_found' => 'No Presbyteries Found',
              'not_found_in_trash' => 'No Presbyteries Found in Trash',
              'parent' => 'Parent Presbytery',
            )
          ) 
      );	
	}
	
}
