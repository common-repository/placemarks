<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://gabriel@nagmay.com
 * @since      1.0.0
 *
 * @package    Placemarks
 * @subpackage Placemarks/admin
 */

class Placemarks_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles($hook) {
        if($this->on_placemarks($hook)){
		  wp_enqueue_style( $this->plugin_name.'-leaflet', plugin_dir_url( __DIR__ ) . 'vendor/leaflet/leaflet.css', array(), $this->version, 'all' );
		  wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/placemarks-admin.css', array(), $this->version, 'all' );
        }
    }
    
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook) {
        if($this->on_placemarks($hook)){
            // data from api
            wp_enqueue_script( $this->plugin_name.'jsonp_init', plugin_dir_url( __DIR__ ) . 'public/js/placemarks-public.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( $this->plugin_name.'jsonp_tiles', rest_url('/placemarks/v2/settings?type=tiles&_jsonp=jsonp_tiles'), array( 'jquery' ), $this->version, true );
            wp_enqueue_script( $this->plugin_name.'jsonp_locations', rest_url('/placemarks/v2/settings?type=locations&_jsonp=jsonp_locations'), array( 'jquery' ), $this->version, true );
            wp_enqueue_script( $this->plugin_name.'jsonp_types', rest_url('/placemarks/v2/settings?type=types&_jsonp=jsonp_types'), array( 'jquery' ), $this->version, true );
            
            // map scripts
            wp_enqueue_script( $this->plugin_name.'leaflet', plugin_dir_url( __DIR__ ) . 'vendor/leaflet/leaflet.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( $this->plugin_name.'overlay-obj', plugin_dir_url( __DIR__ ) . 'public/js/includes/overlay-layer.obj.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/placemarks-admin.js', array( 'jquery' ), $this->version, true );
        }
	}
    
    // Save custom fields
    public function save_meta_options() {
        if ( ! current_user_can( 'edit_posts' ) ) return;

        global $post;
        //                  array(meta_key, type='text', multiple=FALSE)
        $save_array = array( 
                            array('placemarks-lat'),
                            array('placemarks-lng'),
                            array('placemarks-location','textarea'),
                            array('placemarks-locations',''),
                            array('placemarks-type'),
                            array('placemarks-title'),
                            array('placemarks-bubble','textarea'),
                            array('placemarks-link'),
                          );
        foreach($save_array as $save){
            $key = $save[0];
            $type = isset($save[1]) ? $save[1] : 'text';
            if(array_key_exists($key, $_POST)){
                // multiple entries (delete all and add new)
                if(is_array($_POST[$key])){
                    delete_post_meta($post->ID,$key); // remove all old data
                    foreach($_POST[$key] as $content){
                        $content = $this->sanitize($content, $type );
                        if($content){
                            add_post_meta($post->ID, $key, $content);
                        }
                    }
                }
                // single entry (update)
                else{
                    $content = $this->sanitize($_POST[$key], $type );
                    if($content){
                        update_post_meta($post->ID, $key, $content);
                    }
                    else{
                        delete_post_meta($post->ID,$key);
                    }
                }
            }
        }
    }
    
    /* Sanitize for inputs*/
    private function sanitize($content, $type='text'){
        if($type=='textarea'){
            return sanitize_textarea_field($content);
        }
        else{
            return sanitize_text_field($content);
        }
    }

    /* Create a meta box for our custom fields */
    public function rerender_meta_options() {
        add_meta_box( 'pm-metabox-place', 'Place', array($this, "display_metabox_place"), 'placemark', 'normal' );
        add_meta_box( 'pm-metabox-mark', 'Mark', array($this, "display_metabox_mark"), 'placemark', 'normal' );

        // also remove slug meta
        remove_meta_box( 'slugdiv', 'placemark', 'normal' );

    }

    // Display meta box: 'Place'
    public function display_metabox_place() {
        global $post;
        $meta = get_post_custom($post->ID);
        ?>

        <script>
            // extra jsonp data 
            var placemarks_locations_meta 	= <?php echo json_encode($this->get_meta($meta, "placemarks-locations", FALSE)); ?>; // current locations for this placemark
            var placemarks_marker_meta 	= "<?php echo $this->get_meta($meta, "placemarks-type"); ?>"; // current mark for this placemark
        </script>

        <p>
            <label><?php _e( 'Choose a location:', $this->plugin_name ); ?></label>
            <div id="placemarks-location-selects"></div>
        </p>

        <div id="pm-map"></div>
        <p>
            <label><?php _e( 'Latitude, longitude:', $this->plugin_name ); ?></label> 
            <input id="placemarks-lat" name="placemarks-lat" value="<?= $this->get_meta($meta, "placemarks-lat"); ?>" required pattern="-?\d+\.\d+" title="GPS format: xx.xxxxxx"/>, 
            <input id="placemarks-lng" name="placemarks-lng" value="<?= $this->get_meta($meta, "placemarks-lng"); ?>" required pattern="-?\d+\.\d+" title="GPS format: xx.xxxxxx" />
            <input id="mapgps" class="button" type="button" value="Use current location" name="mapgps" />
        </p>
        <p>
            <label><?php _e( 'Alternative text description of location:', $this->plugin_name ); ?></label>
            <textarea name="placemarks-location" required><?= $this->get_meta($meta, "placemarks-location"); ?></textarea>
        </p>
        <p class="instructions">For example: "On the south wall, across from room CC123"</p>
        <?php 
    }
    
    // Display meta box: 'Mark'
    public function display_metabox_mark() {
        global $post;
        $meta = get_post_custom($post->ID);
        ?>
        <p>
            <label><?php _e( 'Mark type:', $this->plugin_name ); ?></label> 
            <select id="placemarks-type" name="placemarks-type"></select>
            <img id="placemark-marker-image">
            
        </p>    
        <p>
            <label><?php _e( 'Title (optional):', $this->plugin_name ); ?></label> 
            <input name="placemarks-title" value="<?= $this->get_meta($meta, "placemarks-title"); ?>"/>        
        </p>        
        <p>
            <label><?php _e( 'Text (optional):', $this->plugin_name ); ?></label> 
            <textarea name="placemarks-bubble"><?= $this->get_meta($meta, "placemarks-bubble"); ?></textarea>
            <p class="instructions">For example: "Keypad access code available at various locations, sign on door."
        </p>        
        <p>
            <label><?php _e( 'Link (optional):', $this->plugin_name ); ?></label> 
            <input name="placemarks-link" value="<?= $this->get_meta($meta, "placemarks-link"); ?>"/>
        </p>

        <?php 
    }
    
    
    /**
     * helper functions
     */
        
    // meta 
    private function get_meta($meta,$name,$single=TRUE){
        if($meta && array_key_exists($name, $meta)){
            if($single){
                return $meta[$name][0];
            }
            else{
                return $meta[$name];
            }
        }
        return '';
    }
    
    // on edit placemark page?
    private function on_placemarks($hook){
        global $post;
        if ( $hook == 'post-new.php' || $hook == 'post.php' ) { 
            if ( $post && $post->post_type == 'placemark') { 
                return true;
            }
        }
        return false;
    }
    
    // get option
    private function get_option($name){
        $val = '';

        // if this is a network installation, start here
        if(is_multisite()){  
            $placemarks_options = (get_site_option('placemarks_options'));
            $val = isset($placemarks_options[$name]) ? $placemarks_options[$name] : $val;
        }

        // local site options. Override the network options if these exist
        $placemarks_options = (get_option('placemarks_options')); 
        $val = !empty($placemarks_options[$name]) ? $placemarks_options[$name] : $val;
        return $val;
    }

} // class
