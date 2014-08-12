<?php get_header(); ?>

<div id="main-content">
	<div class="container">
		<div id="content-area" class="clearfix">
			<div id="left-area">
    
    	 <h1><?php echo get_queried_object()->name; ?></h1>
    	 
		<?php
		  $directory_term_id = get_queried_object()->term_id;
		  $displayType = get_field('display_type', 'directory_'.$directory_term_id);
		  $expandDirectory = get_field('expand_directory', 'directory_'.$directory_term_id);

      //NOTE: Main loop is modified using the pre_get_post filter prior to page load - see modify_directory_org_archive_loop function in Sunrise Directory plugin
			if ( have_posts() ) :
				while ( have_posts() ) : the_post();
				    if($displayType != 'PagedList') { //must be blank or DrillDown
    				  
              //Create array of posts by directoryOrg
              $directoryOrgs = get_the_terms($post->ID, 'directory');
              if ( $directoryOrgs && ! is_wp_error( $directoryOrgs ) ) {     
              	foreach ( $directoryOrgs as $directoryOrg ) {
              	  $children = get_term_children($directoryOrg->term_id, 'directory'); // get children
              	  if(sizeof($children)==0)
              	     $peopleByDirectory[$directoryOrg->term_id][$post->ID] = $post; 
              	}
              }
              
            } else {
          ?>
    					<article id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_post' ); ?>>
    
    						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    				
    						<?php
                      echo Sunrise_Directory::display_person_short($post->ID);
                ?>
    						
    					</article> <!-- .et_pb_post -->
    			<?php
            }
					endwhile;
					
					if($displayType != 'PagedList') { //display DrillDown format
					  wp_enqueue_script( 'directory-org-shortcode-script', plugins_url( '../assets/js/directory_org_shortcode.js', __FILE__ ), array( 'jquery' ), Sunrise_Directory::VERSION );
					  wp_enqueue_style( 'directory-org-styles', plugins_url( '../assets/css/directory_orgs.css', __FILE__ ), Sunrise_Directory::VERSION );
      			?>
            <ul id="directoryListCustom">
              <?php
                //Load Walker_Directory class
                require_once( plugin_dir_path( __FILE__ ) . '../includes/directoryDisplayWalker.php' ); 
                $args = array(
                  'orderby'            => 'term_order',
                  'order'              => 'ASC',
                  'style'              => 'list',
                  'show_count'         => 1,
                  'hide_empty'         => 0,
                  'use_desc_for_title' => 0,
                  'child_of'           => $directory_term_id,
                  'hierarchical'       => true,
                  'title_li'           => null,
                  'show_option_none'   => __('No Directory Orgs'),
                  'number'             => NULL,
                  'echo'               => 1,
                  'depth'              => 0,
                  'current_category'   => 0,
                  'pad_counts'         => 1,
                  'taxonomy'           => 'directory',
                  'expandDirectory'    => $expandDirectory,
                  'peopleByDirectory'   => $peopleByDirectory);
                $args['walker'] = new directoryDisplayWalker; 
                wp_list_categories($args); 
              ?>
            </ul>
            <?php
          } 
          
					if ( function_exists( 'wp_pagenavi' ) )
						wp_pagenavi();
					else
						get_template_part( 'includes/navigation', 'index' );
						
				else :
					get_template_part( 'includes/no-results', 'index' );
				endif;
			?>
			</div> <!-- #left-area -->

			<?php get_sidebar(); ?>
		</div> <!-- #content-area -->
	</div> <!-- .container -->
</div> <!-- #main-content -->

<?php get_footer(); ?>