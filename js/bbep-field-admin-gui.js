jQuery(document).ready(function(){
  
  // check profile for required fields  
  jQuery(":button.bbep_field_add_option_gui").click(function(){
    // retrieve the current html set for label/value of a field option
    var kloon = jQuery('tr#bbep-first-field-values').clone().removeAttr('id');

    // remove any previous values which have been cloned as well
    // and don't make the newly added values mandatory. Only 1 set of
    // label/value pairs is mandatory
    kloon.find(':text').val('').removeClass('bbep-required');
    kloon.find('th').html(''); 
    jQuery('.bbep-field-values').last().after(kloon);
    
  });

  






});
