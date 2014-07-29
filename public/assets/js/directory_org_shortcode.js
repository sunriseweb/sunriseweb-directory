(function ( $ ) {
	"use strict";

	$(function () {

		$('a.directoryExpand').click(function(e) {
      e.stopPropagation();
      //When click on li check if nested ul and expand just that ul    
      if($(this).parent().has('ul')) {
          $(this).parent().children('ul').slideToggle('fast');
      }
      $(this).children('img:first').toggleClass("collapseImage");
      return false;
    });

	});

}(jQuery));