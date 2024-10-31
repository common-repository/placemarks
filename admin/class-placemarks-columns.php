<?php

/**
 * The columns in the 'all' view
 *
 * @link       https://gabriel@nagmay.com
 * @since      1.0.0
 *
 * @package    Placemarks
 * @subpackage Placemarks/admin
 */

class Placemarks_Columns {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct(){}

    
    /* === Show these columns when editing post type: placemarker  === */
    public function columns($columns){
            $columns = array(			

                "cb" => "<input type=\"checkbox\" />",  	// built-in
                //"id" => "Placemark ID",						// custom 			
                "icon" => "Type",							// custom 
                "type" => "Title",							// custom 
                "locations" =>	"Locations",				// custom
                "description" => "Location Description",	// custom
                /*"latlng" => "Place",						// custom */
                "author" => "Author",						// built-in
                "date" => "Date"							// built-in

            );

            return $columns;
    }
    
    /* === Here we define what each custom edit column should do (all post types)  === */
    public function custom_columns($column){
            global $post,$placemarker_path, $placemarks_types_json, $placemarks_locations_json;
            $custom = get_post_custom();
            switch ($column){
                case "id":
                    echo edit_post_link($post->ID,'','',$post->ID);
                    break;
                case "latlng":
                    echo edit_post_link( $custom["placemarks-lat"][0].", ".$custom["placemarks-lng"][0],'','',$post->ID);
                    $status = get_post_status($post->ID);
                    if($status == "pending"){
                        echo " - Pending";
                    }
                    echo "</strong>";
                    break;	
                case "icon":
                    $src = $this->jsonFindSingleValue("src",$placemarks_types_json,"slug",$custom["placemarks-type"][0]); // find icon
                    if(!$src){ $src = $this->jsonFindSingleValue("src",$placemarks_types_json,"name",$custom["placemarks-type"][0]); } 	// prior to 1.0.4 slug was not required
                    $name = $this->jsonFindSingleValue("name",$placemarks_types_json,"slug",$custom["placemarks-type"][0]); // find name
                    if(!$name){ $name = $custom["placemarks-type"][0]; } 	// prior to 1.0.4 slug was not required.
                    $icon = '<img src="'.$src.'" style="vertical-align:text-top;" alt="'.$name.'" title="Edit: '.$name.' (ID #'.$post->ID.')" Icon" /> ';
                    echo edit_post_link($icon,'','',$post->ID);
                    break;
                case "type":
                    if(isset($custom["placemarks-title"][0])){
                        $name = $custom["placemarks-title"][0];
                    }
                    else{
                        $name = $this->jsonFindSingleValue("name",$placemarks_types_json,"slug",$custom["placemarks-type"][0]); // find name
                        if(!$name){ $name = $custom["placemarks-type"][0]; } 	// prior to 1.0.4 slug was not required
                    }
                    echo edit_post_link($name,'','',$post->ID);
                    break;
                case "description":
                    echo isset($custom["placemarks-location"]) ? $custom["placemarks-location"][0] : "";
                    break;			
                case "locations":
                    if(isset($custom["placemarks-locations"])){
                        /* really should put these into individual columns */
                        $locs = $custom["placemarks-locations"];
                        asort($locs);
                        foreach( $locs as $loc){	// find real names
                            $lname = $this->jsonFindSingleValue("name", $placemarks_locations_json,"slug",$loc);
                            echo ($lname !== '') ? $lname : "<span style='color:red;'>$loc</style>"; // if not found 
                            if ($loc !== end($locs)){ echo ', '; }
                        }
                    }
                    else{
                        echo "";
                    }
                    break;
            }
    }

    /* === Here we make them sortable  === */
    public function sortable_columns( $columns ) {  
            $columns = array(			
                "id" => "id",							
                "icon" => "icon",							// custom 
                "type" => "type",							// custom 
                "locations" => "locations",					// custom 
                "description" => "description",             // custom 
                "date"  => "date"
            );
            return $columns;   
        }  
    
    public function orderby( $query ) {
        $orderby = $query->get( 'orderby');

        switch ($orderby){
                case "icon":
                    $query->set('meta_key','placemarks-type');
                    $query->set('orderby','meta_value'); // alpha
                    break;
                case "type":
                    $query->set('meta_key','placemarks-title');
                    $query->set('orderby','meta_value'); // alpha
                    break;
                case "locations":
                    $query->set('meta_key','placemarks-locations');
                    $query->set('orderby','meta_value'); // alpha
                    break;
                case "description":
                    $query->set('meta_key','placemarks-location');
                    $query->set('orderby','meta_value'); // alpha
                    break;
        }
    }
    
    /* ==================================================================
     * helper functions
     * ================================================================== */

    // return the value of $this_key in $json when you find $that_key with $that_value
    private function jsonFindSingleValue($this_key, $json, $that_key, $that_value){
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
    
    
} // class
