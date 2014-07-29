<?php
  //New class to style Directory the way we want (based on Walker_Category)
  class Walker_Directory extends Walker {
  	/**
  	 * @see Walker::$tree_type
  	 * @since 2.1.0
  	 * @var string
  	 */
  	var $tree_type = 'category';
  
  	/**
  	 * @see Walker::$db_fields
  	 * @since 2.1.0
  	 * @todo Decouple this
  	 * @var array
  	 */
  	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');
  
  	/**
  	 * @see Walker::start_lvl()
  	 * @since 2.1.0
  	 *
  	 * @param string $output Passed by reference. Used to append additional content.
  	 * @param int $depth Depth of category. Used for tab indentation.
  	 * @param array $args Will only append content if style argument value is 'list'.
  	 */
  	function start_lvl(&$output, $depth, $args) {
  		if ( 'list' != $args['style'] )
  			return;
  
  		$indent = str_repeat("\t", $depth);
  		$output .= "$indent<ul class='children'>\n";
  	}
  
  	/**
  	 * @see Walker::end_lvl()
  	 * @since 2.1.0
  	 *
  	 * @param string $output Passed by reference. Used to append additional content.
  	 * @param int $depth Depth of category. Used for tab indentation.
  	 * @param array $args Will only append content if style argument value is 'list'.
  	 */
  	function end_lvl(&$output, $depth, $args) {
  		if ( 'list' != $args['style'] )
  			return;
  
  		$indent = str_repeat("\t", $depth);
  		$output .= "$indent</ul>\n";
  	}
  
  	/**
  	 * @see Walker::start_el()
  	 * @since 2.1.0
  	 *
  	 * @param string $output Passed by reference. Used to append additional content.
  	 * @param object $category Category data object.
  	 * @param int $depth Depth of category in reference to parents.
  	 * @param array $args
  	 */
  	function start_el(&$output, $category, $depth, $args) {
  	  $image_path = plugins_url( 'assets/' , dirname(__FILE__) ); //Sunrise_Directory::get_plugin_slug
  		extract($args);
  		$cat_name = esc_attr( $category->name );
  		$cat_name = apply_filters( 'list_cats', $cat_name, $category );
  		$link = '<a href="' . esc_attr( get_term_link($category) ) . '" ';
  		if ( $use_desc_for_title == 0 || empty($category->description) )
  			$link .= 'title="' . esc_attr( sprintf(__( 'View all People in %s' ), $cat_name) ) . '"';
  		else
  			$link .= 'title="' . esc_attr( strip_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
  		$link .= '>';
  		$link .= $cat_name . '</a>';
  
  		if ( !empty($feed_image) || !empty($feed) ) {
  			$link .= ' ';
  
  			if ( empty($feed_image) )
  				$link .= '(';
  
  			$link .= '<a href="' . get_term_feed_link( $category->term_id, $category->taxonomy, $feed_type ) . '"';
  
  			if ( empty($feed) ) {
  				$alt = ' alt="' . sprintf(__( 'Feed for all People in %s' ), $cat_name ) . '"';
  			} else {
  				$title = ' title="' . $feed . '"';
  				$alt = ' alt="' . $feed . '"';
  				$name = $feed;
  				$link .= $title;
  			}
  
  			$link .= '>';
  
  			if ( empty($feed_image) )
  				$link .= $name;
  			else
  				$link .= "<img src='$feed_image'$alt$title" . ' />';
  
  			$link .= '</a>';
  
  			if ( empty($feed_image) )
  				$link .= ')';
  		}
  
  		if ( !empty($show_count) )
  			$link .= ' (' . intval($category->count) . ')';
//   			$link .= ' (' . intval($peopleByDirectory[$category->term_id]) . ')'.' ( ALL ' . intval($category->count) . ')';
//         $link .= ' (' . intval($peopleByDirectory[$category->term_id]) . ')'; 			
  
  		if ( !empty($show_date) )
  			$link .= ' ' . gmdate('Y-m-d', $category->last_update_timestamp);
  
  		if ( 'list' == $args['style'] ) {
  			$output .= "\t<li";
  			$class = 'cat-item cat-item-' . $category->term_id;
  			if ( !empty($current_category) ) {
  				$_current_category = get_term( $current_category, $category->taxonomy );
  				if ( $category->term_id == $current_category )
  					$class .=  ' current-cat';
  				elseif ( $category->term_id == $_current_category->parent )
  					$class .=  ' current-cat-parent';
  			}
  			$output .=  ' class="' . $class . '"';
  			$children = get_term_children( $category->term_id, $category->taxonomy );
  			if(count($children) > 0) {
          $output .= '><a href="#" class="directoryExpand" title="Click to Expand / Collapse"><img class="expandImage" src="'.$image_path.'trans-arrow-circle.png" /></a>&nbsp '.$link;
        } else {
          $output .= '><img class="directoryBottom" src="'.$image_path.'trans-arrow-circle.png" />&nbsp'.$link;
        }
  		} else {
  			$output .= "\t$link<br />\n";
  		}
  	}
  
  	/**
  	 * @see Walker::end_el()
  	 * @since 2.1.0
  	 *
  	 * @param string $output Passed by reference. Used to append additional content.
  	 * @param object $page Not used.
  	 * @param int $depth Depth of category. Not used.
  	 * @param array $args Only uses 'list' for whether should append to output.
  	 */
  	function end_el(&$output, $page, $depth, $args) {
  		if ( 'list' != $args['style'] )
  			return;
  
  		$output .= "</li>\n";
  	}
  
  }