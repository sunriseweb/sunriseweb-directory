<?php
//New class to retrieve and style Directory output the way we want (based on Walker_Category)
//for PDF creation
class directoryFullPDFWalker extends Walker {
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
		//Get all posts that use this term		
// 		$myquery['tax_query']=array(
//         'taxonomy' => $category->taxonomy,
//         'terms' => array($category->term_id),
//         'posts_per_page' => -1
//     );
//     query_posts($args);
//     if (have_posts()) : while (have_posts()) : the_post();
//       //Create array of posts by directoryOrg
//       $directoryOrgs = get_the_terms($post->ID, 'directory');
//       if ( $directoryOrgs && ! is_wp_error( $directoryOrgs ) ) { 
//   
//       	foreach ( $directoryOrgs as $directoryOrg ) {
//       	  $peopleByDirectory[$directoryOrg->term_id][$post->ID] = $post;
// //                           print_r(get_metadata('directory',$directoryOrg->term_id))."<br />"; 
//       	}
//       }   
//     endwhile;
//     endif;
		
		$numPeopleInOrg = count($peopleByDirectory[$category->term_id]);
// 		print_r($peopleByDirectory[$category->term_id]);
		//Parse description for current directory org to get directory settings it may contains (e.g. topHTML, DisplayType, etc.) 
    $lines = explode("|".chr(13).chr(10),$category->description);
    foreach($lines as $lineindex => $settingString) {
      $thisSetting = explode(":=",$settingString);
      if(trim($thisSetting[0]) != "") {
        $directorySettings[$thisSetting[0]] = $thisSetting[1];
      }
    }    
    
		//Create list of people in directory			
    if($numPeopleInOrg > 0) {
        if ( 'list' == $args['style'] ) {
          if($numPeopleInOrg == 1) {
            foreach($peopleByDirectory[$category->term_id] as $personID => $personInfo) {
              $personMeta = get_person_metadata($personID);
//               'plainPersonName',
//                   'designationStatus', 
//                   'streetaddress',
//                   'addressline2',
//                   'cityProvince',
//                   'postalcode',
//                   'personEmail',
//                   'personPhone'
//               
//               $personDisplay .= $personMeta[$infoName].' ';      
              foreach($infoToDisplay as $displayOrder => $infoName) {
                if(trim($personMeta[$infoName]) != "") {
                  $personDisplay .= $personMeta[$infoName].', ';
                }
              }
          	}
          	$categoryPeople = substr($personDisplay,0,strlen($personDisplay)-2).'<br />';
          } else {
            $categoryPeople = '<ul class="peopleInDirectoryOrg">';
            foreach($peopleByDirectory[$category->term_id] as $personID => $personInfo) {
              $personMeta = get_person_metadata($personID);      
              foreach($infoToDisplay as $displayOrder => $infoName) {
                if(trim($personMeta[$infoName]) != "") {
                  $personDisplay .= $personMeta[$infoName].' ';
                }
              }
              $categoryPeople .= '<li>'.$personDisplay.'</li>';
          	}
            $categoryPeople .= '</ul>';
          } 
        } else {

          $categoryPeople = '';
          foreach($peopleByDirectory[$category->term_id] as $personID => $personInfo) {
           $personMeta = get_person_metadata($personID);
           //Get the thumbnail if one exists
            $thumbNail = '';
            if ( has_post_thumbnail($personID)) {
              $thumbNailTitle = esc_attr( $personMeta['plainFormalName'] );
        			$attr = array( 'class'	=> 'person-thumb', 'title' => $thumbNailTitle);
              $thumbNail .= get_the_post_thumbnail( $personID, array(100,100), $attr );
            }
            $personDisplay = '<div class="pdfPersonDiv">';
            $personDisplay .= $thumbNail;        
            foreach($infoToDisplay as $displayOrder => $infoName) {
              if(trim($personMeta[$infoName]) != "") {
                $personDisplay .= $personMeta[$infoName].'<br />';
              }
            }
            $personDisplay .= '</div>';
           
        	  $categoryPeople .= $personDisplay;
        	}
        }
    } elseif (trim($directorySettings['topHTML']) == "") {
      $vacantString = "Vacant";
    }
           	
    if(count($children) == 0) {
        $link = '<br /><span class="directoryTitle" style="font-size: 14px; font-weight: bold;">'.$cat_name.'</span> - '.$vacantString;
    } else {
        $link = '<br /><span class="directoryName" style="font-weight: bold; font-style: underline; font-size: 14px;">'.$cat_name.'</span>';  	
    }
    
    if(trim($directorySettings['topHTML']) != "" ) {
      $link .= "<p>".trim($directorySettings['topHTML'])."</p>";
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
