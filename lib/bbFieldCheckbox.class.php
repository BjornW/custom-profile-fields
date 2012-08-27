<?php 
if( ! class_exists('bbFieldCheckbox') ) {
  class bbFieldCheckbox {

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

      // check if there are any values selected already
      // we presume that multiple values are seperated by comma's 
      $selected_values = array();
      if( ! empty($current_value) && strpos($current_value, ',') > 0 ) {
        $selected_values = explode(',', $current_value);
      } else {
        $selected_values[] = $current_value;
      }

      // create field label 
      // needs to be done for all gui field types
      $field_label = stripslashes_deep($field->field_label);
      
      if($field->field_required) { 
        $required_html = ' <span class="description">' . __('(required)') . "<span>\n";
        $label = esc_html($field_label) . $required_html; 
        $args['class'] =  'bbep-required';
      } else {  
        $label = esc_html($field_label);
      }



      $f_options = maybe_unserialize($field->field_options);
      if( ! $invisible) {
        if( is_array($f_options) && array_key_exists('field_options', $f_options) ) {
          if( is_array($f_options['field_options']) && sizeof($f_options) > 0 ) {
            $checkboxes = '<th>' . $bbUI->label( $field->field_meta_key, $label ) . "</th>\n";
            $checkboxes .= '<td>';
            $checkboxes_name = $field->field_meta_key . '[]'; 
            foreach($f_options['field_options'] as $label => $value ) {
              $selected = false;
              // select the values chosen by a user, or if the field was not yet part of the 
              // user's profile we will show the field with the default values using usermeta table 
              if(is_array($selected_values) && sizeof($selected_values) > 0) {
                $selected = in_array($value, $selected_values) ? true : false;
              } elseif( $show_defaults && array_key_exists('field_selected', $f_options) && is_array($f_options['field_selected']) && sizeof($f_options['field_selected']) > 0 ) {
                $selected = in_array($value, $f_options['field_selected']) ? true : false;
              }
              $id = $value . '-id';
              $class = ($field->field_required) ? 'bbep-required' : '';
              $checkboxes .=  $bbUI->checkbox($checkboxes_name, $args = array('value' => $value,
                'id' => $id,
                'class' => $class,
                'selected' => $selected,
                'disabled' => $disabled) 
              ); 
              $checkboxes .= ' '. $bbUI->label($id, $label); 
              $checkboxes .=  "<br />";
            }
            $checkboxes .= "</td>\n";
          }
        }
      }
      return $checkboxes;  
    }

  } 
}


?>
