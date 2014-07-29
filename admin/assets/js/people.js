(function ( $ ) {
	"use strict";

	$(function () {

		$('#directorychecklist li').click(function(e) {
      e.stopPropagation();
      if( e.target.tagName.toUpperCase() !== 'INPUT' ) {
        var cbox = $(this).find('input:checkbox')[0];
        cbox.checked = !cbox.checked;
        //When click on li check if nested ul and expand just that ul    
        if($(this).has('ul')) {
          $(this).children('ul:first').slideToggle('fast');
        }
      }
    });   

	});

}(jQuery));