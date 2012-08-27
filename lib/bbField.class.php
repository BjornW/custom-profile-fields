<?php 
if( ! class_exists('bbField') ) {
  abstract class bbField {
    
    public function __construct()
    {

    }  

    abstract public function gui();
  }
}

?>
