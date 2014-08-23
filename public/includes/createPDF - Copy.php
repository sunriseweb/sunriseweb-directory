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
             
            if (have_posts()) : while (have_posts()) : the_post();
              
              $postid = get_the_id();
                    
              //See if 8 people have been outputted and add new page if so.              
              if($peopleCount % 8 == 0 && $peopleCount != 0 && $displayType == 'PagedList') {
                $mpdf->WriteHTML('<pagebreak>');
              }
              $peopleCount++;
              
              if($displayType == 'PagedList' || $numChildren == 0) {
              
                //Create HTML for output to PDF - 14 people per 8.5 x 11 sheet in portrait
                              
//                 $thisPDF = get_person_metadata($post->ID);

                //Get the thumbnail if one exists
//                 $thumbNail = '';
//                 if ( has_post_thumbnail($post->ID)) {
//                   $thumbNailTitle = esc_attr( $thisPDF['plainFormalName'] );
//             			$attr = array( 'class'	=> 'person-thumb', 'title' => $thumbNailTitle);
//                   $thumbNail .= get_the_post_thumbnail( $post->ID, array(100,100), $attr );
//                 }
                
                $mpdf->WriteHTML('<div class="pdfPersonDiv">');
                $mpdf->WriteHTML($this->display_person_long($postid));
//                 $htmlOutput .= $thumbNail;
//                 foreach($infoToDisplay as $displayOrder => $infoName) {
//                   if(trim($thisPDF[$infoName]) != "") {
//                     $htmlOutput .= $thisPDF[$infoName].'<br />';
//                   }
//                 }
                $mpdf->WriteHTML('<div class="clear"></div>');
                $mpdf->WriteHTML($thisPDF['personDirectoryOrgs']);
                $mpdf->WriteHTML('</div>');
                
              } elseif($displayType != 'PagedList' && $numChildren > 0) {

                  //Create array of posts by directoryOrg
                  $directoryOrgs = get_the_terms($postid, 'directory');
                  if ( $directoryOrgs && ! is_wp_error( $directoryOrgs ) ) {                
                  	foreach ( $directoryOrgs as $directoryOrg ) {
                  	  $peopleByDirectory[$directoryOrg->term_id][$postid] = $postid; 
                  	}
                  }    
                
              }        
                    
            endwhile; endif;
            
            rewind_posts();
            
            if($displayType != 'PagedList' && $numChildren > 0) {
                      
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
                  'peopleByDirectory'  => $peopleByDirectory
                  );
                $args['walker'] = new directoryPDFWalker; 
                $mpdf->WriteHTML(wp_list_categories($args));
                $mpdf->WriteHTML('</ul>');

            }
            

// $htmlOutput = $hhtml.$htmlOutput;             
// $mpdf->WriteHTML($htmlOutput);

$mpdf->Output( $filepath.$fileprefix.$filename.$filesuffix,'F');
// $content = $mpdf->Output('','S');
// $filepathPDF = get_stylesheet_directory_uri().'/directoryExports/'.$fileprefix."export.pdf";
// $fhpdf = fopen($filepathPDF,'xb') or die("Error opening file for write.");
// fwrite($fhpdf, $content);
// fclose($fhpdf);
// $filesuffix = '.pdf';
// echo "<p>".do_shortcode('[filedownload file="'.$fileURL.$filesuffix.'" type="application/text"]Download Exported PDF File[/filedownload]')."</p>";
// echo '<p class="exportResults"><a target="_new" href="'.$fileURL.$filesuffix.'" type="application/pdf">View / Download Exported PDF File</a></p>';
// exit;
?>
