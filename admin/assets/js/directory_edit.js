(function ( $ ) {
	"use strict";

	$(function () {
	
    var add_drilldown = function() {
        var parent_trid = new Array();
        var last_parent_trid = '';
        var last_level = 0;
        var first_time = 1;
        
		    // Add drilldown functionality to the directory taxonomy edit admin page wp list table
    		$('.row-title').each(function( i ) {
    		  var title = $(this).text();
    		  var current_level = 0;
          for (var j=0; j<title.length; j += 2) {
              //Check every two characters for "dash space" = "8212 32"
              var get_next_two_characters = title.substring(j,j+2);
              if(get_next_two_characters.charCodeAt(0) == 8212 && get_next_two_characters.charCodeAt(1) == 32 ) { //'—' = 8212 ' ' = 32
                current_level = current_level + 1;  
              }
          }
    		  
    		  // Get table row id
    		  var trobj = $(this).closest('tr');
    		  var trid = trobj.attr('id');
    		  
    		  if(first_time == 1) {
            last_parent_trid = 'parent-'+trid;
            parent_trid[current_level] = 'parent-0';
            first_time = 0;
          }
          
          console.log('trid='+trid+', last_level='+last_level+', current_level='+current_level);
          
          if(last_level != current_level && current_level > last_level) {
            parent_trid[current_level] = last_parent_trid;
            last_parent_trid = 'parent-'+trid;
            last_level = current_level;  
          } else if(last_level != current_level && current_level < last_level) {
            last_parent_trid = 'parent-'+trid;                           
            parent_trid[last_level] = last_parent_trid;
            last_level = current_level; 
          } else if(current_level == last_level){
            last_parent_trid = 'parent-'+trid;
          }
    		  
    		  // Show all top-level taxonomy terms
    		  if(current_level == 0) { 
    		    trobj.show();
    		    trobj.addClass('parent-0');
    		  } else {
    		    trobj.addClass(parent_trid[current_level]+'-'+current_level);
    		    trobj.addClass('level'+current_level);
    		    for(var level=(current_level-1); level>=1; level--) {
              trobj.addClass(parent_trid[level]+'-child');
            }    
          }
    		    
  		    //Append span to title for use in
          var toggle = $('<button class="directory-toggle" level="'+current_level+'">+</button>');
          toggle.click(function(e) {
            e.stopPropagation(); 
            if($(this).hasClass('expanded')) {
              //Hide all chidren
              $('.parent-'+trid+'-child').hide();
              //Remove expanded class from all buttons below
//               $('.parent-'+trid+'-child button').removeClass('expanded');              
              $("[class*='parent-"+trid+"'] button.directory-toggle").removeClass('expanded');
                  
            }
            var thislevel = $(this).attr('level')*1+1;
            $('.parent-'+trid+"-"+thislevel).toggle();
            
            $(this).toggleClass('expanded');
            if($(this).hasClass('expanded')) {
              $(this).html('&ndash;');  
            } else {
              $(this).html('+');
            }
            
            return false; 
          }); 
    		  $(this).parent().prepend(toggle);
    		  
        });
    }
    
    add_drilldown();
    
    $('#the-list tr').each(function( i ) {
      var thisid = $(this).attr('id');
      if($("[class*='parent-"+thisid+"']").length == 0) {
        //Hide toggle button
        $('#'+thisid+' button.directory-toggle').hide();      
      }
         
    });

	});

}(jQuery));