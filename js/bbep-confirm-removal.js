jQuery(document).ready(function(){
  jQuery(".bbep_do_remove").click(function(event){
    return confirm(bbep_js.bbep_confirm_removal_msg);
  });
  return false;
});
