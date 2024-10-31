<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://gabriel@nagmay.com
 * @since      1.0.0
 *
 * @package    Placemarks
 * @subpackage Placemarks/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Placemarks
 * @subpackage Placemarks/includes
 * @author     Gabriel Nagmay <gabriel.nagmay@pcc.edu>
 */
class Placemarks {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Placemarks_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
   

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PLACEMARKS_VERSION' ) ) {
			$this->version = PLACEMARKS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'placemarks';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Placemarks_Loader. Orchestrates the hooks of the plugin.
	 * - Placemarks_i18n. Defines internationalization functionality.
	 * - Placemarks_Admin. Defines all hooks for the admin area.
	 * - Placemarks_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		// orchestrating the actions and filters of the core plugin.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-placemarks-loader.php';

		// internationalization functionality
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-placemarks-i18n.php';
        
        // custom Post Types
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-placemarks-post-types.php';
        
        // api
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-placemarks-api.php';

		// admin area
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-placemarks-admin.php';
        		
        // columns 'all' page
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-placemarks-columns.php';       
        
        // admin settings page
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-placemarks-settings.php';

		// public-facing side of the site.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-placemarks-public.php';

        $this->loader = new Placemarks_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Placemarks_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Placemarks_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Placemarks_Admin( $this->get_plugin_name(), $this->get_version() );

        // enqueue
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        
        // custom post types
        $plugin_post_types = new Placemarks_Post_Types();
        $this->loader->add_action( 'init', $plugin_post_types, 'create_custom_post_type', 999 ); 
        
        // meta boxes 
        $this->loader->add_action( 'admin_init', $plugin_admin, 'rerender_meta_options' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_options' );
        
        // columns
        $plugin_columns = new Placemarks_Columns();
        $this->loader->add_action( 'manage_edit-placemark_columns', $plugin_columns, 'columns' );
        $this->loader->add_action( 'manage_posts_custom_column', $plugin_columns, 'custom_columns' );
        $this->loader->add_action( 'manage_edit-placemark_sortable_columns', $plugin_columns, 'sortable_columns' );
        $this->loader->add_action( 'pre_get_posts', $plugin_columns, 'orderby' );
        
        // api: new Placemarks_Custom_Route();
        $plugin_api = new Placemarks_Custom_Route;
        $this->loader->add_action( 'rest_api_init', $plugin_api, 'register_routes', 999 ); 

        // settings page(s)
        $plugin_settings = new Placemarks_Settings( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action('admin_init', $plugin_settings, 'build_form_sections'); // Register fields, connect to save function
        $this->loader->add_action( 'admin_menu', $plugin_settings, 'add_plugin_admin_menu' ); // Add menu item
        $this->loader->add_action( 'network_admin_menu', $plugin_settings, 'add_plugin_admin_menu' ); // Add network menu item
        $this->loader->add_action( 'network_admin_edit_placemarks_options', $plugin_settings, 'network_placemarks_options_function' ); // on network edit
        $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' ); // Add Settings link to the plugin
        $this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links' );
    }

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Placemarks_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_shortcode( 'placemarks', $plugin_public, 'shortcode_placemarks', $priority = 10, $accepted_args = 2 ); // placemarks shortcode

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Placemarks_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
    
    public function get_option($name){
        
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

}
