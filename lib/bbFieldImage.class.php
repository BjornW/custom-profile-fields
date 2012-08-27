<?php 
if( ! class_exists('bbFieldImage') ) {
  class bbFieldImage {

    public function gui($field, bbUI $bbUI, $args = array())
    {
      // make sure we get an array of arguments
      if( ! is_array($args) ) { 
        return false; 
      }

      $defaults = array(
        'current_value' => '',
        'invisible' => false,
        'disabled' => false
      );

      
      $params = array_merge($defaults, $args);
      extract($params);

      $class = ($field->field_required) ? 'bbep-required' : '';
      $btn_args  = array( 'class' => 'bbep-image-uploader-btn', 'value' => __('Add an image') );
      $file_args = array( 'class' => "bbep-image-uploader $class", 'value' => $current_value ); 

      // needs to be done for all gui field types
      $field_label = stripslashes_deep($field->field_label);

      if($field->field_required) { 
        $required_html = ' <span class="description">' . __('(required)') . "<span>\n";
        $label = esc_html($field_label) . $required_html; 
        $args['class'] =  'bbep-required';
      } else {  
        $label = esc_html($field_label);
      }

      // create gui
      if( ! $invisible ) {
        $html = '<th>' . $bbUI->label( $field->field_meta_key, $label) . "</th>\n";
        $html .= '<td>';
        if( ! empty($current_value) ) {
          $html .= "<p><img src='$current_value' class='bbep-current-image' /><br />\n";
          $html .= ($disabled || $field->field_required)  ? '' : $bbUI->checkbox( $field->field_meta_key .'-remove', array( 'value' => 'yes') );
          $html .= ($disabled || $field->field_required)  ? '' : $bbUI->label( $field->field_meta_key .'-remove', esc_html(__('Remove')) );
          $html .= "</p>\n";  
        }
        if( ! $disabled) {
          $btn_name = $field->field_meta_key . '_btn'; 
          $html .= $bbUI->input_hidden( $field->field_meta_key, $file_args); 
          $html .= $bbUI->input_button( $btn_name, $btn_args ) . "</td>\n";
        }
      }
      return $html;  
    }



  } 
}


?>
