<?php
$thisTitleFilter = trim($_GET['personTitleFirstChar']);
$thisTitleLongFilter = "";
if(trim($thisTitleFilter) != "") {
  $thisTitleLongFilter = "<h1>Whose Last Name Starts With</h1><h1><b>$thisTitleFilter</b></h1>";
  $thisTitleFilter = " - $thisTitleFilter";
}

$blogtime = date("g:ia, D M d, Y",strtotime(get_date_from_gmt(date("Y-m-d H:i:s"))));

include( plugin_dir_path( __FILE__ ) . 'MPDF53/mpdf.php' );

$mpdf=new mPDF('c'); 

$mpdf->SetDisplayMode('fullpage');

// LOAD a stylesheet
$stylesheet = file_get_contents( plugin_dir_path( __FILE__ ) . '../assets/css/stylePDF.css' );
$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
$ucc_crest = wp_get_attachment_image_src( 14820, 'full' );

$hhtml = '  <div style="text-align: center;">
              <h1><a href="'.site_url().'">'.get_bloginfo("name").'</a></h1>
              <a href="'.site_url().'"><img src="'.$ucc_crest[0].'"></a>            
            <h1>Directory Report</h1>
            <h1>'.$blogtime.'</h1>
            <h1><b>'.$thisName.'</b></h1>
            '.$thisTitleLongFilter.'            
            </div>
            <pagebreak>
            <htmlpageheader name="myHTMLHeaderOdd" style="display:none">
            <table width="100%" style="border-bottom: 1px solid black; vertical-align: bottom;">
              <tr>
                <td style="text-align: left;"><b>'.$thisName.$thisTitleFilter.'</b>  ('.$blogtime.')</td>
                <td style="text-align: right;"><b>Page&nbsp;{PAGENO}&nbsp;</b></td>  
              </tr>
            </table>
            </htmlpageheader>
            <htmlpagefooter name="myHTMLFooterOdd" style="display:none">
            <table width="100%" style="border-top: 1px solid black; vertical-align: bottom;">
              <tr>
                <td style="text-align: left;"><b><a href="'.site_url().'">'.get_bloginfo("name").'</a></b></td>
                <td style="text-align: right;"><b>Page&nbsp;{PAGENO}&nbsp;</b></td>  
              </tr>
            </table>
            </htmlpagefooter>
            <sethtmlpageheader name="myHTMLHeaderOdd" page="O" value="on" show-this-page="1" />
            <sethtmlpagefooter name="myHTMLFooterOdd" page="O" value="on" show-this-page="1" />
            ';

$mpdf->WriteHTML($hhtml);
            
$peopleCount = 0;
$thispage = '';

      if($displayType == 'PagedList' || $numChildren == 0) {
      
            $peopleByDirectory = Sunrise_Directory::get_post_ids( 'people', 'directory', $directory_term_id );
//             print_r($peopleByDirectory);
            $totalPeople = count($peopleByDirectory);
            foreach( $peopleByDirectory as $el => $personid ) {
                    
              //See if 8 people have been outputted and add new page if so.              
              if($peopleCount % 8 == 0 && $peopleCount != 0 && $displayType == 'PagedList') {
                $mpdf->WriteHTML($thispage);
                $thispage = '';
                if($peopleCount < $totalPeople)
//                   $mpdf->WriteHTML("peopleCount=$peopleCount, totalPeople=$totalPeople".'<pagebreak>');
                  $mpdf->WriteHTML('<pagebreak>');
              }
              $peopleCount++;
                            
              //Create HTML for output to PDF - 14 people per 8.5 x 11 sheet in portrait
              $thispage .= '<div class="pdfPersonDiv">';
              $thispage .= Sunrise_Directory::display_person_long($personid, true, array(100,100));
              //add in directory orgs
              $thispage .= '</div>';

            }
            
            $mpdf->WriteHTML($thispage);
            
            wp_reset_postdata();
            
      } elseif($displayType != 'PagedList' && $numChildren > 0) {
                      
                //Load Walker_Directory class
                require_once( plugin_dir_path( __FILE__ ) . '../includes/directoryPDFWalker.php' );
                  
                $mpdf->WriteHTML('<ul id="directoryListCustom">');
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
                  'echo'               => 0,
                  'depth'              => 0,
                  'current_category'   => 0,
                  'pad_counts'         => 1,
                  'taxonomy'           => 'directory',
//                   'peopleByDirectory'  => $peopleByDirectory
                  );
                $args['walker'] = new directoryPDFWalker; 
                $mpdf->WriteHTML(wp_list_categories($args));
                $mpdf->WriteHTML('</ul>');

      } //end of if not PagedList
      
            
$mpdf->Output( $filepath.$fileprefix.$filename.$filesuffix,'F');