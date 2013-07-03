<?php
/*******************************************************************************
Plugin Name: Custom Profile Fields
Plugin URI: http://www.burobjorn.nl
Description: Extend the default WordPress user profiles with custom fields
Author: Bjorn Wijers <burobjorn at burobjorn dot nl>
Version: 0.7
Author URI: http://www.burobjorn.nl
*******************************************************************************/

/*  Copyright 2010-2012


Custom Profile Fields is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Custom Profile Fields is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if ( ! class_exists('CustomProfileFields')) {
  class CustomProfileFields {

    /**
     * @var string The options string name for this plugin
    */
    var $options_name = 'bbep_options';

    /**
     * @var string $localization_domain Domain used for localization
    */
    var $localization_domain = "bbep";

    /**
     * @var string $plugin_url The path to this plugin
    */
    var $plugin_url = '';

    /**
     * @var string $plugin_path The path to this plugin
    */
    var $plugin_path = '';

    /**
     * @var array $options Stores the options for this plugin
    */
    var $options = array();


    /**
     * @var wpdb database object
     */
    var $wpdb = null;

    /**
     * @var database table names
     */
    var $db = array();


    /**
     * PHP 4 Compatible Constructor
    */
    function bbExtendProfile(){ $this->__construct(); }

    /**
     * PHP 5 Constructor
    */
    function __construct( $activate = true )
    {
      // UI class
      require_once('lib/bbUI.class.php');
      $this->bbUI = new bbUI();

      // database connection setup
      global $wpdb;
      $this->wpdb = $wpdb;
      // database table
      $this->db['table_fields'] = $this->wpdb->base_prefix . 'bbExtendProfile_fields';

      // language setup
      $locale = get_locale();
      $mo     = dirname(__FILE__) . "/languages/" . $this->localization_domain . "-".$locale.".mo";
      load_textdomain($this->localization_domain, $mo);

      // 'constants' setup
      $this->plugin_url  = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
      $this->plugin_path = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';


      if($activate) {
        $this->activate();
      }

    }


    /**
     * Sets the options, connects WordPress hooks
     * and install the database
     *
     * @access public
     * @return void
     */
    function activate()
    {
      // prepare the options
      $this->get_options();
      // set the WordPress hooks
      $this->wp_hooks();
    }



    /**
     * Adds actions to callbacks
     *
     * @access public
     * @return void
     */
    function wp_hooks()
    {
      // add settings
      add_action( 'admin_menu', array(&$this,'admin_menu_link') );

      // remove the default personal options from the user profiles
      add_action( 'personal_options', array(&$this, 'hide_personal_options') );

      add_filter('user_contactmethods',array(&$this, 'hide_profile_contact_fields') );

      // show the custom fields in the user profiles
      add_action( 'show_user_profile', array($this, 'add_fields_to_profile') );
      add_action( 'edit_user_profile', array($this, 'add_fields_to_profile') );

      // make sure the user profile allows file uploads
      add_action ( 'user_edit_form_tag', array(&$this, 'make_form_multipart') );

      // save the custom field data from the user profiles
      add_action( 'profile_update', array($this, 'save_profile_fields'), 10, 2);

      // add js
      add_action( 'admin_enqueue_scripts', array(&$this, 'admin_js') );
      add_action( 'admin_print_styles', array(&$this, 'admin_css') );

      // add errors, if any, to the user profile so it will be shown to the user
      // using the default WordPress error handling
      add_action( 'user_profile_update_errors', array(&$this, 'validate_profile_fields'), 10, 3);
    }


    /**
     * Retrieves the plugin options from the database.
     * @return array
    */
    function get_options()
    {
      $field_type_prepend = 'bbField';
      // don't forget to set up the default options
      if ( ! $the_options = get_option( $this->options_name) ) {
        $the_options = array(
          'bbep_version' => 0.7,
          'bbep_hide_personal_options' => false,
          'bbep_hide_contact_fields' => false,
          'bbep_remove_data' => false,
          'bbep_extended_fields_title' => __('Extra Fields', $this->localization_domain),
          'bbep_field_types' => array(
            __('Single-line', $this->localization_domain)                => $field_type_prepend . 'TextSingleLine',
            __('Multi-line', $this->localization_domain)                 => $field_type_prepend . 'TextMultiLine',
            //__('WYSISWYG Multi-line', $this->localization_domain)        => $field_type_prepend . 'RichMultiLine',
            __('Date', $this->localization_domain)                       => $field_type_prepend . 'Date',
            __('Image', $this->localization_domain)                      => $field_type_prepend . 'Image',
            //__('Audio', $this->localization_domain)                      => $field_type_prepend . 'Audio',
            //__('Video', $this->localization_domain)                      => $field_type_prepend . 'Video',
            //__('File', $this->localization_domain)                       => $field_type_prepend . 'File',
            //__('Location', $this->localization_domain)                   => $field_type_prepend . 'Location',
            __('Single-choice (radio)', $this->localization_domain)      => $field_type_prepend . 'Radio',
            __('Multiple-choice (checkbox)', $this->localization_domain) => $field_type_prepend . 'Checkbox',
            __('Dropdown', $this->localization_domain)                   => $field_type_prepend . 'Select',
            //__('Multiselect Dropdown', $this->localization_domain)       => $field_type_prepend . 'MultiSelect',
          )
        );
        update_option($this->options_name, $the_options);
      }
      $this->options = $the_options;
    }


    /**
     * Makes a form allow file uploads by
     * adding the form encoding attribute
     *
     * Called by user_edit_form_tag action
     *
     * @access public
     * @return void
     */
    function make_form_multipart()
    {
      echo " enctype='multipart/form-data'";
    }


    /**
     * Saves the admin options to the database.
     * @return bool true on success
    */
    function save_admin_options()
    {
      return update_option($this->options_name, $this->options);
    }

    /**
     * @desc Adds the options subpanel
    */
    function admin_menu_link()
    {
      // If you change this from add_options_page, MAKE SURE you change the filter_plugin_actions function (below) to
      // reflect the page filename (ie - options-general.php) of the page your plugin is under!
      add_options_page('Custom Profile Fields', 'Custom Profile Fields', 'edit_plugins', basename(__FILE__), array(&$this,'admin_options_page'));
      add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
    }

    /**
     * @desc Adds the Settings link to the plugin activate/deactivate page
    */
    function filter_plugin_actions($links, $file)
    {
      // If your plugin is under a different top-level menu than
      // Settiongs (IE - you changed the function above to something other than add_options_page)
      // Then you're going to want to change options-general.php below to the name of your top-level page
      $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
      array_unshift( $links, $settings_link ); // before other links
      return $links;
    }

    /**
     * Add custom javascript scripts from our plugin
     * to the WordPress admin interface
     *
     */
    function admin_js($hook_suffix)
    {
      // only enqueu our javascript files on the profile page and on the plugin's settings page
      if( ( defined('IS_PROFILE_PAGE') ) || ( isset($_GET['page']) && $_GET['page'] == basename(__FILE__) ) ) {
        // add confirmation to plugin settings to warn for removal of fields
        wp_enqueue_script('bbep-confirm-removal', $this->plugin_url . '/js/bbep-confirm-removal.js', array('jquery'), '1.0.0');
        wp_localize_script('bbep-confirm-removal', 'bbep_js' ,$this->localize_js());

        // add validators based on classes for clientside validation
        wp_enqueue_script('bbep-field-validators', $this->plugin_url . '/js/bbep-field-validators.js', array('jquery'), '1.0.0');
        wp_localize_script('bbep-field-validators', 'bbep_js' ,$this->localize_js());

        // add media uploader
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_register_script('bbep-field-uploader', $this->plugin_url .'/js/bbep-field-uploader.js', array('jquery','media-upload','thickbox') );
        wp_enqueue_script('bbep-field-uploader');

        // adding plugin admin js
        wp_enqueue_script('bbep-field-admin-gui', $this->plugin_url . '/js/bbep-field-admin-gui.js', array('jquery'), '1.0.0');

        // adding jQuery datepicker
        // Needs a cleaner check for which version of WP
        global $wp_version;
        if ( version_compare( $wp_version, '3.0.1', '<=' ) ) {
        wp_enqueue_script('date-picker', $this->plugin_url . '/vendor/datePicker/js/jquery-ui-datepicker.min.js', array('jquery','jquery-ui-core'));
        } elseif( version_compare( $wp_version, '3.3', '>=') ) {
          wp_enqueue_script('jquery-ui-datepicker',false, array('jquery','jquery-ui-core'));
        }
      }
    }


    /**
     * Adds plugin specific styles to the plugin admin
     * and profile
     *
     * @todo make sure it is only included when needed
     *
     * @access public
     * @return void
     */
    function admin_css()
    {
      wp_enqueue_style( 'bbep-style', $this->plugin_url . 'css/bbep-style.css', array(), '1.0.0', 'all');
      wp_enqueue_style('thickbox');
      // adding jQuery style
      wp_enqueue_style('date-picker-css', $this->plugin_url . 'vendor/datePicker/css/ui-lightness/jquery-ui.css');
    }


    /**
     * Localize javascript
     *
     * @access public
     * @return array
     */
    function localize_js()
    {
      return array(
        'bbep_confirm_removal_msg' => __('Are you sure you want to remove this field including any associated user data? There is no undo...', $this->localization_domain),
        'bbep_required_msg' => __(
          'You forgot to fill in some required fields. These are marked red. Please correct your mistake and try again', $this->localization_domain
        )
      );
    }


    /**
     *
     * Adds settings/options page
     * decides which gui should be shown & process user input
     */
    function admin_options_page()
    {
      if( isset($_POST['bbep_action']) ) {
        switch($_POST['bbep_action']) {

          // save the plugin options
          case 'options_save':
            $this->options['bbep_hide_personal_options']  = ($_POST['bbep_hide_personal_options'] == 'on') ? true : false;
            $this->options['bbep_hide_contact_fields']    = ($_POST['bbep_hide_contact_fields'] == 'on')   ? true : false;
            $this->options['bbep_remove_data']            = ($_POST['bbep_remove_data'] == 'on')           ? true : false;
            $this->options['bbep_extended_fields_title']  = $_POST['bbep_extended_fields_title'];

            if( $this->save_admin_options() ) {
              $args = array('msg' => __('Succes! Saved options!', $this->localization_domain) );
            } else {
              $args = array('msg' => __('Failure! Could not save options!', $this->localization_domain) );
            }
            $this->admin_start_gui($args);
            break;

          // user choses to add a new field
          case 'new_field':
            // make sure the field has a label, which is unique and the field type is valid
            if( empty($_POST['bbep_field_label']) ) {
              $this->admin_start_gui( array(
                'msg' => __("Can't add field. Field label is empty. A field label is mandatory",$this->localization_domain)
              ) );
            } elseif( false === $this->is_field_unique($this->create_field_meta_key($_POST['bbep_field_label']) ) ) {
              $this->admin_start_gui( array(
                'msg' => __("Can't add field. Field label is already in use. Use a different field label",$this->localization_domain)
              ) );
            } elseif( false == $this->is_field_type_valid($_POST['bbep_field_type']) ) {
              $this->admin_start_gui( array(
                'msg' => __("Can't add field. Field type unknown. Select other field type",$this->localization_domain)
              ) );
            } else {
              if( ($key = $this->add_field($_POST) ) !== false ) {
                $field_data = $this->get_field($key);
                $this->admin_field_options_gui( array('field_data' => $field_data) );
              } else {
                $this->admin_start_gui( array(
                  'msg' => __("Can't add field. Database issues. Contact administrator",$this->localization_domain)
                ) );
              }
            }
            break;

          // remove an existing field, but this will not touch a user profile
          case 'remove_field':
            if( $this->remove_field($_POST['bbep_field_meta_key']) ) {
              $this->admin_start_gui( array(
                'msg' => __("Field removed!",$this->localization_domain)
              ) );
            } else {
              $this->admin_start_gui( array(
                'msg' => __("Could not remove field!",$this->localization_domain)
              ) );
            }
            break;

          // retrieve an existing field and show a gui to edit it's settings
          case 'edit_field':
            $field_data = $this->get_field($_POST['bbep_field_meta_key']);
            if( is_array($field_data) && sizeof($field_data) > 0 ) {
              $this->admin_field_options_gui( array('field_data' => $field_data) );
            } else {
              $this->admin_start_gui( array(
                'msg' => __("Can't edit field. Field does not exist.",$this->localization_domain)
              ) );
            }
            break;

          // update an editted field
          case 'update_field':
            $name_has_changed = false;
            $field_data = $this->get_field( $_POST['bbep_field_meta_key'] );
            // make sure the field has a label, which is unique and if the label has been changed make sure
            // any profiles using the field under its old field label will be updated as well. Depending on the
            // field's type it may contain more required options.
            if( empty($_POST['bbep_field_label']) ) {
              $this->admin_field_options_gui( array(
                'msg' => __("Can't change field. Field name is empty. A field name is mandatory",$this->localization_domain),
                'field_data' => $field_data
              ) );
              // if the name has been altered and the new name is not unique fail with an error message
            } elseif( ($this->create_field_meta_key($_POST['bbep_field_label']) != $_POST['bbep_field_meta_key']) &&
              (false === $this->is_field_unique($this->create_field_meta_key($_POST['bbep_field_label']) ) ) ) {
                $this->admin_field_options_gui( array(
                  'msg' => __("Can't change field. Field label already in use. Use a different field label",$this->localization_domain),
                  'field_data' => $field_data
                ) );
            } elseif( false === $this->field_type_requirements($_POST) ) {
              $this->admin_field_options_gui( array(
                'field_data' => $field_data,
                'msg' => __("Can't change field'. Field type requires at least one field option (label/value) to be set",$this->localization_domain)
                )
              );
            } else {
              if( ($key = $this->update_field($_POST, $_POST['bbep_field_meta_key']) ) !== false ) {
                if($this->create_field_meta_key($_POST['bbep_field_label']) != $_POST['bbep_field_meta_key']) {
                  $this->change_usermeta($_POST['bbep_field_meta_key'], $key);
                }
                $field_data = $this->get_field($key);
                $this->admin_field_options_gui( array(
                  'field_data' => $field_data,
                  'msg' => __("Updated field successfully!",$this->localization_domain)
                  )
                );
              } else {
                $this->admin_field_options_gui( array(
                  'field_data' => $field_data,
                  'msg' => __("Field could not be updated. Database issues. Contact administrator",$this->localization_domain)
                  )
                );
              }
            }
            break;
          case 'main_menu':
            $this->admin_start_gui();
            break;
          default:
            $this->admin_start_gui();
            break;

        }
      } else {
        $this->admin_start_gui();
      }
    }

    function field_type_requirements($post)
    {
      switch($post['bbep_field_type']) {
        case 'bbFieldSelect':
          return $this->process_field_options($post['bbep_field_options_labels'], $post['bbep_field_options_values']);
          break;
        case 'bbFieldCheckbox':
          return $this->process_field_options($post['bbep_field_options_labels'], $post['bbep_field_options_values'], $post['bbep_field_options_selected']);
        break;
      }
    }



    function process_field_options($labels = array(), $values = array(), $selected_values = array() )
    {
      if( (is_array($labels) && sizeof($labels) > 0) && is_array($values) && sizeof($values) > 0 && sizeof($labels) == sizeof($values) ) {
        $field_options = array_combine($labels, $values);
        $field_data = array('field_options' => $field_options);
        if( is_array($selected_values) && sizeof($selected_values) > 0 ) {
          $field_data['field_selected'] = $selected_values;
        }
        return $field_data;
      } else {
        return false;
      }
    }




    /**
     * Renders the default gui
     * of the plugin's admin interface
     *
     * @param array $args
     * @access public
     * @return void
     */
    function admin_start_gui( $args = array() )
    {
      $html = '';

      if( is_array($args) && array_key_exists('msg', $args) && ! empty($args['msg']) ) {
        $html .= '<div class="updated"><p>' . $args['msg'] . '</p></div>';
      }

      $html .= "<div class=\"wrap\">\n";
      $html .= __("<h2>bbExtendProfile Settings</h2>", $this->localization_domain);

      // Add new field GUI
      $html .= __("<h2>Add new field</h2>", $this->localization_domain);
      $html .= __("<p>Keep in mind, that you cannot change a field's type after creating it. <br />
        All other field attributes can be changed, though.</p>", $this->localization_domain);
      $html .= "<form method=\"post\" action=\"\" id=\"bbep_new_field\">";
      $html .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"5\" class=\"form-table\">\n";

      // field label
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>" . $this->bbUI->label("bbep_field_label", __('Field label:', $this->localization_domain) ) . "</th>\n";
      $html .= "\t<td>" . $this->bbUI->input_text("bbep_field_label") . "</td>\n";
      $html .= "</tr>\n";

      // field type
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>" . $this->bbUI->label("bbep_field_type", __('Field type:', $this->localization_domain) ) . "</th>\n";
      $html .= "\t<td>" . $this->bbUI->select("bbep_field_type", $this->options['bbep_field_types']) . "</td>\n";
      $html .= "</tr>\n";

      $html .= "<tr>\n";
      $html .= "\t<td>" . $this->bbUI->input_hidden("bbep_action", array('value' => 'new_field') ) . "</td>\n";
      $html .= "\t<th colspan='2'>";
      $html .= "<input type=\"submit\" name=\"bbep_new_field\" value=\"" . __('Add field', $this->localization_domain) . "\"/>";
      $html .= "</th>\n";
      $html .= "</tr>\n";
      $html .= "</table>\n";
      $html .= "</form>\n";

      // Available fields GUI
      $html .= __("<h2>Available fields</h2>", $this->localization_domain);

      $fields = $this->get_all_fields();
      if( is_array($fields) && sizeof($fields) > 0 ) {
        $html .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"5\" class=\"widefat\">\n";
        // headers
        $html .= "<tr valign=\"top\">";
        $html .= "\t<th>" . __('Field Label', $this->localization_domain) . "</th>\n";
        $html .= "\t<th>" . __('Field Meta Key', $this->localization_domain) . "</th>\n";
        $html .= "\t<th>" . __('Field Type', $this->localization_domain) . "</th>\n";
        $html .= "\t<th>" . __('Active Field', $this->localization_domain) . "</th>\n";
        $html .= "\t<th>" . __('Required Field', $this->localization_domain) . "</th>\n";
        $html .= "\t<th>" . __('Field Order', $this->localization_domain) . "</th>\n";
        $html .= "\t<th>" . __('Used', $this->localization_domain) . "</th>\n";
        $html .= "\t<th colspan=\"2\">" . __('Actions', $this->localization_domain) . "</th>\n";
        $html .= "</tr>\n";
        foreach($fields as $f) {
          if( is_object($f) ) {
            $active = ($f->field_active)    ? __('Yes', $this->localization_domain) : __('No', $this->localization_domain);
            $require = ($f->field_required) ? __('Yes', $this->localization_domain) : __('No', $this->localization_domain);

            $html .= "<tr valign=\"top\">";
            $html .= "\t<td>" . esc_html(stripslashes_deep($f->field_label) ) . "</td>\n";
            $html .= "\t<td>" . esc_html(stripslashes_deep($f->field_meta_key) ) . "</td>\n";
            $html .= "\t<td>" . array_search($f->field_type, $this->options['bbep_field_types'])  . "</td>\n";
            $html .= "\t<td>" . $active . "</td>\n";
            $html .= "\t<td>" . $require . "</td>\n";
            $html .= "\t<td>" . $f->field_order . "</td>\n";
            $html .= "\t<td>" . $this->used_in_nr_profiles($f->field_meta_key) . "</td>\n";
            $html .= $this->action_form($f->field_meta_key, 'edit');
            $html .= $this->action_form($f->field_meta_key, 'remove');
            $html .= "</tr>\n";
          }
        }
        $html .= "</table>\n";
      } else {
        $html .= __('<p>There are no fields available. Have you tried adding a new field?</p>', $this->localization_domain);
      }

      $html .= __("<h2>Advanced options</h2>", $this->localization_domain);
      $html .= "<form method=\"post\" action=\"\" id=\"bbep_options\">";
      $html .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"5\" class=\"form-table\">\n";

      // remove database
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>" . $this->bbUI->label("bbep_remove_data", __('Remove data on plugin delete:', $this->localization_domain) ) . "</th>\n";
      $html .= "\t<td>" . $this->bbUI->checkbox("bbep_remove_data", array('selected' => $this->options['bbep_remove_data']) ) . "</td>\n";
      $html .= "</tr>\n";

      // remove contact fields from a profile
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th><label for=\"bbep_hide_contact_fields\">";
      $html .= __('Hide contact info (AIM, Yahoo IM & Jabber / Google Talk) in profiles', $this->localization_domain);
      $html .= "</label></th>\n";

      $bbep_checked = ($this->options['bbep_hide_contact_fields'] == true) ? 'checked="checked"' : '';

      $html .= "\t<td><input type=\"checkbox\" id=\"bbep_hide_contact_fields\" $bbep_checked name=\"bbep_hide_contact_fields\" /></td>\n";
      $html .= "</tr>\n";

      // remove personal options from a profile
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th><label for=\"bbep_hide_personal_options\">";
      $html .= __('Hide Personal Options in profiles', $this->localization_domain);
      $html .= "</label></th>\n";

      $bbep_checked = ($this->options['bbep_hide_personal_options'] == true) ? 'checked="checked"' : '';

      $html .= "\t<td><input type=\"checkbox\" id=\"bbep_hide_personal_options\" $bbep_checked name=\"bbep_hide_personal_options\" /></td>\n";
      $html .= "</tr>\n";

      // Extended Profile Fields Section Name
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>";
      $html .= $this->bbUI->label("bbep_extended_fields_title", __('Extended Fields section title:', $this->localization_domain) );
      $html .= "</th>\n";
      $html .= "\t<td>";
      $html .= $this->bbUI->input_text("bbep_extended_fields_title", array('value' => $this->options['bbep_extended_fields_title']));
      $html .= "</td>\n";
      $html .= "</tr>\n";

      // Save
      $html .= "<tr>\n";
      $html .= "\t<td>" . $this->bbUI->input_hidden("bbep_action", array('value' => 'options_save') ) . "</td>\n";
      $html .= "\t<th colspan='2'><input type=\"submit\" name=\"bbep_save\" value=\"" . __('Save advanced options', $this->localization_domain) . "\"/></th>\n";
      $html .= "</tr>\n";
      $html .= "</table>\n";
      $html .= "</form>\n";
      $html .= "</div>\n";

      echo $html;
    }


    /**
     * Adds action buttons to a form
     * used by the available fields actions
     *
     * @param mixed $field_meta_key
     * @param string $action
     * @access public
     * @return void
     */
    function action_form($field_meta_key, $action = 'edit')
    {
      $label = ucwords($action);
      $html = "\t<td>\n";
      $html .= "<form  method=\"post\" action=\"\">";
      $html .= $this->bbUI->input_hidden("bbep_field_meta_key", array('value' => $field_meta_key) ) . "\n";
      $html .= $this->bbUI->input_hidden("bbep_action", array('value' => "{$action}_field") ) . "\n";

      $html .= "<input class=\"bbep_do_{$action}\" type=\"submit\" name=\"bbep_{$action}_field\" value=\"";
      $html .= __($label, $this->localization_domain) . "\"/>";

      $html .= "</form>\n";
      $html .= "</td>\n";
      return $html;
    }



    /**
     * Renders update gui for a field
     *
     * @param array $args
     * @access public
     * @return void
     */
    function admin_field_options_gui( $args = array() )
    {
      // initialize field values
      $field_label = $html = '';
      $field_order = $field_required = $field_activate = 0;
      $field_not_visible_for_roles = $field_not_editable_by_roles = $field_options = array();


      if( is_array($args) ) {
        if( array_key_exists('msg', $args) && ! empty($args['msg']) ) {
          $html .= '<div class="updated"><p>' . $args['msg'] . '</p></div>';
        }

        if( array_key_exists('field_data', $args) && is_array($args['field_data']) ) {
          extract( $args['field_data'] );
          $field_label = esc_html( stripslashes_deep($field_label) );
          $field_not_editable_by_roles = empty($field_not_editable_by_roles) ? array() : maybe_unserialize($field_not_editable_by_roles);
          $field_not_visible_for_roles = empty($field_not_visible_for_roles) ? array() : maybe_unserialize($field_not_visible_for_roles);
          $field_options               = empty($field_options)               ? array() : maybe_unserialize($field_options);
        }
      }

      $html .= "<div class=\"wrap\">\n";
      $html .= "<h2>Custom Profile Fields '$field_label' Options</h2>\n";
      $html .= "<form method=\"post\" action=\"\" id=\"bbep_new_field\">";
      $html .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"5\" class=\"form-table\">\n";

      // field label
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>" . $this->bbUI->label("bbep_field_label", __('Field label: ', $this->localization_domain) ) . "</th>\n";
      $html .= "\t<td>" . $this->bbUI->input_hidden("bbep_field_meta_key", array('value' => $field_meta_key)) . "\n";
      $html .= $this->bbUI->input_text("bbep_field_label", array('value' => $field_label)) . "</td>\n";
      $html .= "</tr>\n";

      // field type
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>" . $this->bbUI->label("bbep_field_type", __('Field type: ', $this->localization_domain) ) . "</th>\n";
      $html .= "\t<td>" . array_search($field_type, $this->options['bbep_field_types']);
      $html .= $this->bbUI->input_hidden("bbep_field_type", array('value' => $field_type)) . "</td>\n";
      $html .= "</tr>\n";

      // extra options based on the field type
      $html .= $this->field_type_options_gui($field_type, $field_options);

      // field order
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>" . $this->bbUI->label("bbep_field_order", __('Field order:', $this->localization_domain) ) . "</th>\n";
      $html .= "\t<td>" . $this->bbUI->input_text("bbep_field_order", array('value' => $field_order)) . "</td>\n";
      $html .= "</tr>\n";

      // field active
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>" . $this->bbUI->label("bbep_field_active", __('Active field:', $this->localization_domain) ) . "</th>\n";
      $html .= "\t<td>" . $this->bbUI->checkbox("bbep_field_active", array('selected' => $field_active, 'value' => 1)) . "</td>\n";
      $html .= "</tr>\n";

      // Field rules
      $html .= "</table>\n";
      $html .= "<h2>" . __('Field Rules') . "</h2>\n";
      $html .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"5\" class=\"form-table\">\n";

      // field required
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>" . $this->bbUI->label("bbep_field_required", __('Required field:', $this->localization_domain) ) . "</th>\n";
      $html .= "\t<td>" . $this->bbUI->checkbox("bbep_field_required", array('selected' => $field_required, 'value' => 1) ) . "</td>\n";
      $html .= "</tr>\n";

      // field not editable by users with roles
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>";
      $html .= $this->bbUI->label("bbep_field_not_editable_by_roles",
        __('Users with role(s) are <strong>not</strong> allowed to edit this field:', $this->localization_domain) );
      $html .= "</th>\n";
      $html .= "\t<td>" . $this->show_roles_checkboxes("bbep_field_not_editable_by_roles", $field_not_editable_by_roles) . "</td>\n";
      $html .= "</tr>\n";

      // field invisible for users with roles
      $html .= "<tr valign=\"top\">";
      $html .= "\t<th>";
      $html .= $this->bbUI->label("bbep_field_not_visible_for_roles",
        __('Users with role(s) are <strong>not</strong> allowed to view this field:', $this->localization_domain) );
      $html .= "</th>\n";
      $html .= "\t<td>" . $this->show_roles_checkboxes("bbep_field_not_visible_for_roles", $field_not_visible_for_roles) . "</td>\n";
      $html .= "</tr>\n";

      // FIELD VALIDATION
      //$html .= "</table>\n";
      //$html .= "<h2>" . __('Field Validators') . "</h2>\n";
      //$html .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"5\" class=\"form-table\">\n";

      //$html .= "<tr>\n";
      //$html .= "\t<th>Validator 1</th>\n";
      //$html .= "\t<td>Validator 1 gui</td>\n";
      //$html .= "</tr>\n";

      $html .= "</table>\n";
      $html .= "<p>\n";
      $html .= $this->bbUI->input_hidden("bbep_action", array('value' => 'update_field') ) . "\n";
      $html .= "<input style=\"float: left;\" type=\"submit\" name=\"bbep_new_field\" value=\"" . __('Save field options', $this->localization_domain) . "\"/>";
      $html .= "</form>\n";

      $html .= "<form method=\"post\" action=\"\" id=\"bbep_back_to_menu\">";
      $html .= $this->bbUI->input_hidden("bbep_action", array('value' => 'main_menu') ) . "\n";
      $html .= "<input type=\"submit\" style=\"float: left; margin-left: 20px\" name=\"bbep_back\" value=\"" . __('Cancel, back to main menu', $this->localization_domain) . "\"/>";
      $html .= "</p>\n";
      $html .= "</form>\n";
      $html .= "</div>\n";
      echo $html;
    }




    function field_type_options_gui($field_type, $field_options = array() )
    {
      $html = '';
      if('bbFieldSelect' == $field_type || 'bbFieldMultiSelect' == $field_type || 'bbFieldRadio' == $field_type || 'bbFieldCheckbox' == $field_type) {
        $html .= "<tr valign=\"top\">";
        $html .= "\t<th>" . $this->bbUI->label("bbep_field_options", __('Field values: ', $this->localization_domain) ) . "</th>\n";

        $html .= "\t<td>" . $this->bbUI->label('bbep_field_options_label', __('Option label:', $this->localization_domain)  ) . "</td>\n";
        $html .= "\t<td>" . $this->bbUI->label('bbep_field_options_value', __('Option value:', $this->localization_domain)  ) . "</td>\n";
        if('bbFieldCheckbox' == $field_type) {
          $html .= "\t<td>" . $this->bbUI->label('bbep_field_options_value', __('Option is selected:', $this->localization_domain)  ) . "</td>\n";
        } elseif('bbFieldRadio' == $field_type) {
          $html .= "\t<td>" . $this->bbUI->label('bbep_field_options_value', __('Default option:', $this->localization_domain)  ) . "</td>\n";
        }
          $html .= "</tr>\n";


        // field options is an array which looks like this:
        // $field_options['field_options'] = array('label' => 'value');
        // $field_options['field_selected'] = array('value', 'value2');
        //
        // Depending on the type
        // $field_options['field-not-valid-value'] = array('value', 'value2');
        if( is_array($field_options) && sizeof($field_options) > 0 ) {
          if( array_key_exists('field_options', $field_options) ) {
            $field_labels_values = $field_options['field_options'];
            if( is_array($field_labels_values) ) {
              $counter = 0;
              foreach($field_labels_values as $option_label => $option_value) {
                $id    = ($counter == 0) ? 'id="bbep-first-field-values"' : '';
                $class = ($counter == 0) ? 'class="bbep-required"'        : '';

                $html .= "<tr valign=\"top\" class=\"bbep-field-values\" $id>";
                if( $counter == 0 && ('bbFieldSelect' == $field_type || 'bbFieldMultiSelect' == $field_type) ) {
                  $html .= "\t<th><span class='description'>" . __('Default (selected value)', $this->localization_domain)  ."</span></th>\n";
                } else {
                  $html .= "\t<th></th>\n";
                }
                $html .= "\t<td>";
                $html .=  $this->bbUI->input_text('bbep_field_options_labels[]', array('id' => 'bbep_field_options_label', 'class' => $class, 'value' => $option_label) );
                $html .= "</td>\n";
                $html .= "\t<td>";
                $html .= $this->bbUI->input_text('bbep_field_options_values[]', array('id' => 'bbep_field_options_value', 'class' => $class, 'value' => $option_value) );
                $html .= "</td>\n";
                if('bbFieldCheckbox' == $field_type) {
                  $selected = (array_key_exists('field_selected', $field_options) && in_array($option_value, $field_options['field_selected']) ) ? true : false;
                  $html .= "\t<td>";
                  $html .= $this->bbUI->checkbox('bbep_field_options_selected[]', array('id' => 'bbep_field_options_selected', 'value' => $option_value, 'selected' => $selected) );
                  $html .= "</td>\n";
                } elseif('bbFieldRadio' == $field_type) {
                  $selected = (array_key_exists('field_selected', $field_options) && in_array($option_value, $field_options['field_selected']) ) ? true : false;
                  $html .= "\t<td>";
                  $html .= $this->bbUI->radio('bbep_field_options_selected[]', array('id' => 'bbep_field_options_selected', 'value' => $option_value, 'selected' => $selected) );
                  $html .= "</td>\n";
                }

                // keep track of the amount of options, only the first are mandatory and need a specific id and class
                $counter++;
              }
            }
          }
        } else {

          $html .= "<tr valign=\"top\" class=\"bbep-field-values\" id=\"bbep-first-field-values\">";
          if('bbFieldSelect' == $field_type || 'bbFieldMultiSelect' == $field_type ) {
            $html .= "\t<th><span class='description'>" . __('Default (selected value)', $this->localization_domain)  ."</span></th>\n";
          } else {
            $html .= "\t<th></th>\n";
          }
          $html .= "\t<td>";
          $html .=  $this->bbUI->input_text('bbep_field_options_labels[]', array('id' => 'bbep_field_options_label', 'class' => 'bbep-required') );
          $html .= "</td>\n";
          $html .= "\t<td>";
          $html .= $this->bbUI->input_text('bbep_field_options_values[]', array('id' => 'bbep_field_options_value', 'class' => 'bbep-required') );
          $html .= "</td>\n";
          if('bbFieldCheckbox' == $field_type) {
            $html .= "\t<td>";
            $html .= $this->bbUI->checkbox('bbep_field_options_selected[]', array('id' => 'bbep_field_options_selected', 'value' => $option_value, 'selected' => $selected) );
            $html .= "</td>\n";
          } elseif('bbFieldRadio' == $field_type) {
            $selected = (array_key_exists('field_selected', $field_options) && in_array($option_value, $field_options['field_selected']) ) ? true : false;
            $html .= "\t<td>";
            $html .= $this->bbUI->radio('bbep_field_options_selected[]', array('id' => 'bbep_field_options_selected', 'value' => $option_value, 'selected' => $selected) );
            $html .= "</td>\n";
          }

          $html .= "</tr>\n";
        }

        // add more fields button
        $html .= "<tr valign=\"top\" class=\"field-values-button\">";
        $html .= "\t<th></th>\n";
        $html .= "\t<td>";
        $html .= $this->bbUI->input_button('bbep_field_add_option_gui',
          array('value' => __('Add another value', $this->localization_domain), 'id' => '') ) ;
        $html .= "</td>\n";
        $html .= "</tr>\n";

        if('bbFieldSelect' == $field_type || 'bbFieldMultiSelect' == $field_type) {
         // $html .= "<tr valign=\"top\">";
         // $html .= "\t<th>" . $this->bbUI->label("bbep_field_options",
         //   __('Default field value is <strong>not</strong> a valid choice: ', $this->localization_domain) ) . "</th>\n";
         // $html .= "\t<td>"  . $this->bbUI->checkbox('bbep_field_default_selectable', array('value' => 'no') );
         // $html .= "</tr>\n";
        }
      }
      return $html;
    }






    /**
     * Check if the field label is already defined in either the bbExtendProfile
     * fields table or the wp_usermeta, if it is already defined the given field
     * label is not unique and the function will return boolean false, otherwise it will
     * return boolean true.
     *
     * @todo Query could use some love. Combining them might make more sense
     *
     * @param mixed $field_meta_key
     * @access public
     * @return bool
     */
    function is_field_unique($field_meta_key)
    {
      $q_fields = $this->wpdb->prepare(
        "SELECT field_meta_key
        FROM {$this->db['table_fields']}
        WHERE field_meta_key=%s",
        $field_meta_key);

      $result = $this->wpdb->get_var($q_fields);
      if( ! is_null($result) ) {
        // found something so return false since the field is not unique
        return false;
      } else {
        // check the existing fields in the user meta table
        $result = null;
        $q_usermeta = $this->wpdb->prepare(
          "SELECT meta_key
          FROM {$this->wpdb->usermeta}
          WHERE meta_key=%s",
          $field_meta_key);
        $result = $this->wpdb->get_var($q_usermeta);
        if( ! is_null($result) ) {
          return false;
        }
      }
      // fall tru... field is unique
      return true;
    }


    /**
     * Checks if a given field type is part
     * of the field types options
     *
     * @param mixed $type
     * @access public
     * @return void
     */
    function is_field_type_valid($type)
    {
      $types = $this->options['bbep_field_types'];
      if( is_array($types) ) {
        foreach($types as $k => $v) {
          if($type == $v) {
            return true;
          }
        }
        return false;
      }
    }






    /**
     * Adds a field to the database
     *
     * @param array $fdata
     * @access public
     * @return string field_meta_key || bool false on failure
     */
    function add_field( $post_data = array() )
    {
      if( is_array($post_data) && sizeof($post_data) > 0 ) {

        $fdata = array();
        foreach($post_data as $name => $value) {
          switch($name){
            case 'bbep_field_label':
              $fdata['field_label'] = $value;
              break;
            case 'bbep_field_type':
              $fdata['field_type'] = $value;
              break;
          }
        }

        $field_defaults = array(
          'field_active' => 0,
          'field_required' => 0,
          'field_order' => 0,
          'field_options' => '',
          'field_validators' => '',
          'field_not_visible_for_roles' => '',
          'field_not_editable_by_roles' => '',
        );

        $field_data = array_merge($field_defaults, $fdata);
        extract($field_data);
        $field_meta_key = $this->create_field_meta_key($field_label);
        $q = $this->wpdb->prepare("INSERT INTO {$this->db['table_fields']}
          (field_meta_key,
          field_label,
          field_type,
          field_options,
          field_validators,
          field_not_editable_by_roles,
          field_not_visible_for_roles,
          field_order,
          field_required,
          field_active)
          VALUES(%s, %s, %s, %s, %s, %s, %s, %d, %d, %d)",
          $field_meta_key,
          $field_label,
          $field_type,
          $field_options,
          $field_validators,
          $field_edit_roles,
          $field_visible_roles,
          $field_order,
          $field_required,
          $field_active
        );

        $nr_affected = $this->wpdb->query($q);
        if($nr_affected == 1) {
          return $field_meta_key;
        } else {
          return false;
        }
      }

     //fall thru
     return false;

    }

    /**
     * create_field_meta_key
     * Transforms a field label to a field meta key
     * this is used as an identifier between the plugin's
     * table and WordPress usermeta table. A field meta key
     * may only contain alphanumeric characters and underscores
     *
     * @todo might be enought to use only sanitize_key
     * @param mixed $field_label
     * @access public
     * @return void
     */
    function create_field_meta_key($field_label)
    {
      if( isset($field_label) && ! empty($field_label) ) {
        $f = $field_label;
        $f = trim($f); // remove trailing whitespace
        $f = preg_replace('/\s+/', ' ', $f); // replace multiple spaces with one space
        $f = str_replace(' ', '_', $f); // replace space by underscore
        $f = strtolower($f); // make field label all lowercase
        $f = sanitize_key($f);
        return $f;
      }
     return $field_label;
    }


    /**
     * Updates a field in the database
     *
     * @param array $field_data
     * @access public
     * @return void
     */
    function update_field( $post_data = array(), $current_field_meta_key )
    {

      if( is_array($post_data) ) {

        $field_label    = $post_data['bbep_field_label'];
        $field_meta_key = $this->create_field_meta_key($field_label);
        $field_type     = $post_data['bbep_field_type'];
        $field_required = (isset($post_data['bbep_field_required']) && $post_data['bbep_field_required'] == '1') ? 1 : 0;
        $field_active   = (isset($post_data['bbep_field_active'])   && $post_data['bbep_field_active'] == '1')   ? 1 : 0;
        $field_order    = is_numeric( $post_data['bbep_field_order'] ) ? (int) $post_data['bbep_field_order'] : 0;

        // serialize data
        $field_not_editable_by_roles = isset($post_data['bbep_field_not_editable_by_roles']) ?
          serialize($post_data['bbep_field_not_editable_by_roles']) : '';
        $field_not_visible_for_roles = isset($post_data['bbep_field_not_visible_for_roles']) ?
          serialize($post_data['bbep_field_not_visible_for_roles']) : '';


        $field_options = $this->process_field_options($post_data['bbep_field_options_labels'],
          $post_data['bbep_field_options_values'],
          $post_data['bbep_field_options_selected']
        );
        $field_options = ($field_options === false) ? '' : serialize($field_options);


        $field_validators = ''; // todo: implement this


        $q = $this->wpdb->prepare("UPDATE {$this->db['table_fields']}
          SET field_meta_key=%s,
          field_label=%s,
          field_type=%s,
          field_options=%s,
          field_validators=%s,
          field_not_editable_by_roles=%s,
          field_not_visible_for_roles=%s,
          field_order=%d,
          field_required=%d,
          field_active=%d
          WHERE field_meta_key=%s",
          $field_meta_key,
          $field_label,
          $field_type,
          $field_options,
          $field_validators,
          $field_not_editable_by_roles,
          $field_not_visible_for_roles,
          $field_order,
          $field_required,
          $field_active,
          $current_field_meta_key
        );
        //echo $q;
        $nr_affected = $this->wpdb->query($q);

        if($nr_affected == 1) {
          return $field_meta_key;
        } else {
          return false;
        }
      }
      return false;
    }


    // change the user meta key when a field changes it label
    function change_usermeta($old_meta_key, $new_meta_key)
    {
      $q = $this->wpdb->prepare("
        UPDATE {$this->wpdb->usermeta}
        SET meta_key=%s
        WHERE meta_key=%s", $new_meta_key, $old_meta_key
      );
      $nr_affected = $this->wpdb->query($q);
      return $nr_affected;
    }


    /**
     * remove a field and any associated user data
     *
     *
     * @param mixed $field_meta_key
     * @access public
     * @return void
     */
    function remove_field($field_meta_key)
    {
      $q = $this->wpdb->prepare(
        "DELETE FROM {$this->db['table_fields']}
        WHERE field_meta_key=%s LIMIT 1", $field_meta_key);
      $nr_affected = $this->wpdb->query($q);

      if($nr_affected == 1) {
        $this->remove_user_data($field_meta_key);
        return true;
      } else {
        return false;
      }
    }

    // remove user data based on a field's meta key
    function remove_user_data($field_meta_key)
    {
      $q = $this->wpdb->prepare("
        DELETE FROM {$this->wpdb->usermeta}
        WHERE meta_key=%s", $field_meta_key);
      return $nr_users_affected = $this->wpdb->query($q);
    }



    // retrieve a field's data as an array
    function get_field($field_meta_key)
    {
      $q = $this->wpdb->prepare("
        SELECT * FROM {$this->db['table_fields']}
        WHERE field_meta_key=%s LIMIT 1", $field_meta_key
      );
      return $this->wpdb->get_row($q, ARRAY_A);
    }


    /**
     * Get all available roles
     *
     * @access public
     * @return void
     */
    function get_user_roles()
    {
      $the_roles = array();
      $roles = get_editable_roles();
      //var_dump($roles);
      if( is_array($roles) ) {
        foreach($roles as $role_key => $role_data) {
          if( is_array($role_data) && array_key_exists('name', $role_data) ) {
            $the_roles[$role_key] = $role_data['name'];
          }
        }
      }
      return $the_roles;
    }



    function show_roles_checkboxes( $name, $selected_values = array() )
    {
      $chbx = '';
      $roles = $this->get_user_roles();
      if( is_array($roles) && sizeof($roles) > 0 ) {
        // sort the roles alphabetically
        natcasesort($roles);
        foreach($roles as $role_key => $role_label) {
          $selected =  in_array($role_key, $selected_values) ? true : false;

          $id = $name . '-' . $role_key;
          $value = $role_key;
          $chbx .= $this->bbUI->checkbox($name . '[]',
            array(
              'class' => '',
              'id' => $id ,
              'value' => $value,
              'selected' => $selected
            ) );
          $chbx .= $this->bbUI->label($id, $role_label) . '<br />';
        }
      }
      return $chbx;

    }

    /**
     * Hide the default personal options in a user profile
     *
     * @access public
     * @return void
     */
    function hide_profile_contact_fields($contact_fields)
    {
      if(is_admin() && $this->options['bbep_hide_contact_fields'] == true )  {
        if( is_array($contact_fields) ) {
          unset($contact_fields['aim']);
          unset($contact_fields['jabber']);
          unset($contact_fields['yim']);
          return $contact_fields;
        }
      }
      // fall thru
      return $contact_fields;
    }


    /**
     * Hide the default personal options in a user profile
     *
     * @access public
     * @return void
     */
    function hide_personal_options()
    {
      if(is_admin() && $this->options['bbep_hide_personal_options'] == true ) {
        remove_action("admin_color_scheme_picker", "admin_color_scheme_picker");
        require_once("js/hide-personal-options.js");
      }
    }


    /**
     * Adds custom fields to a profile
     *
     * @access public
     * @return void
     */
    function add_fields_to_profile( $profile_user)
    {
      $fields = $this->get_fields(); // @TODO check for visibility and editable role!
      if(is_array($fields) && sizeof($fields) > 0) {
        $html  = '<h3>' . esc_html($this->options['bbep_extended_fields_title']) . "</h3>\n";
        $html .= wp_nonce_field('bbep-update-profile', $name = 'bbep_wpnonce', $referer = true, $echo = false);
        $html .= "<table class=\"form-table\">\n";
        // the fields
        foreach($fields as $f) {
          $render_args['disabled']      = $this->is_field_disabled_for_user( maybe_unserialize($f->field_not_editable_by_roles) );
          $render_args['invisible']     = $this->is_field_invisible_for_user( maybe_unserialize($f->field_not_visible_for_roles) );
          $render_args['current_value'] = get_user_meta($profile_user->ID, $f->field_meta_key, true);
          $render_args['show_defaults']  = $this->show_field_defaults($f->field_meta_key, $profile_user->ID);
          $html .= '<tr>' . $this->render_field_gui($f, $render_args)  . "</tr>\n";
        }

        $html .= "</table>\n";
        echo $html;
      }
    }

    // checks if the profile fields adhere to the requirements and
    // the per field specified validators called by user_profile_update_errors action
    function validate_profile_fields(&$errors, $update, &$user) {

      $fields = $this->get_fields(); // @TODO check for visibility and editable role!

      if(is_array($fields) && sizeof($fields) > 0) {
        foreach($fields as $f) {
          if( is_object($f) ) {
            if( isset($_POST[$f->field_meta_key]) ) { // make sure this works with checkboxes as well

              // make sure required fields are filled in
              if( ($f->field_required) && empty($_POST[$f->field_meta_key]) ) {
                $errors->add( $_POST[$f->field_meta_key], __('Oops, you seem to have forgotten to fill in the required field: ' . esc_html($f->field_label) ) );
              }




            }
          }
        }
      }
      return $errors;
    }


    /**
     * See if a field has been saved once
     * by checking the existence of the field
     * meta key in the user meta table. If the
     * meta key does not exists assume that the field
     * has not been shown to user before and thus
     * (if any) defaults may be shown. Otherwise
     * if a umeta_id is returned the defaults may not be shown
     * since the meta_value may be intentionally left blank
     *
     * @param mixed $field_meta_key
     * @param mixed $user_id
     * @access public
     * @return bool true if the field may show any defaults, false if not
     */
    function show_field_defaults($field_meta_key, $user_id)
    {
      $q = $this->wpdb->prepare("
        SELECT umeta_id
        FROM {$this->wpdb->usermeta}
        WHERE meta_key=%s
        AND user_id=%d", $field_meta_key, $user_id);

      $result = $this->wpdb->get_var($q);
      return $return = (is_null($result)) ? true : false;
    }



    // proces the fields, if no errors are found
    // @TODO validators, uploads
    function save_profile_fields( $user_id, $old_user_data )
    {
      //var_dump($old_user_data);
      if ( wp_verify_nonce($_POST['bbep_wpnonce'], 'bbep-update-profile') ) {
        $fields = $this->get_fields(); // @TODO check for visibility and editable role!

        if( is_array($fields) && sizeof($fields) > 0) {
          foreach($fields as $f) {
            if( is_object($f) ) {
              if( isset($_POST[$f->field_meta_key]) && 'bbFieldCheckbox' != $f->field_type ) { // make sure this works with checkboxes as well
                if( is_array($_POST[$f->field_meta_key]) ) {
                  $value = $this->wpdb->prepare( implode(',',$_POST[$f->field_meta_key]) );
                } else {
                  $value = $this->wpdb->prepare( $_POST[$f->field_meta_key]);
                }

                // if the field is an image and has the checkbox is ticked off
                // we need to remove the image from the profile. It wil NOT be
                // removed from disk!
                if($f->field_type == 'bbFieldImage') {
                  $remove_selected = $_POST[$f->field_meta_key . '-remove'];
                  $value = ($remove_selected == 'yes') ? '' : $value;
                }
              } elseif('bbFieldCheckbox' == $f->field_type) {
                if( isset($_POST[$f->field_meta_key]) ) {
                 $value = is_array($_POST[$f->field_meta_key]) ?  $this->wpdb->prepare( implode(',',$_POST[$f->field_meta_key]) ) : $this->wpdb->prepare( $_POST[$f->field_meta_key]);
                } elseif( ! $this->is_field_invisible_for_user( maybe_unserialize($f->field_not_visible_for_roles) ) ) {
                  $value = '';
                } else {
                  $value = null; // do not change the value of this field
                }
              }
              if( ! is_null($value) ) {
                update_user_meta($user_id, $f->field_meta_key, $value);
              }
            }
          }
        }
      } else {
      	return $old_user_data;
      }
    }


    // check if a field is invisible for a user with given roles
    // @todo can a user have multiple roles? For now assume they can
    // and if a user has a role which may not see a field don't show the field
    // even if the user has another role which allows them to see the field
    function is_field_invisible_for_user( $field_invisible_for_roles = array() )
    {
      if( ! is_array($field_invisible_for_roles) ) {
        return false;
      }

      foreach($field_invisible_for_roles as $role) {
        if( current_user_can($role) ) {
          return true;
        }
      }
      return false;
    }


    // check if a field is disabled for a user with given roles
    function is_field_disabled_for_user( $field_disabled_for_roles = array(), $user_roles = array() )
    {
      if( ! is_array($field_disabled_for_roles) ) {
        return false;
      }
      // check if one of the roles was linked to the user
      foreach($field_disabled_for_roles as $role) {
        if(current_user_can($role) ) {
          return true;
        }
      }
      return false;
    }



    // expects a field db object
    // checks if the field type is known
    function is_valid_field_type($field)
    {
      $field_types = $this->options['bbep_field_types'];
      if( is_object($field) && is_array($field_types) && sizeof($field_types) > 0) {
        if( in_array($field->field_type, $field_types) ) {
          return true;
        }
      }
      // fall thru
      return false;
    }


    function render_field_gui($field, $args = array())
    {
      if( ! is_array($args) ) {
        return false;
      }
      if( $this->is_valid_field_type($field) ) {
        $class_name = $field->field_type;
        require_once('lib/' . $class_name . '.class.php');
        return call_user_func(array($class_name, 'gui'),$field, $this->bbUI, $args);
        //return $class_name::gui($field, $this->bbUI, $args );
      }
    }


    function get_fields( $args = array() )
    {
      $defaults = array (
        'field_active' => 1,
        'orderby' => 'field_order, field_label',
        'order' => 'asc'
      );

      $params = array_merge($defaults, $args);
      extract($params);

      $q = $this->wpdb->prepare(
        "SELECT * FROM {$this->db['table_fields']}
        WHERE field_active=%d
        ORDER BY $orderby $order",
        $field_active, $orderby, $order
      );
      //echo $q;
      return $this->wpdb->get_results($q);
    }


    // check how many times a field is being used
    function used_in_nr_profiles($field_meta_key)
    {
      $q = $this->wpdb->prepare(
        "SELECT COUNT(meta_key) AS used
        FROM {$this->wpdb->usermeta}
        WHERE meta_key=%s", $field_meta_key
      );
      return $this->wpdb->get_var($q);
    }


    function get_all_fields()
    {
      $q = $this->wpdb->prepare(
        "SELECT * FROM {$this->db['table_fields']}
        ORDER BY field_order, field_label ASC");
      //echo $q;
      return $this->wpdb->get_results($q);
    }


    /**
     * Check if a given table is already installed
     *
     * @access private
     * @param string table name
     * @return bool true on database tables installed
     */
    function _is_installed($table_name)
    {
      $sql = $this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
      $result = $this->wpdb->get_var($sql) == $table_name;
      if( $result == $table_name ) {
        return true;
      }
      // fall thru
      return false;
    }


    /**
     * Creates the necessary database table for the plugin. Also deals with
     * plugin specific database upgrades if needed
     *
     * @access public
     * @return void
     */
    function setup_database()
    {
      $queries = array();

      // dealing with colation and character sets. Derived from /wp-admin/wp-includes/schema.php
      $charset_collate = '';
      if ( ! empty($this->wpdb->charset) ) {
        $charset_collate = "DEFAULT CHARACTER SET " . $this->wpdb->charset;
      }
      if ( ! empty($this->wpdb->collate) ) {
        $charset_collate .= " COLLATE " . $this->wpdb->collate;
      }

      // check if the tables are already installed
      if( $this->_is_installed( $this->db['table_fields'] ) ) {
          // if there are already tables installed, check the version
          //  to see if and what we need to update

      } else {
        // no tables so we need to install using the latest sql
        $queries[] = "CREATE TABLE IF NOT EXISTS " . $this->db['table_fields'] . " (
          field_meta_key varchar(255) NOT NULL ,
          field_label varchar(255) NOT NULL,
          field_type varchar(255) NOT NULL,
          field_options text NOT NULL,
          field_validators text NOT NULL,
          field_not_editable_by_roles text NOT NULL,
          field_not_visible_for_roles text NOT NULL,
          field_order bigint(20) NOT NULL DEFAULT '0',
          field_required tinyint(1) NOT NULL DEFAULT '0',
          field_active tinyint(1) NOT NULL DEFAULT '1',
          KEY field_label (field_label),
          KEY field_meta_key (field_meta_key)
        ) $charset_collate;";
      }
      // include dbDelta function
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      // for some unknown reason dbDelta refuses to work correctly with multiple queries at once
      // so for now I use a for loop to loop through the necessary queries
      foreach($queries as $q) {
        dbDelta($q);
      }
    }

  }
}

// instantiate the class
if ( class_exists('CustomProfileFields') ) {
  $bbep_var = new CustomProfileFields();
  register_activation_hook(__FILE__, array(&$bbep_var,'setup_database') );
}

?>
