<?php
//New class to retrieve and style Directory output the way we want (based on Walker_Category)
class directoryDisplayWalker extends Walker {
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
		//Check if directory orgs should be collapsed or expanded by default
	  if( $args['expandDirectory'] == "Y" || $args['expandDirectory'] == "" || $args['expandDirectory'] == 1) {
      $expandClass = 'directoryListExpand';
    } else {
      $expandClass = 'directoryListCollapse';
    }
		$output .= "$indent<ul class='children ".$expandClass."'>\n";
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
// 		echo "<br />";
		$cat_name = esc_attr( $category->name );
		$cat_name = apply_filters( 'list_cats', $cat_name, $category );
		$children = get_term_children($category->term_id, $category->taxonomy);
		$numPeopleInOrg = count($peopleByDirectory[$category->term_id]);
		
    //Parse description for current directory org to get directory settings it may contains (e.g. topHTML, DisplayType, etc.)
//     $directorySettings = get_fields('directory_'.$directory_term_id); //results in array with $field_name => $value - see http://www.advancedcustomfields.com/resources/functions/get_fields/ 
//     $lines = explode("|".chr(13).chr(10),$category->description);
//     foreach($lines as $lineindex => $settingString) {
//       $thisSetting = explode(":=",$settingString);
//       if(trim($thisSetting[0]) != "") {
//         $directorySettings[$thisSetting[0]] = $thisSetting[1];
//       }
//     }    
    
		//Create list of people in directory			
    if($numPeopleInOrg > 0) {
    
      $categoryPeople = '<ul class="peopleInDirectoryOrg">';
      foreach($peopleByDirectory[$category->term_id] as $personID => $personInfo) {
       
       $personMeta = Sunrise_Directory::display_person_short($personID);
       $personDisplay = '<a href="/' . $personInfo->post_name . '">' . $personMeta['fullname'] .'<br />';
       $personDisplay .= $personMeta['email'] .', '. $personMeta['phone'] .'<br />';
       $personDisplay .= $personMeta['address'].'</a>';
    	 $categoryPeople .= '<li>'.$personDisplay.'</li>';
    	}
    	$categoryPeople .= '</ul>';

    } elseif (trim($directorySettings['additional_text']) == "") {
      $vacantString = " - Vacant";
    }
           	
    if(count($children) == 0 ) {
      $link = '<span class="directoryName">'.$cat_name.'</span>'.$vacantString;
    } else {  	
      	$link = '<a href="' . esc_attr( get_term_link($category) ) . '" ';
    		if ( $use_desc_for_title == 0 || empty($category->description) )
    			$link .= 'title="' . esc_attr( sprintf(__( 'View all People in %s' ), $cat_name) ) . '"';
    		else
    			$link .= 'title="' . esc_attr( strip_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
    		$link .= '>';
    		$link .= '<span class="directoryName">'.$cat_name.'</span></a>';
    }
    
    if(trim($directorySettings['additional_text']) != "" ) {
      $link .= "<p>".trim($directorySettings['additional_text'])."</p>";
    }
    
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
			
			
			if(count($children) > 0) {
			  //Check if directory orgs should be collapsed or expanded by default
			  if( $expandDirectory == "Y" || $expandDirectory == "") {
          $expandClass = 'expandImage collapseImage';
        } else {
          $expandClass = 'expandImage';
        }
        $output .= '><a href="#" class="directoryExpand" title="Click to Expand / Collapse"><img class="'.$expandClass.'" src="'.$image_path.'trans-arrow-circle.png" /></a>&nbsp '.$link;
        $output .= $categoryPeople;

        
      } else {
        $output .= '><img class="directoryBottom" src="'.$image_path.'trans-arrow-circle.png" />&nbsp'.$link;
        $output .= $categoryPeople;
      }
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
	}

}
?>
