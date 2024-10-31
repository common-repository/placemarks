<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://gabriel@nagmay.com
 * @since      1.0.0
 *
 * @package    Placemarks
 * @subpackage Placemarks/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Placemarks
 * @subpackage Placemarks/public
 * @author     Gabriel Nagmay <gabriel.nagmay@pcc.edu>
 */
class Placemarks_Public {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Placemarks_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Placemarks_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/placemarks-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Placemarks_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Placemarks_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/placemarks-public.js', array( 'jquery' ), $this->version, false );

	}
    
    /**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function shortcode_placemarks($atts) {
        extract( shortcode_atts( array(
            'template'  => 'simple',    // simple or features
            // simple map options
            'types' 	=> '',
            'locations'	=> '',
            'align'     => 'center', // (center 100, right 50, left 50) 
            'ids'		=> '',
            'lat'		=> '""',
            'lng'		=> '""',
            'zoom'		=> '""',      // '17',
            'height'	=> '400px',
            // feature maps
            'location'  => 'sy-cc-1', // where should we start? location hash
            'name'      => '', // used in the interface text, eg. "we dont know about any xxx features."
        ), $atts ) );
        
        // track instance, so we can have multiple on the same page
        static $instance = 0;
        $instance++;
        
        // styles
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/placemarks-public.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name.'leaflet', plugin_dir_url( __DIR__ ) . 'vendor/leaflet/leaflet.css', array(), $this->version, 'all' );
       
        // data from api
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/placemarks-public.js', array( 'jquery' ), $this->version, true ); 
        wp_enqueue_script( $this->plugin_name.'jsonp_tiles', rest_url('/placemarks/v2/settings?type=tiles&_jsonp=jsonp_tiles'), array( 'jquery' ), $this->version, true );
        wp_enqueue_script( $this->plugin_name.'jsonp_locations', rest_url('/placemarks/v2/settings?type=locations&_jsonp=jsonp_locations'), array( 'jquery' ), $this->version, true );
        wp_enqueue_script( $this->plugin_name.'jsonp_types', rest_url('/placemarks/v2/settings?type=types&_jsonp=jsonp_types'), array( 'jquery' ), $this->version, true );

        // map scripts
        wp_enqueue_script( $this->plugin_name.'leaflet', plugin_dir_url( __DIR__ ) . 'vendor/leaflet/leaflet.js', array( 'jquery' ), $this->version, true );
        wp_enqueue_script( $this->plugin_name.'overlay-obj', plugin_dir_url( __DIR__ ) . 'public/js/includes/overlay-layer.obj.js', array( 'jquery' ), $this->version, true );

        // inline scripts for each map instance on page
        $json = rest_url("/placemarks/v2/pins/?per_page=999&locations=$locations&types=$types&ids=$ids");
        $script_a ="(function($){'use strict';$.getJSON(\"$json\", function(data) {";
        $script_b = "});})( jQuery );";
        
        if($template == 'features'){
            return "Feature template TBD";
            /*
            wp_enqueue_script( $this->plugin_name.'marker-obj', plugin_dir_url( __FILE__ ) . 'js/includes/marker-layer.obj.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( $this->plugin_name.'overlay-obj', plugin_dir_url( __FILE__ ) . 'js/includes/overlay-layer.obj.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( $this->plugin_name.'features-gui', plugin_dir_url( __FILE__ ) . 'js/includes/features-gui.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( $this->plugin_name.'features', plugin_dir_url( __FILE__ ) . 'js/placemarks-features.js', array( 'jquery' ), $this->version, true );
            $function = "pm_features_map('pm-map-$instance',data,$lat,$lng,$zoom)";
            $script = "$script_a $function; $script_b";
            wp_add_inline_script( $this->plugin_name.'features', $script); 
            return '    
                <div class="placemarks pm-features" class="row align-'.$align.'">
        
                    <div class="small-12 medium-4 columns" id="control-bar">
                        <div id="location-controls-box">
                            <div class="row location-controls">
                              <div class="columns small-3"><label for="select-feature">Feature</label></div>
                              <div class="columns small-9">
                                <select id="pm-select-feature">
                                  <option value="">loading...</option>
                                </select>
                              </div>
                            </div>

                            <div class="row location-controls">
                              <div class="columns small-3"><label for="campus">Campus</label></div>
                              <div class="columns small-9">
                                <select id="pm-select-campus">
                                  <option value="">loading...</option>
                                </select>
                              </div>
                            </div>

                            <div class="row location-controls">
                              <div class="columns small-3"><label for="select-feature">Building</label></div>
                              <div class="columns small-9">
                                <select id="pm-select-building">
                                  <option value="">loading...</option>
                                </select>
                              </div>
                            </div>
                            <div class="row location-controls">
                              <div class="columns small-3"><label for="select-feature">Floors</label></div>
                              <div class="columns small-9">
                                <select id="pm-select-floor">
                                  <option value="">loading...</option>
                                </select>
                              </div>
                            </div>
                        </div>

                        <div id="legend">
                          <h4 class="visually-hide"></h4>
                          <p id="building-info"></p>
                          <ul>
                            <li class="loading"></li>
                          </ul>
                        </div>

                        <div id="pm_shadow_bottom"></div>
                    </div> <!-- #control-bar -->

                    <div class="small-12 medium-8 columns" id="map-column">
                          <div id="pm-map-'.$instance.'" class="pm-map" style="height:'.$height.'">
                            <button id="icons-off" class="off">turn icons off</button>
                            <div id="pm-buttons-floor"></div>
                          </div>
                    </div>

                </div><!-- .row -->
                <p class="visually-hide">The graphic information from the map on this page is also represented in narrative in the list.</p>';
                */
        }
        else{
            wp_enqueue_script( $this->plugin_name.'simple', plugin_dir_url( __FILE__ ) . 'js/placemarks-simple.js', array( 'jquery' ), $this->version, true );
            $function = "pm_simple_map('pm-map-$instance',data,$lat,$lng,$zoom)";
            $script = "$script_a $function; $script_b";
            wp_add_inline_script( $this->plugin_name.'simple', $script); 
            return "<div class=\"placemarks pm-simple align-$align\"/><div id=\"pm-map-$instance\" class=\"pm-map\" style=\"height:$height;\"></div></div>";
        }
        
	}

}
