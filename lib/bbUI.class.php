<?php 
/**
 * bbUI 
 * 
 * @package bbUI 
 * @version 1.0
 * @copyright  Bjorn Wijers <burobjorn@burobjorn.nl>
 * @author Bjorn Wijers <burobjorn@burobjorn.nl> 
 * @license GPL 
 */
if( ! class_exists('bbUI') ) {
  class bbUI {

    function __construct()
    {
      
    }


    /**
     * Create a simple html select UI element 
     *
     * @param string $name 
     * @param array $values  
     * @param mixed $args 
     * @access protected
     * @return string html
     */
    public function select($name, $values = array(), $args = array() )
    {
      $defaults = array(
        'id'    => $name,
        'class' => $name,
        'disabled' => false,
        'multiselect' => null, /*expect a number */
        'none_selected_text' => '',
        'none_selected_value' => -1,
        'selected' => -1,
        'echo' => false
      );

      if( is_array($args) && is_array($values) && sizeof($values) > 0 ) { 
        $params = array_merge($defaults, $args);
        extract($params);
        $multiple = is_null($multiselect) ? '' : 'multiple size=' . (int) $multiselect; 
        $disable = $disabled ? 'disabled="disabled"' : '';
        $id = $this->_has_id($id);
        $class = $this->_has_class($class);
        $html = "<select name='$name' $id $multiple $disable $class>";
      
        // only add a non-selectable option if the text is set
        if( ! empty($none_selected_text) ) {
          $select_none = ($selected == $non_selected_value ) ? "selected='selected'" : '';
          $html .= "<option $select_none value='$non_selected_value'>$none_selected_text</option>\n";
        }     

        // process the possible options
        foreach($values as $key => $val) {
          $select = ($val == $selected && ! is_null($selected) ) ? "selected='selected'" : '';
          $html .= "<option value='$val' $select>$key</option>\n";
        }
        
        $html .= "</select>\n"; 
     
        if($echo) {
          echo $html;    
        } else { 
          return $html; 
        }
      } else {
        return false;
      }
    }


    /**
     * Create a simple html label field 
     * 
     * @param string id of the labeled element 
     * @param mixed string text for the label (may include html) 
     * @access public
     * @return string
     */
    public function label($for, $label, $args = array() )
    {
      if( is_array($args) ) {
        $defaults = array('echo'=> false);
        $params = array_merge($defaults, $args);
        extract($params);
        $html = "<label for='$for'>" . $label . "</label>\n";
        if($echo) {
          echo $html; 
        } else {
          return $html; 
        }
      }
    }


    /**
     * Create a simple html text input UI element 
     * 
     * @param mixed $name 
     * @param array $args 
     * @access public
     * @return void
     */
    public function input_text($name, $args = array()) 
    {
      $defaults = array(
        'id' => $name,
        'class' => $name,
        'value' => '',
        'maxlength' => 255,
        'disabled' => false,
        'size' => null,
        'echo' => false  
      );
      if( is_array($args) ) {
        $params = array_merge($defaults, $args);
        extract($params);
        $the_size  = ( ! is_null($size) ) ? "size='$size'" : ''; 
        $max_input = ( is_numeric($maxlength) && $maxlength > 0 ) ? "maxlength='$maxlength'" : '';
        $class = $this->_has_class($class);
        $id = $this->_has_id($id);
        $disabled = $this->_is_disabled($disabled);

        $html = "<input type='text' $disabled $the_size name='$name' $id $max_input $class value='$value' />"; 
        if($echo) {
          echo $html; 
        } else {
          return $html;
        }
      } else {
        return false;
      }
    }


    /**
     * Create a simple html textarea UI element 
     * 
     * @param mixed $name 
     * @param array $args 
     * @access public
     * @return void
     */
    public function textarea($name, $args = array()) 
    {
      $defaults = array(
        'id' => $name,
        'class' => $name,
        'value' => '',
        'cols' => 10,
        'rows' => 10,
        'echo' => false  
      );
      if( is_array($args) ) {
        $params = array_merge($defaults, $args);
        extract($params);
        $class = $this->_has_class($class);
        $id = $this->_has_id($id);
        $html = "<textarea rows=\"$rows\" cols=\"$cols\" name='$name' $id $class>$value</textarea>"; 
        
        if($echo) {
          echo $html; 
        } else {
          return $html;
        }
      } else {
        return false;
      }
    }

    /**
     * Create a simple html text input UI element 
     * 
     * @param mixed $name 
     * @param array $args 
     * @access public
     * @return void
     */
    public function input_hidden($name, $args = array()) 
    {
      $defaults = array(
        'id' => null,
        'class' => $name,
        'value' => '',
        'size' => null,
        'echo' => false  
      );
      if( is_array($args) ) {
        $params = array_merge($defaults, $args);
        extract($params);
        $the_size  = ( ! is_null($size) ) ? "size='$size'" : '';
        $id = $this->_has_id($id);
        $class = $this->_has_class($class); 
        $value = $this->_has_value($value);

        $html = "<input type='hidden' $the_size name='$name' $id $class $value />"; 
        if($echo) {
          echo $html; 
        } else {
          return $html;
        }
      } else {
        return false;
      }
    }
    
    public function radio($name, $args = array() )
    {
      if( is_array($args) ){
        $defaults = array(
          'id' => $name,
          'class' => $name,
          'name' => $name,
          'value' => '',
          'disabled' => false,
          'selected' => false,
        );
        $params = array_merge($defaults, $args);
        extract($params);
        
        $class = $this->_has_class($class);
        $id = $this->_has_id($id);
        $checked = ($selected == true) ? 'checked="checked"' : '';
        $disabled = $this->_is_disabled($disabled);
        
        $html = "<input type=\"radio\" $class $disabled value=\"$value\" $id $checked name=\"$name\" />"; 
        if($echo) {
          echo $html; 
        } else {
          return $html;
        }
      } else {
        return false;
      }
    }
    
    public function checkbox($name, $args = array() )
    {
      if( is_array($args) ){
        $defaults = array(
          'id' => $name,
          'class' => $name,
          'name' => $name,
          'value' => '',
          'selected' => false,
          'disabled' => false,
          'echo' => false
        );
        $params = array_merge($defaults, $args);
        extract($params);
        $class = $this->_has_class($class);
        $id = $this->_has_id($id);
        $checked = ($selected == true) ? 'checked="checked"' : '';
        $disabled = $this->_is_disabled($disabled);
        
        $html = "<input type=\"checkbox\" $disabled $class value=\"$value\" $id $checked name=\"$name\" />"; 
        if($echo) {
          echo $html; 
        } else {
          return $html;
        }
      } else {
        return false;
      }
    }

    public function input_file($name, $args = array() )
    {
      $defaults = array(
        'max_file_size' => $this->_return_bytes( ini_get('upload_max_filesize') ),
        'class' => $name,
        'id' => $name,
        'echo' => false
      );

      $params = array_merge($defaults, $args);
      extract($params);

      $the_id = $this->_has_id($id);  
      $the_class = $this->_has_class($class); 
      $html = $this->input_hidden('MAX_FILE_SIZE', array('value' => $max_file_size) );
      $html .= "\n";
      $html .= "<input type=\"file\" $the_class $the_id name=\"$name\" />";
      
      if($echo) {
        echo $html; 
      } else {
        return $html;
      }
    }

    public function input_button($name, $args = array() )
    {
      $defaults = array(
        'class' => $name,
        'id' => $name,
        'echo' => false,
        'value' =>'Upload'
      );

      $params = array_merge($defaults, $args);
      extract($params);

      $the_id = $this->_has_id($id);  
      $the_class = $this->_has_class($class); 
      $html .= "<input type=\"button\" $the_class\" $the_id\" name=\"$name\" value=\"$value\" />";
      
      if($echo) {
        echo $html; 
      } else {
        return $html;
      }
    }


    public function input_submit($name, $args = array() )
    {
      $defaults = array(
        'class' => $name,
        'id' => $name,
        'echo' => false,
        'value' =>'Upload'
      );

      $params = array_merge($defaults, $args);
      extract($params);

      $id = $this->_has_id($id);  
      $class = $this->_has_class($class); 

      $html = "<input type=\"submit\"  $class $id value=\"$value\" />";
      
      if($echo) {
        echo $html; 
      } else {
        return $html;
      }
    }


    /**
     * _has_class returns a class attribute when the parameter
     * supplied is a string and not empty or a null value 
     * 
     * 
     * @param string $val 
     * @access private
     * @return string html class attribute or nothing
     */
    private function _has_class($val) 
    {
      return ( is_null($val) || empty($val) ) ? '' : "class='$val'"; 
    }

    /**
     * _has_id returns an id attribute when the parameter
     * supplied is a string and not empty or a null value 
     * 
     * 
     * @param string $val 
     * @access private
     * @return string html id attribute or nothing
     */
    private function _has_id($val) 
    {
      return ( is_null($val) || empty($val) ) ? '' : "id='$val'"; 
    }

    private function _is_disabled($val) 
    {
      return ($val) ? 'disabled="disabled"' : ''; 
    }

    private function _has_value($val) 
    {
      return (is_null($val) || empty($val) ) ? '' : "value='$val'"; 
    }
    
    private function _return_bytes($val) 
    {
      $val = trim($val);
      $last = strtolower($val[strlen($val)-1]);
      switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
      case 'g':
        $val *= 1024;
      case 'm':
        $val *= 1024;
      case 'k':
        $val *= 1024;
      }
      return $val;
    }


  }
}



?>
