<?php 
if( ! class_exists('bbFieldTextMultiLine') ) {
  class bbFieldTextMultiLine {

    public function gui($field, bbUI $bbUI, $args = array() )
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


      // needs to be done for all gui field types
      $field_label = stripslashes_deep($field->field_label);

      $text_args = array('class' => 'regular-text');
      
      if($field->field_required) { 
        $required_html = ' <span class="description">' . __('(required)') . "<span>\n";
        $label = esc_html($field_label) . $required_html; 
        $text_args['class'] = $text_args['class'] . ' bbep-required';
      } else {  
        $label = esc_html($field_label);
      }


      // for now since wp may return an array 
      if( ! is_array ($current_value) ) {
        $text_args['value'] = $current_value;  
      }
      
      if( ! $invisible) {
        $html  = '<th>' . $bbUI->label( $field->field_meta_key, $label ) . "</th>\n";
        $html .= '<td>';
        $html .= ($disabled) ? esc_html($current_value) : $bbUI->textarea( $field->field_meta_key, $text_args ); 
        $html .= "</td>\n";
        return $html; 
      } 
    }

  } 
}


?>
