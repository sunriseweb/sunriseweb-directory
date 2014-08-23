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
    
    /* Add extra ACF metadata after WP All Import saves a post */
    add_action( 'pmxi_saved_post', array( $this, 'add_acf_metadata_after_wpallimport' ), 10, 1 );
    
    /* Change main loop for Directory Org taxonomy archive pages based on DisplayType */
    add_action( 'pre_get_posts', array( $this, 'modify_archive_loop' ), 100, 1 );
    
    /* Add people metadata to single person display post content */
    add_filter( 'the_content', array( $this, 'person_content' ) );
    
    /* Add export to csv / pdf if people CPT or directory taxonomy archive */
    add_filter( 'the_content', array( $this, 'add_export_area' ) );    
    
    /* Filter template used for Directory taxonomy */
    add_filter( 'template_include', array( $this, 'custom_templates' ) );
    
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
		if( is_tax('directory') )
		  wp_enqueue_style( 'directory-org-styles', plugins_url( 'assets/css/directory_orgs.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		if( is_tax('directory') )
		  wp_enqueue_script( 'directory-org-shortcode-script', plugins_url( 'assets/js/directory_org_shortcode.js', __FILE__ ), array( 'jquery' ), self::VERSION );
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
	 * This function work in conjunction with 
	 * the Really Simple Captcha plugin
	 * to add an Export to PDF button.
	 * 
	 * If not logged in then must enter captcha.
	 * 
	 * An export to CSV button is added if user is logged in.               	 
	 *
	 * @since    1.0.0
	 */
	public function add_export_area($content) {

	  if ( !is_tax('directory') )
	   return $content;
	   
	  $result = '';
	  $fileSlug = get_queried_object()->slug;
	  $thisName = get_queried_object()->name;
	  $directory_term_id = get_queried_object()->term_id;
	  $displayType = get_field('display_type', 'directory_'.$directory_term_id);
	  $expandDirectory = get_field('expand_directory', 'directory_'.$directory_term_id);
	  $termChildren = get_term_children($directory_term_id,'directory');
    $numChildren = count($termChildren);
	  
    global $wp;
//     $current_url = home_url(add_query_arg(array(),$wp->request));
    $currentURL = 'http://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    //Check to see what current URL ends with and choose proper append character
    if(substr($currentURL,strlen($currentURL)-1,1) == "/") {
      $currentURL .= "?";
    } else {
      $currentURL .= "&";
    }
    
    $correct = false;
    if(!is_user_logged_in() && isset($_GET['exportToPDF']) && $_GET['exportToPDF'] == "Y" && class_exists('ReallySimpleCaptcha')) {
    
      $captcha_instance = new ReallySimpleCaptcha();
      $captcha_instance->tmp_dir = plugin_dir_path( __FILE__ ) . '/reallysimplecaptchas/';
      
      //Validate captcha
      if( isset($_POST['export-captchaPrefix']) && isset($_POST['export-captchaEntry']) ) {
      
        $prefix = intval($_POST['export-captchaPrefix']);
        $captchaResponse = trim($_POST['export-captchaEntry']);  
        $correct = $captcha_instance->check($prefix, $captchaResponse);
        
      } 
      
    } else {
    
      $correct = true;
      
    }
    
    $result = '<div class="exportResults">';
    
    //Set File Defaults
    $filepath = plugin_dir_path( __FILE__ ) . 'directory_exports/';
    $filebaseurl = plugins_url( '/directory_exports/', __FILE__ ); 
    $filename = 'export';
    
    //Use options to store file name and last date generated combination.
    $checkfiledatetime = time();
    $getlastgendatetimeCSV = get_option( $fileSlug.$filename.'.csv', $checkfiledatetime ); //If filename option doesn't exist then defaults to time()
    $getlastgendatetimePDF = get_option( $fileSlug.$filename.'.pdf', $checkfiledatetime ); //If filename option doesn't exist then defaults to time()

    $fileprefixCSV = $fileSlug.'_'.date('Y-m-d-His_', $getlastgendatetimeCSV);
    $fileprefixPDF = $fileSlug.'_'.date('Y-m-d-His_', $getlastgendatetimePDF);
    $fileURLCSV = $filebaseurl.$fileprefixCSV.$filename;
    $fileURLPDF = $filebaseurl.$fileprefixPDF.$filename;
    
    if( file_exists($filepath.$fileprefixCSV.$filename.'.csv') && current_user_can('edit_post') )
//           echo do_shortcode('[filedownload file="'.$fileURLCSV.'.csv" type="application/vnd.ms-excel"]Download Last Exported CSV File - '.$fileprefixCSV.$filename.'.csv[/filedownload]').'</p>';
      $result .= '<a class="sd-lastcsv" target="_new" href="'.$fileURLCSV.'.csv" type="application/vnd.ms-excel" target="_blank">'.__('Last Exported CSV File = ').$fileprefixCSV.$filename.'.csv</a>';   
      
    if(file_exists($filepath.$fileprefixPDF.$filename.'.pdf'))           
      $result .= '<a class="sd-lastpdf" target="_new" href="'.$fileURLPDF.'.pdf" type="application/pdf" target="_blank">'.__('Last Exported PDF File = ').$fileprefixPDF.$filename.'.pdf</a>';
    
    //Display results or submit button/form depending if rquest submitted and captcha is correct 
    if( ( ( isset($_GET['exportToExcel']) && $_GET['exportToExcel'] == "Y" ) 
          ||  
          ( isset($_GET['exportToPDF']) && $_GET['exportToPDF'] == "Y" ) ) 
        && $correct ) {
        
        //Check to see if can regenerate file
        $timetoregenerate = 60*60*1*-1; //non-admins can regenerate every 1 hours 60 sec/min * 60 min/hour * 1 hours
        if ( current_user_can('edit_post') ) //Admins can regenerate every 5 minutes
          $timetoregenerate = 60*.1*-1; //60 sec/min * 5 min
        
        //Set filesuffix and getlastgendatetime based on what type of file is being generated
        if( isset($_GET['exportToExcel']) && $_GET['exportToExcel'] == "Y" ) {
          $filesuffix = '.csv';
          $getlastgendatetime = $getlastgendatetimeCSV;
        } else {
          $filesuffix = '.pdf';
          $getlastgendatetime = $getlastgendatetimePDF;
        }
        
        $timedifference = $getlastgendatetime-$checkfiledatetime;
        $gennewfile = false;
        if ( $getlastgendatetime == $checkfiledatetime || $timedifference < $timetoregenerate ) { 
            $setlastgendatetime = update_option( $fileSlug.$filename.$filesuffix, $checkfiledatetime ); //Update the option with new last generated time for checking
            
            $getsecondlastgendatetime = get_option( 'secondlast_'.$fileSlug.$filename.$filesuffix, '' );
            
            if( $getlastgendatetime != $checkfiledatetime) //Only create second last if a version already exists
              $secondlastgendatetime = update_option( 'secondlast_'.$fileSlug.$filename.$filesuffix, $getlastgendatetime ); //Update secondlast option with old file information
            
            if( $getsecondlastgendatetime != '') //Delete second last stored file if there was that many versions generated
              unlink($filepath.$fileSlug.'_'.date('Y-m-d-His_', $getsecondlastgendatetime).$filename.$filesuffix);
                        
            $fileprefix = $fileSlug.'_'.date('Y-m-d-His_', $checkfiledatetime);
            $fileURL = $filebaseurl.$fileprefix.$filename;
            $gennewfile = true;
        }
          
        //Display / Generate CSV
        if( isset($_GET['exportToExcel']) && $_GET['exportToExcel'] == "Y" ) {
        
          if($gennewfile) { //If enough time has elapsed since last file generation the allow new file to be generated
                    
            $fhw = fopen($filepath.$fileprefix.$filename.$filesuffix,'xb') or die("Error opening file for write.");
            $firsttime = true;
            if (have_posts()) : while (have_posts()) : the_post();
                    
                    $thisPerson = $this->get_person_csv_export(get_the_id());
                    
                    //If first time through then get column headings
                    if($firsttime) {
                      $columnHeadings = array();
                      foreach($thisPerson as $columnName => $columnValue) {
                        $columnHeadings[] = $columnName;
                      }
                      fputcsv($fhw,$columnHeadings,',','"');
                      $firsttime = false;
                    }
        
                    fputcsv($fhw,$thisPerson,',','"');
                    
            endwhile; endif;
            
            rewind_posts();
            
            fclose($fhw);
            
  //           $result .= do_shortcode('[filedownload file="'.$fileURL.$filesuffix.'" type="application/vnd.ms-excel"]Download Exported CSV File[/filedownload]');
            $result .= '<a href="'.$fileURL.$filesuffix.'" type="application/vnd.ms-excel" target="_blank">'.__('Newly Exported CSV File = ').$fileprefix.$filename.$filesuffix.'</a>';
            
          } else {
            $timeremaining = ($timetoregenerate*-1) + $timedifference;
            $minutes = floor($timeremaining/60);
            $seconds = fmod($timeremaining,60);
            $duration = $this->duration( $timeremaining, $if_reached=null );
            $result .= __('You may generate a new export in ').$duration;            
          }
            
        }
        
        //Display / Generate PDF
        if( isset($_GET['exportToPDF']) && $_GET['exportToPDF'] == "Y" ) {
        
          if($gennewfile) { //If enough time has elapsed since last file generation the allow new file to be generated 
         
            include( plugin_dir_path( __FILE__ ) . '/includes/createPDF.php' );
            
            $result .= '<a href="'.$fileURL.$filesuffix.'" type="application/pdf" target="_blank">'.__('Newly Exported PDF File = ').$fileprefix.$filename.$filesuffix.'</a>';
              
          } else {
            $timeremaining = ($timetoregenerate*-1) + $timedifference;
            $minutes = floor($timeremaining/60);
            $seconds = fmod($timeremaining,60);
            $duration = $this->duration( $timeremaining, $if_reached=null );
            $result .= __('You may generate a new export in ').$duration;            
          }
            
        } //check if exportToPDF is set
        
        //Delete option if something has gone wrong and file doesn't exist.
        if(!file_exists($filepath.$fileprefix.$filename.$filesuffix)) {
          delete_option($fileSlug.$filename.$filesuffix);
        }
        
    } else {
    
        if(!class_exists('ReallySimpleCaptcha') || is_user_logged_in() ) { //
          
          $result .= sd_ats('<a href="'.$currentURL.'exportToPDF=Y">'.__("Export Results to PDF File").'</a>', ' | ');
          
          if( is_user_logged_in() )
            $result .= '<a href="'.$currentURL.'exportToExcel=Y">'.__("Export Results to CSV File").'</a>';
          
        } elseif( class_exists('ReallySimpleCaptcha') ) {
        
          if( $correct != true && isset($_GET['exportToPDF']) && $_GET['exportToPDF'] == "Y" ) //captcha failed
            $result .= '<p class="captcha_error">'.__('Incorrect captcha text entered - please try again.').'</p>';
          
          $result .= '<a id="exportToPDF" href="#">Export Results to PDF File</a>';
          $result .= '<div id="exportToPDFForm" class="hide-area">';
          $result .= '<form id="requestExportToPDF" action="'.$currentURL.'exportToPDF=Y" method="post">';              
          $captcha_instance = new ReallySimpleCaptcha();
          $captcha_instance->tmp_dir = plugin_dir_path( __FILE__ ) . '/reallysimplecaptchas/';
          $word = $captcha_instance->generate_random_word();
          $prefix = mt_rand();
          $captchaImage = $captcha_instance->generate_image($prefix, $word);
    //               echo $_SERVER['SCRIPT_FILENAME'];
    //               $x = plugins_url().'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)); 
    //               $result .= "<h4>word=$word, prefix=$prefix, captchaImage=$captchaImage</h4>";
          $result .= '<img class="reallysimplecaptcha-image" src="'.plugins_url( '/reallysimplecaptchas/'.$captchaImage, __FILE__ ).'" />';
          $result .= '<input name="export-captchaEntry" type="text" placeholder="'.__("replace with image text").'">';
          $result .= '<input type="hidden" name="export-captchaPrefix" value="'.$prefix.'">';
          $result .= '<input id="exportToPDF" class="sd-submit" name="submitpdf" type="submit" value="'.__("Export Results to PDF File").'">';
          $result .= '</form>';
          $result .= '</div>';
          
        }
    
    } //end of check if export request was submitted
    
    $result .= '</div>';
    
    return $result.$content;
    
	}

  /**
	 * Get all post ids for a given post_type, taxonomy and term
	 *  - Directory Org taxonomy
	 *  - People CPT
	 *  - specific Directory Org(s) as term      	 
	 * 	 
	 * @since    1.0.1
	 */
	public function get_post_ids( $post_type='people', $taxonomy='directory', $term ) {
	
    return get_posts(array(
        'numberposts'   => -1, // get all posts.
        'orderby' => 'title',
        'order' => 'ASC',
        'post_type' => $post_type,
        'tax_query'     => array(
            array(
                'taxonomy'  => $taxonomy,
                'field'     => 'id',
                'terms'     => is_array($term) ? $term : array($term),
            ),
        ),
        'fields'        => 'ids', // only get post IDs.
    ));
    
  }
  	
	/**
	 * Use pre_get_posts to modify archive pages:
	 *  - Directory Org taxonomy
	 *  - People CPT   	 
	 * 	 
	 * @since    1.0.1
	 */
	public function modify_archive_loop( $query ) {

    //Modify directory org archive loop
	  if ( get_queried_object()->taxonomy == 'directory' && $query->is_main_query() ) { //need to also check that post_type is "people"?

	      //Get Directory Org metadata setting - display_type
	      $directory_term_id = get_queried_object()->term_id;
	      $displayType = get_field('display_type', 'directory_'.$directory_term_id);
	      
	      //post_type=people&nopaging=true&posts_per_page=-1&orderby=title&order=ASC&conferences=".get_current_conference()
	      $query->set( 'post_type', 'people' );
        $query->set( 'orderby', 'title' );
	      $query->set( 'order', 'ASC' );
	      $query->set( 'conferences', $this->get_current_conference() );
	      
	      if($displayType == 'PagedList') {
	        $query->set( 'posts_per_page', 50 );
        } else {
          $query->set( 'posts_per_page', -1 );
          $query->set( 'nopaging', true );
        }
        
        return;
    }
    
    //Modify people CPT archive loop
	  if ( is_post_type_archive('people') && $query->query['post_type'] == 'people' && $query->is_main_query() ) {
	      
	      $query->set( 'orderby', 'title' );
	      $query->set( 'order', 'ASC' );
	      $query->set( 'posts_per_page', 50 );
	      
	      return;
	  }
		
	}
	
	/**
	 * Get Conference Year option for use in queries, etc.
	 * 	 
	 * @since    1.0.1
	 */
	public function get_current_conference() {
    $conferenceYear = get_field('conference_year','option');
    $currentYear = date('Y');
    $currentMonth = date('n');
    //Change this so set in theme options
    if($currentMonth >= 1 && $currentMonth <=11 ) {
      $altConfYear = $currentYear-1;
    } else {
      $altConfYear = $currentYear;
    }
    
    if(trim($conferenceYear) == "") {
      return $altConfYear;
    } else {
      return $conferenceYear;
    }
  }
 
	/**
	 * Filter template used for custom taxonomies and post types.
	 *  - Directory taxonomy	 
	 *
	 * @since    1.0.1
	 */
	public function custom_templates($template) {
	
  	if( is_tax( 'directory' ) ) {
  	   if ( locate_template( 'taxonomy-directory.php' ) == '' ) {
         // If neither the child nor parent theme have overridden the template,
         // we load the template from the 'templates' sub-directory of the plugin
         $template = plugin_dir_path( __FILE__ ) .'templates/taxonomy-directory.php';
       }          
  	}
   
    return $template;
	}
	
	/**
	 * Show list of Directory Orgs
	 * with ability to drill-down.
	 * 
	* Accepts one attribute specifying the directory org slug to start at in the hierarchy:          
	 *
	 * @since    1.0.1
	 */
	public function directoryOrg_shortcode($atts, $content = null) {
    $result = "";
    wp_enqueue_script( 'directory-org-shortcode-script', plugins_url( 'assets/js/directory_org_shortcode.js', __FILE__ ), array( 'jquery' ), self::VERSION ); 
  	extract(shortcode_atts(array(
  		"org" => ''
  	), $atts));
  	
  	$termID = 0; 
    //Use slug to get term id
    if($org != "") {
      $termArray = get_terms( 'directory', 'slug='.$org.'&hide_empty=0' );
      foreach($termArray as $term) {
        $termID = $term->term_id;
      }
  //     $termID = $org;
    }
    //Load Walker_Directory class
    require_once( plugin_dir_path( __FILE__ ) . 'includes/directoryWalker.php' );
    
    $result .= '<ul id="directoryList" class="post entry">';
    $args = array(
      'orderby'            => 'term_order',
      'order'              => 'ASC',
      'style'              => 'list',
      'show_count'         => 1,
      'hide_empty'         => 0,
      'use_desc_for_title' => 0,
      'child_of'           => $termID,
      'hierarchical'       => true,
      'title_li'           => null,
      'show_option_none'   => __('No Directory Orgs'),
      'number'             => NULL,
      'echo'               => 0,
      'depth'              => 0,
      'current_category'   => 0,
      'pad_counts'         => 1,
  //     'peopleByDirectory'  => $personCount,
      'taxonomy'           => 'directory');
    $args['walker'] = new Walker_Directory; 
    $result .= wp_list_categories($args);
    $result .= '</ul>';
  	return $result;
  }

  /**
	 *  Displays people metadata in the single CPT content 
	 *
	 * @since    1.0.0
	 */
	public function person_content($content) {
		if ( is_singular('people') && in_the_loop() ) {

        $content .= '<div class="personSummary">';
        
        $content .= Sunrise_Directory::display_person_long($post->ID);
        
        //Add in person's Directory Orgs
        
        $content .= '</div> <!-- end person -->';
        
    } elseif ( is_post_type_archive('people') && in_the_loop() ) {

        $content .= '<div class="personSummary">';
        
//         $content .= str_replace("<br />", " | ", Sunrise_Directory::display_person_short($post->ID) );
        $content .= Sunrise_Directory::display_person_short($post->ID);
        
        //Add in person's Directory Orgs
        
        $content .= '</div> <!-- end person -->';
        
    }
    return $content;
	}
	
	/**
	 *  Builds Person data for long display.
	 *
	 * @since    1.0.0
	 */
	public function display_person_long($personid, $name = false, $imagedimensions = array(250,250)) {
    $result = '';
    $fields_to_retrieve = array( 'salutation', 'first_name', 'middle_initial', 'last_name', 'email', 'second_email', 'home_phone', 'work_phone', 'address_line_1', 'address_line_2', 'city', 'province', 'postal_code', 'member_type', 'designation', 'ministry_status' );
    foreach($fields_to_retrieve as $fieldname) {
      $$fieldname = trim( str_replace("---", "", get_field($fieldname, $personid) ) );
    }
    
    $infotohide = trim(get_field('infotohide', $personid));
    if($infotohide != "" ) { //if some fields need to be hidden and user is not logged in
      $hiddenfields = str_replace(', ', ',',$infotohide);
      $hiddenfields = explode(',',$infotohide);
      foreach($hiddenfields as $findex => $hiddenfieldname ) {
        $trimmed = trim($hiddenfieldname);
        $$trimmed = ""; //clear out the $$field_name = $value set (if any) 
      }    
    }
     
    //Display Person metadata
    $result .= '<div class="personSummary">';
    
    if($name) {
      $displayname = the_title_attribute(array('echo'=> 0, 'post'=>$personid));
      $result .= '<h2 class="personTitle"><a href="'.get_permalink($personid).'" alt="'.$displayname.'">' . $displayname . '</a></h2>';
//       $result .= '<h2 class="personTitle">'.sd_ats($salutation).sd_ats($first_name).sd_ats($middle_initial).$last_name.'</h2>';
    }
      
    if ( has_post_thumbnail($personid)) {
       $large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id($personid), 'large');
       $result .= '<a href="' . $large_image_url[0] . '" alt="' . the_title_attribute('echo=0') . '" title="' . the_title_attribute('echo=0') . '" >';
       $result .= get_the_post_thumbnail($personid, $imagedimensions, array('class' => 'alignleft person-thumb')); 
       $result .= '</a>';
    }
    
    $result .= sd_ats( trimCommaSpace( sd_ats($member_type, ", ").sd_ats($designation, ", ").$ministry_status ), '<br />');
    $result .= sd_ats($address_line_1, '<br />');
    $result .= sd_ats($address_line_2, '<br />');
    $result .= sd_ats( trimCommaSpace(sd_ats($city, ', ').$province), '<br />' );
    $result .= sd_ats($postal_code,'<br />');
    $result .= sd_ats( trimCommaSpace( sd_ats( antispambot($email),'</a>, ', '<a href="mailto:'.antispambot($email).'">'). sd_ats( antispambot($second_email), '</a>' ,'<a href="mailto:'.antispambot($second_email).'">' ) ), '<br />' );
    $result .= sd_ats( trimCommaSpace( sd_ats($home_phone, ', ', '(H) ') . sd_ats($work_phone, ', ', '(O) ') . sd_ats($fax_number, '', '(F) ') ), '<br />' );
    
    $result .= '</div> <!-- end person -->';
      
    return $result;
	}
	
  	/**
	 *  Builds Person data for short display.
	 *
	 * @since    1.0.0
	 */
	 public function display_person_short($personid, $separator = '<br />') {
	   $result = '';
	   $fields_to_retrieve = array( 'salutation', 'first_name', 'middle_initial', 'last_name', 'email', 'second_email', 'home_phone', 'work_phone', 'address_line_1', 'address_line_2', 'city', 'province', 'postal_code' );
     foreach($fields_to_retrieve as $fieldname) {
        $$fieldname = trim( get_field($fieldname, $personid) );
     }
	   
	   $infotohide = trim(get_field('infotohide', $personid));
	   if($infotohide != "" ) { //if some fields need to be hidden and user is not logged in
      $hiddenfields = str_replace(', ', ',',$infotohide);
      $hiddenfields = explode(',',$infotohide);
      foreach($hiddenfields as $findex => $hiddenfieldname ) {
        $trimmed = trim($hiddenfieldname);
        $$trimmed = ""; //clear out the $$field_name = $value set (if any) 
      }    
    }
	   $displayname = sd_ats($salutation).sd_ats($first_name).$last_name; //sd_ats($middle_initial)
	   $result .= sd_ats( '<a class="plainPersonName" href="'.get_permalink($personid).'" alt="'.$displayname.'">' . $displayname . '</a>', $separator ); 
	   $result .= sd_ats( trimCommaSpace( sd_ats( antispambot($email),'</a>, ', '<a href="mailto:'.antispambot($email).'">') . sd_ats( antispambot($second_email),'</a>, ', '<a href="mailto:'.antispambot($second_email).'">') ) , $separator );
	   $result .= sd_ats( trimCommaSpace( sd_ats($home_phone, ', ', '(H) ') . sd_ats($work_phone, ', ', '(O) ') . sd_ats($fax_number, '', '(F) ') ) , $separator );
	   $result .= trimCommaSpace( sd_ats($address_line_1, ', ') . sd_ats($address_line_2, ', ') . sd_ats($city, ', ') . sd_ats($province, ', ') . $postal_code );
      
	   return $result;
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
                'search_items' => 'Directory Orgs',
                'popular_items' => 'Popular Directory Orgs',
                'all_items' => 'Directory Orgs',
                'parent_item' => 'Parent Directory Org',
                'parent_item_colon' => '',
                'edit_item' => 'Edit Directory Org',
                'update_item' => 'Update Directory Org',
                'add_new_item' => 'Add Directory Org',
                'new_item_name' => 'New Directory Org',
                'separate_items_with_commas' => '',
                'add_or_remove_items' => 'Add / Remove Directory Orgs',
                'choose_from_most_used' => 'Most Used Directory Orgs',
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
        'supports' => array('title','excerpt','revisions','thumbnail'), //'editor',
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
	
  /**      
   * Returns an array of field groups with fields for the passed CPT, where field group ACF location rule of "post_type == CPT" exists.
   *  - each field group points at an array of its fields, in turn pointed at an array of that field's detailed information:
   *    - array of info for each field [ ID, key, label, name, type, menu_order, instructions, required, id, class, conditional_logic[array()], etc. ]
   *  
   * @since    1.0.0      
  */
  public function get_acf_field_groups_by_cpt($cpt) {
    // need to create cache or transient for this data?
		
    $result = array();
    $acf_field_groups = acf_get_field_groups();
    foreach($acf_field_groups as $acf_field_group) {
      foreach($acf_field_group['location'] as $group_locations) {
        foreach($group_locations as $rule) {

            if($rule['param'] == 'post_type' && $rule['operator'] == '==' && $rule['value'] == $cpt) {
            
              $result[] = acf_get_fields( $acf_field_group );
                          
            }

        }
        
      }
        
    }
    
    return $result;
  }
  
  /**
	 * This function runs after WP ALL IMPORT saves a post.
	 * 
	 * It adds in the post meta field keys required for ACF.              	 
	 *
	 * @since    1.0.0
	 */	
	public function add_acf_metadata_after_wpallimport($post_id) {
    $cpt = get_post_type( $post_id );
    $field_groups = $this->get_acf_field_groups_by_cpt($cpt);
    foreach($field_groups as $fields) {
      foreach($fields as $field) {
        update_post_meta($post_id, '_'.$field['name'], $field['key']);
      }
    }
  }
  
  /**
	 * This function retrieves a person's information for export to CSV	 
	 *
	 * @since    1.0.0
	 */	
	public function get_person_csv_export($post_id) {
    $result = array( 'PersonID' => $post_id );
    $presbyteries_id = 0;
    $presbyteries = "";
    $pastoralCharges = "";
    $otherOrgs = "";    
    $directoryOrgs = get_the_terms($post_id, 'directory');  					
    if ( $directoryOrgs && ! is_wp_error( $directoryOrgs ) ) { 
    	foreach ( $directoryOrgs as $directoryOrg ) {
    	  //Check Presbytery - i.e. if parent is Presbyteries & Pastoral Charges directory org - ID=62
//     	  if($directoryOrg->parent == $presbyteries_id) { //62 is the ID of the Presbyteries & Pastoral Charges directory org
//           $presbyteries .= $directoryOrg->name.', ';
//         //Check if Pastoral Charge - i.e. a child of a Presbytery
//         } elseif ( in_array($directoryOrg->term_id, $presbyteryChildren) ) {
//           $pastoralCharges .= $directoryOrg->name.', ';
//         } else {
      		$otherOrgs .= $directoryOrg->name.', ';
//     		}
    		
    		$allDirectorySlugs .= $directoryOrg->slug.', ';
    	}
    }
    
    $conferences = get_the_terms($post_id, 'conferences');
    if ( $conferences && ! is_wp_error( $conferences ) ) {
      foreach ( $conferences as $conference ) {
        $allConferenceSlugs .= $conference->slug.', ';  
      }
      
    }
	  
	  $result['Presbytery'] = trimCommaSpace($presbyteries);
	  $result['Pastoral Charge(s)'] = trimCommaSpace($pastoralCharges);

    $field_groups = $this->get_acf_field_groups_by_cpt('people');
    foreach($field_groups as $fields) {
      foreach($fields as $field) {
        $result[$field['name']] = get_field($field['name']);
      }
    }
    
    $result['Directory Orgs'] = trimCommaSpace($otherOrgs);
    $result['taxonomy_directory'] = trimCommaSpace($allDirectorySlugs);
    $result['taxonomy_conferences'] = trimCommaSpace($allConferenceSlugs);
    
    return $result;
  }
  
/**
	 * This function determines if 
	 *
	 * @since    1.0.0
	 */  
  function duration( $int_seconds=0, $if_reached=null )
  {
      $key_suffix = 's';
      $periods = array(
                      'year'        => 31556926,
                      'month'        => 2629743,
                      'day'        => 86400,
                      'hour'        => 3600,
                      'minute'    => 60,
                      'second'    => 1
                      );
  
      // used to hide 0's in higher periods
      $flag_hide_zero = true;
  
      // do the loop thang
      foreach( $periods as $key => $length )
      {
          // calculate
          $temp = floor( $int_seconds / $length );
  
          // determine if temp qualifies to be passed to output
          if( !$flag_hide_zero || $temp > 0 )
          {
              // store in an array
              $build[] = $temp.' '.$key.($temp!=1?'s':null);
  
              // set flag to false, to allow 0's in lower periods
              $flag_hide_zero = false;
          }
  
          // get the remainder of seconds
          $int_seconds = fmod($int_seconds, $length);
      }
  
      // return output, if !empty, implode into string, else output $if_reached
      return ( !empty($build)?implode(', ', $build):$if_reached );
  }                
  	
} 