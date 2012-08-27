jQuery(document).ready(function(){
  
  // check profile for required fields  
  jQuery("#your-profile").submit(function(){
    
    // check all input text fields which have a class bbep-required
    // and if no value has been given set a class to warn the user
    jQuery("input[type=text].bbep-required").each(function(index){
      jQuery(this).removeClass('bbep-warning');
      if( jQuery(this).val() == '' || jQuery(this).val() == undefined ) {
        jQuery(this).addClass('bbep-warning');
      }
    });

    // check all textarea fields which have a class bbep-required
    // and if no value has been given set a class to warn the user
    jQuery("textarea.bbep-required").each(function(index){
      jQuery(this).removeClass('bbep-warning');
      if( jQuery(this).val() == '' || jQuery(this).val() == undefined ) {
        jQuery(this).addClass('bbep-warning');
      }
    });

    // check all required image file upload fields which have a class bbep-required
    // and if no value has been given set a class to warn the user
    jQuery("input[type=hidden].bbep-required").each(function(index){
      if( jQuery(this).hasClass('bbep-image-uploader') ) {
        jQuery(this).parents('td').removeClass('bbep-warning');
        if( jQuery(this).val() == '' || jQuery(this).val() == undefined ) {
          jQuery(this).parents('td').addClass('bbep-warning');
        }
      }
    });
   
   function hasAtLeastOneChecked(name) {
     if( jQuery('input[name="' + name + '"]:checked').length > 0) {
       return true;
     }
     return false;
   }

    // retrieve al the names of the checkboxes which need at least on value 
    // checked. So first we gather the name of a group of checkboxes. Then we 
    // check all the checkboxes with the same name. If one is checked no worries 
    // if none is checked we need to set a class on the td wherein the checkboxes reside
    // to signal the user
    var boxes = new Array();  
    jQuery("input[type=checkbox].bbep-required, input[type=radio].bbep-required").each(function(index) {
      var name = jQuery(this).attr('name');
      if( jQuery.inArray(name, boxes) == -1 ) {
        jQuery(this).parents('td').removeClass('bbep-warning');
        boxes.push(name);
        if( ! hasAtLeastOneChecked(name) ) {
          jQuery(this).parents('td').addClass('bbep-warning');
        }
      }
    });
    



    /*jQuery("input[type=checkbox].bbep-required").each(function(index){
      jQuery(this).removeClass('bbep-warning');
      if( jQuery(this).val() == '' || jQuery(this).val == undefined ) {
        jQuery(this).addClass('bbep-warning');
      }
    });*/



    if( jQuery("*").hasClass('bbep-warning') ) { 
      alert(bbep_js.bbep_required_msg); 
      return false;
    } else {
      return true;
    }
    return false;
  });

  jQuery('.bbep-date').datepicker({ 
    yearRange: '1910:2030',
    minDate: null,
    changeYear: true,
    changeMonth: true,
    constrainInput: true,
    dateFormat: 'yy/mm/dd'  
  });




});
