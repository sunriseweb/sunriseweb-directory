<?php
//New class to retrieve and style Directory output the way we want (based on Walker_Category)
//for PDF creation
class directoryPDFWalker extends Walker {
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
		extract($args);
// 		echo "<br />";
		$cat_name = esc_attr( $category->name );
		$cat_name = apply_filters( 'list_cats', $cat_name, $category );
		$children = get_term_children($category->term_id, $category->taxonomy);
		$numPeopleInOrg = count($peopleByDirectory[$category->term_id]);
		
		$additional_text = get_field('additional_text', 'directory_'.$category->term_id);
		
		//Create list of people in directory			
    if($numPeopleInOrg > 0) {
        if ( 'list' == $args['style'] ) {
          if($numPeopleInOrg == 1) {
          
            foreach($peopleByDirectory[$category->term_id] as $personID => $personInfo) {
              $categoryPeople .= Sunrise_Directory::display_person_short($personID).'<br />';
          	}
          	
          } else {
            $categoryPeople = '<ul class="peopleInDirectoryOrg">';
            foreach($peopleByDirectory[$category->term_id] as $personID => $personInfo) {
              $categoryPeople .= '<li>'.Sunrise_Directory::display_person_long($personID).'</li>';
          	}
            $categoryPeople .= '</ul>';
          } 
        } else {
        
//           $qargs = array(
//             'numberposts'   => -1, // get all posts.
//           	'post_type' => 'people',
//           	'tax_query' => array(
//           		array(
//           			'taxonomy' => 'directory',
//           			'field'    => 'id',
//           			'terms'    => array($category->term_id),
//           		),
//           	'fields'        => 'ids', // Only get post IDs
//           	),
//           );
//           $people_query = new WP_Query( $qargs );
          
//           if ( $people_query->have_posts() ) {
//             while ( $people_query->have_posts() ) {
//               $people_query->the_post();
//               
//               $categoryPeople = '<div class="pdfPersonDiv">';
//               $categoryPeople .= Sunrise_Directory::display_person_long( get_the_id() );
//               $categoryPeople .= '</div>';           
//           	  $categoryPeople .= '<br />';              
//               
//             } //end of while have_posts
//           } //end of if have_posts
          
          foreach( Sunrise_Directory::get_post_ids( 'people', 'directory', $category->term_id ) as $person) {
            $categoryPeople = '<div class="pdfPersonDiv">';
            $categoryPeople .= Sunrise_Directory::display_person_long($person-ID);
            $categoryPeople .= '</div>';           
        	  $categoryPeople .= '<br />';
        	}
        	
//         	wp_reset_postdata();
        	
        }
    } elseif(trim($additional_text) == "") {
      $vacantString = "Vacant";
    }
           	
    if(count($children) == 0) {
        $link = '<br /><span class="directoryTitle" style="font-size: 14px; font-weight: bold;">'.$cat_name.'</span> - '.$vacantString;
    } else {
          $link = '<br /><span class="directoryName" style="font-weight: bold; font-style: underline; font-size: 14px;">'.$cat_name.'</span>';  	
//       	$link = '<div class="directoryName"><a href="' . esc_attr( get_term_link($category) ) . '" ';
//     		if ( $use_desc_for_title == 0 || empty($category->description) )
//     			$link .= 'title="' . esc_attr( sprintf(__( 'View all People in %s' ), $cat_name) ) . '"';
//     		else
//     			$link .= 'title="' . esc_attr( strip_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
//     		$link .= '>';
//     		$link .= $cat_name.'</a></div>';
    }
    
    if(trim($additional_text) != "" ) {
      $link .= "<p>".$additional_text."</p>";
    }
    
    
		if ( !empty($show_date) )
			$link .= ' ' . gmdate('Y-m-d', $category->last_update_timestamp);

		if ( 'list' == $args['style'] ) {
		  $output .= "\t<li";
// 			$output .= "\t<div";
			$class = 'cat-item cat-item-' . $category->term_id;
			if ( !empty($current_category) ) {
				$_current_category = get_term( $current_category, $category->taxonomy );
				if ( $category->term_id == $current_category )
					$class .=  ' current-cat';
				elseif ( $category->term_id == $_current_category->parent )
					$class .=  ' current-cat-parent';
			}
			$output .=  ' class="' . $class . '"';
			
			$output .= '>'.$link.' '.$categoryPeople;
// 			$output .= '<div>'.$link.'</div>'.$categoryPeople;

		} else {
			$output .= "\t$link<br />\n";
			$output .= $categoryPeople;
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
//     $output .= "</div>\n";    
	}

}
?>
