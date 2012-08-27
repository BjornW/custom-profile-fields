jQuery(document).ready(function() {

jQuery('.bbep-image-uploader-btn').click(function() {
 //formfield = jQuery(this).prev('input').attr('name');
 // set a flag for later use
 jQuery(this).prev('input').addClass('bbep-selected-image-field');
 tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
 return false;
});

window.send_to_editor = function(html) {
 img_url = jQuery('img',html).attr('src');
 jQuery('.bbep-selected-image-field').prev('p').hide();

 jQuery('.bbep-selected-image-field').before('<p><img class="bbep-new-image" src="' +img_url +'" /></p>');
 jQuery('.bbep-selected-image-field').val(img_url);
 jQuery('.bbep-selected-image-field').removeClass('bbep-selected-image-field');
 tb_remove();
}

});

