<?php

/**
 * Register custom post types
 *
 * Add to wp-json routes
 *
 * @link       https://gabriel@nagmay.com
 * @since      1.0.0
 *
 * @package    Placemarks
 * @subpackage Placemarks/includes
 */


class Placemarks_Custom_Route extends WP_REST_Controller {
    
    private $pm_namespace = 'placemarks/v2';
    
    private $options;
    
    public function __construct() {}
    
    /**
     * return the value of $this_key in $json when you find $that_key with $that_value
     */
    protected function jsonFindSingleValue($this_key, $json, $that_key, $that_value){
         $out = "";
         foreach($json as $k => $v){
             if(is_array($v)){								// keep going
                 foreach($v as $next_json){
                        $out .= $this->jsonFindSingleValue($this_key, $next_json, $that_key, $that_value);
                 }
             }
             elseif($k=$that_key && $v == $that_value){		// stop
                $out = $json->$this_key;
                break;
             }
        }
        return $out;
     }
    
    /**
    * Register the routes for the objects of the controller.
    */
    public function register_routes() {
        $namespace = $this->pm_namespace;
        $base = 'pins';
        register_rest_route( $namespace, '/pins', array(
          array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'pm_pins' ),
            'args'                => array(
              'types' => array(
                'default' => '',
                'validate_callback' => function($param, $request, $key) {return is_string( $param );}
              ),
              'locations' => array(
                'default' => '',
                'validate_callback' => function($param, $request, $key) {return is_string( $param );}
              ),
              'ids' => array(
                'default' => '',
                'validate_callback' => function($param, $request, $key) {return is_string( $param );}
              ),
              'per_page' => array(
                'type' => 'integer',  
                'default' => 10,
                'description' => 'Maximum number of items to be returned in result set.',
                'validate_callback' => function($param, $request, $key) {return is_numeric( $param );}
              ),
            ),          
          )
        ) );
        register_rest_route( $namespace, '/settings', array(
          array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'pm_settings' ),
            'args'                => array(
              'setting' => array(
                'type' => 'string',
                'description' => 'Return setting: locations, types, tiles, list-locations-by-type, list-types-by-location',
                'default' => '',
                'validate_callback' => function($param, $request, $key) {return is_string( $param );}
              ),
            ),
          ),
        ) );
        register_rest_route( $namespace, '/' . $base . '/schema', array(
          'methods'  => WP_REST_Server::READABLE,
          'callback' => array( $this, 'get_public_item_schema' ),
        ) );
    } // register_routes
    
    /**
    * Display the setting: location, types
    * @param WP_REST_Request $request Full data about the request.
    * @return WP_Error|WP_REST_Response
    */
    public function pm_settings($request){
        global $wpdb, $placemarks_tiles, $placemarks_types_json, $placemarks_locations_json;

        if($request['type'] == 'locations'){
            return $placemarks_locations_json;
        }
        elseif($request['type'] == 'types'){
            return $placemarks_types_json;
        }
        elseif($request['type'] == 'tiles'){
            return $placemarks_tiles;
        }
        elseif($request['type'] == 'list-locations-by-type'){
            $i=0;
            foreach($placemarks_types_json->types as $type) { 
                    $slug = $type->slug;
                    $locations = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = 'placemarks-locations' AND post_id IN (SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = 'placemarks-type' AND meta_value = '$slug')" );
                    if($locations){
                        $type->locations = $locations;
                    }
                    else{
                        unset($placemarks_types_json->types[$i]); // remove type from list if it is not used (eg, no locations)
                    }
                $i++;
            }
            return json_encode($placemarks_types_json);
        }
        elseif($request['type'] == 'list-types-by-location'){
            
            
        }
        else{
            return new WP_Error( 'no_pin', 'Invalid', array( 'status' => 404 ) );
        }
    }
    
    /**
    * Display placemark pins
    * @param WP_REST_Request $request Full data about the request.
    * @return WP_Error|WP_REST_Response
    */
    public function pm_pins($request){
        global $post,$wpdb,$placemarks_types_json, $placemarks_locations_json;
        $geojson = json_decode('{"type": "FeatureCollection","features": []}');
        
        $query_array = array(   'post_type'=>'placemark', // Start building query (this will grab all)
                                'posts_per_page'=>$request['per_page'],
                                'post_status' => 'publish',
                                'orderby' => 'meta_value', 
                                'meta_key' => 'placemarks-type',
                                'order'=>'ASC'); 
        
        $query_meta_array = array(); // to limit by meta

        if($types = $request['types']){  	// based on types
            $types_array = array_map('trim', explode(',',$types)); 			// create trimmed array
            array_push($query_meta_array, array('key' => 'placemarks-type','value' => $types_array,'compare' => 'IN')); // limit by types
            }
        if($locations = $request['locations']){  	// based on locations
            $locations_array = array_map('trim', explode(',',$locations)); 	// create trimmed array
            array_push($query_meta_array, array('key' => 'placemarks-locations','value' => $locations_array,'compare' => 'IN')); // limit by locations
        }
        if($ids = $request['ids']){ // based on ids
            $ids_array = array_map('trim', explode(',',$ids)); 	   	// create trimmed array
            $query_array['post__in'] = $ids_array;					// limit by ids
        }

        $query_array['meta_query'] = $query_meta_array; 			// add meta
        $placmarks_query = new WP_Query($query_array); 				// run query

        while ($placmarks_query->have_posts()) : $placmarks_query->the_post(); 
             // what we know
                $p_id = 		$post->ID;
                $p_lat = 		attribute_escape( get_post_meta($p_id,"placemarks-lat",true));
                $p_lng = 		attribute_escape( get_post_meta($p_id,"placemarks-lng",true));
                $p_location = 	attribute_escape( get_post_meta($p_id,"placemarks-location",true));
                $p_locations = 	attribute_escape( implode(", ", get_post_meta($p_id,"placemarks-locations",false)));  // make list
                $p_title = 		attribute_escape( get_post_meta($p_id,"placemarks-title",true));
                $p_bubble = 	attribute_escape( get_post_meta($p_id,"placemarks-bubble",true));
                $p_type = 		attribute_escape( get_post_meta($p_id,"placemarks-type",true));
                $p_link = 		attribute_escape( get_post_meta($p_id,"placemarks-link",true));

                $p_name =       attribute_escape($this->jsonFindSingleValue("name",$placemarks_types_json,"slug",$p_type)); // find name from type

                // if p_link add to bubble
                if($p_link){
                    $p_link_id = url_to_postid( $p_link );      // 0 if not from this blog!
                    if($p_link_id){
                        $p_link = get_permalink( $p_link_id);   // get full link
                        //$p_bubble .= placemarks_thumb_by_scan($p_link_id);	// get attachment only works if uploaded to post, instead let's scan the content
                    }
                    $p_bubble .= " <a href='$p_link'>Learn more <span class='visually-hide'>($p_name #$p_id)</span> &raquo;</a>";
                }

                // which title to use
                $p_title = $p_title!="" ? $p_title : $p_name; 

                // link to edit?
                if(current_user_can('edit_posts')){ // can the user edit placemarks?
                     $p_title .= ' | <a href=\"'.admin_url()."post.php?post=$p_id&action=edit".'\"  target=\"_blank\" >Edit</a>'; 
                }

                // icon
                $src = $this->jsonFindSingleValue("src",$placemarks_types_json,"slug",$p_type); 				
                if(!$src){ $src = $this->jsonFindSingleValue("src",$placemarks_types_json,"name",$p_type); } 	// prior to 1.0.4 slug was not required
        
                $feature = (object)[
                    'type' => 'Feature',
                    'geometry' => (object)[
                        'type' => 'point',
                        'coordinates' => [$p_lat, $p_lng]
                    ],
                    'properties' => (object)[
                        'title' => $p_title,
                        'name' => $p_name,
                        'type' => $p_type,
                        'description' => $p_bubble,
                        'location_list' => $p_locations,
                        'location_alt'  => $p_location,
                        'icon' => $src,
                    ]
                ];
                array_push($geojson->features,$feature);
        
            endwhile;

        return($geojson);
    }
    
} // Placemarks_Custom_Route

