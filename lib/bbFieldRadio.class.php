<?php 
if( ! class_exists('bbFieldRadio') ) {
  class bbFieldRadio {

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
            $radioboxes = '<th>' . $bbUI->label( $field->field_meta_key, $label ) . "</th>\n";
            $radioboxes .= '<td>';
            $radioboxes_name = $field->field_meta_key . '[]'; 
            foreach($f_options['field_options'] as $label => $value ) {
              $selected = false;
              // select the value chosen by a user, or if the field was not yet part of the 
              // user's profile we will show the field with the default value using usermeta table 
              if( ! $show_defaults && $current_value ==  $value) {
                $selected = true;
              } elseif($show_defaults) {
                $selected = in_array($value, $f_options['field_selected']) ? true : false;
              }
              $class = ($field->field_required) ? 'bbep-required' : '';
              $id = $value . '-id';
              $radioboxes .=  $bbUI->radio($radioboxes_name, $args = array('value' => $value,
                'id' => $id,
                'selected' => $selected,
                'disabled' => $disabled,
                'class' => $class
              )); 
              $radioboxes .= ' ' . $bbUI->label($id, $label);
              $radioboxes .= "<br />";
            }
            $radioboxes .= "</td>\n";
          }
        }
      }

      return $radioboxes;  
    }

  } 
}


?>
