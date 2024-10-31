<?php

/**
 * The settings page(s)
 *
 * @link       https://gabriel@nagmay.com
 * @since      1.0.0
 *
 * @package    Placemarks
 * @subpackage Placemarks/admin
 */

class Placemarks_Settings {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

     /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

    // Register the administration menu for this plugin into the WordPress Dashboard menu.
    public function add_plugin_admin_menu() {
        // Single site > Settings > Placemarks
        add_submenu_page( 'options-general.php', 'Settings Admin', 'Placemarks', 'manage_options', $this->plugin_name, array($this, 'display_settings_page') );
        // Network > Settings > Placemarks
        add_submenu_page( 'settings.php', 'Settings Admin', 'Placemarks', 'manage_options', $this->plugin_name, array($this, 'display_settings_page') );
    }

	// on network save
    public function network_placemarks_options_function(){
        $options = array();
		foreach ( $_POST['placemarks_options'] as $key => $val ) {
			$options[$key] = ( isset(  $_POST['placemarks_options'][$key] ) ) ?  stripslashes($val)  : ''; // clean up slashes in _post
		}
		update_site_option( 'placemarks_options', $this->validate($options) ) ; // stores in wp_sites

		// redirect to settings page in network
		wp_redirect(
			add_query_arg(array( 'page' => 'placemarks', 'updated' => 'true' ), network_admin_url( 'admin.php' ))
		);
		exit;
    }

    // Add settings action link to the plugins page.
    public function add_action_links( $links ) {

        /*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
       $settings_link = array(
        '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __( 'Settings', $this->plugin_name ) . '</a>',
       );
       return array_merge(  $settings_link, $links );

    }

    /**
     * Validate fields from admin area plugin settings form 
     * @param  mixed $input as field form settings form
     * @return mixed as validated fields
     */
    public function validate($input) {
        $valid = array();
        $save = array( 
                            array('placemarks_tiles',''),
                            array('placemarks_types_json','textarea'),
                            array('placemarks_locations_json','textarea'),
                          );
        foreach($save as $s){
            if(isset($input[$s[0]]) && !empty($input[$s[0]])){
                $in = $input[$s[0]];
                if($s[1]=='textarea'){
                    $valid[$s[0]] = sanitize_textarea_field($in);
                }
                else{
                    $valid[$s[0]] = sanitize_text_field($in);
                }
            }
            else{
                $valid[$s[0]] = '';
            }
        }
        return $valid;
    }

    public function display_settings_page(){
        ?>
        <div class="wrap">
            <?php
            	// different form actions for multisite. 
                if(is_network_admin()){
                    echo '<div class="wrap">
                          <h2>Placemarks Settings: Network</h2>
                          <p>These can be overwritten for individual sites:</p>
                          <form action="edit.php?action=placemarks_options" method="post">';
                    // Set class property
                    $this->options = get_site_option( 'placemarks_options' );
                }
                else{
                    echo '<div class="wrap">
                          <h2>Placemarks Settings</h2>';
                    if(is_multisite()){
                        echo '<p>Editing these will override the network defaults:</p>';
                    }
                    echo'<form action="options.php" method="post">';
                    // Set class property
                    $this->options = get_option( 'placemarks_options' );
                }
            

                settings_fields($this->plugin_name);
                do_settings_sections($this->plugin_name);
            ?>
            <h2>Notes:</h2>
                <p>Having trouble? Be sure to check out <a href="http://gabriel.nagmay.com/2013/10/placemarks/" target="_blank">the documentation</a> and <a href="http://jsonlint.com/" target="_blank">validate your JSON</a>.</p>
            <?php
                submit_button( __( 'Save placemarks settings', $this->plugin_name ), 'primary','submit', TRUE ); 
            ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Register and add settings
     */
    public function build_form_sections(){        
        
        register_setting( $this->plugin_name, $this->plugin_name.'_options', array( $this, 'validate' ) ); // on update

        add_settings_section(
            'setting_section', // section id
            '', // Title
            '', // Callback
            $this->plugin_name // Page
        ); 
        add_settings_field(
            'placemarks_tiles',     // id
            'Tile Layer Server',    // title 
            array( $this, 'placemarks_display_callback' ), // callback
            $this->plugin_name,     // page
            'setting_section',      // section 
            array(                  // args
                'id' => 'placemarks_tiles',
                'type' => 'text'
            )
        );  
        add_settings_field(
            'placemarks_types_json',     // id
            'Marker Types (JSON)',    // title 
            array( $this, 'placemarks_display_callback' ), // callback
            $this->plugin_name,     // page
            'setting_section',      // section 
            array(                  // args
                'id' => 'placemarks_types_json',
                'type' => 'textarea'
            )
        );  
        add_settings_field(
            'placemarks_locations_json',     // id
            'Locations (JSON)',    // title 
            array( $this, 'placemarks_display_callback' ), // callback
            $this->plugin_name,     // page
            'setting_section',      // section 
            array(                  // args
                'id' => 'placemarks_locations_json',
                'type' => 'textarea'
            )
        );  
    }
    
    public function placemarks_display_callback($args){
        $id = $args['id'];
        $type = $args['type'];
        $val = isset($this->options[$id]) ?  esc_attr($this->options[$id]) : '';
        $placeholder = '';
        // multisite?
        if(is_multisite()){
            $placemarks_options = get_site_option('placemarks_options');
            $placeholder = $placemarks_options[$id] ? 'Set at network level' : '';
        }
        if($type=='textarea'){
            echo '<textarea id="'.$id.'" name="placemarks_options['.$id.']" style="width:100%;" placeholder="'.$placeholder.'">'.$val.'</textarea>';
        }
        else{
            echo '<input type="text" id="'.$id.'" name="placemarks_options['.$id.']" style="width:100%;" value="'.$val.'" placeholder="'.$placeholder.'" />';
        }
    }
	
    
    /**
     * helper functions
     */
        
    
    // options (setting page)
    private function get_option($options,$name){
        if($options){
            if(array_key_exists($name, $options)){
                return $options[$name];
            }
        }
        return '';
    }
    
    

} // class
