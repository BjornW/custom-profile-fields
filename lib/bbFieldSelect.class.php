<?php 
if( ! class_exists('bbFieldSelect') ) {
  class bbFieldSelect {

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

      // disables the select if needed
      $args = array('disabled' => $disabled);

      // needs to be done for all gui field types
      $field_label = stripslashes_deep($field->field_label);


      // field options is an array which looks like this:
      // $field_options['field_options'] = array('label' => 'value');
      // $field_options['field_selected'] = array('value', 'value2');
      //
      // Depending on the type
      // $field_options['field-not-valid-value'] = array('value', 'value2');
      $f_options = maybe_unserialize($field->field_options);

      if( is_array($f_options) && sizeof($f_options) > 0 ) {
        $options  = array_key_exists('field_options', $f_options)  ? $f_options['field_options'] : array();
        if( isset($current_value) && ! empty($current_value) ){
          $args['selected'] = $current_value;
        } else { 
          $args['selected'] = array_key_exists('field_selected', $f_options) ? $f_options['field_selected'] : -1;
        } 
      }

      // create label 
      if($field->field_required) { 
        $required_html = ' <span class="description">' . __('(required)') . "<span>\n";
        $label = esc_html($field_label) . $required_html; 
        $args['class'] =  'bbep-required';
      } else {  
        $label = esc_html($field_label);
      }
      
      
      if( ! $invisible) {
        $html = '<th>' . $bbUI->label( $field->field_meta_key, $label ) . "</th>\n";
        $html .= '<td>' . $bbUI->select( $field->field_meta_key, $options, $args ) . "</td>\n";
      }
      return $html;  
    }

  } 
}


?>
