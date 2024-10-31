var pm_markers;

/**
 * Build simple map interface
 */
function pm_features_map(id, json, lat, lng, zoom){
    
    var map = L.map(id);
    L.tileLayer(placemarks_tiles, {
        maxZoom: 20,
        attribution: '<a href=\"http://www.openstreetmap.org/copyright\">&copy; OpenStreetMap</a>, <a href=\"https://www.mapbox.com/about/maps/\">&copy; Mapbox</a>: <a href=\"https://www.mapbox.com/map-feedback/\" target=\"_blank\">Improve this map</a>',
        id: 'mapbox.streets'
    }).addTo(map);
    
    
    pm_initialize(map); // set up layers
    
    locationJsonToSelect(placemarks_types_json.types, 'pm-select-feature');         // features select
    
    $(window).on('hashchange', function(e){                                 // rebuild each time the hash changes
        var hash_arr = window.location.hash.replace("#", "").split("-");    // get hash from url
        var campus = hash_arr[0] ? hash_arr[0] : null;
        var bldg = hash_arr[1] ? hash_arr[0]+'-'+hash_arr[1] : null;
        var floor = hash_arr[2] ? hash_arr[0]+'-'+hash_arr[1]+'-'+hash_arr[2] : null;
        var room = hash_arr[3] ? hash_arr[0]+'-'+hash_arr[1]+'-'+hash_arr[2]+'-'+hash_arr[3] : null;
        pm_update_gui(map,campus,bldg,floor,room);                              // update the gui (map and selects)
        //update_header(campus,bldg,floor,room);                              // update the index page header
    });
    
    $(window).trigger('hashchange');                                        // trigger hashchange
    $(window).trigger('resize');                                            // trigger resize
    
    
} 


/**
 * Function: Set up the map
 */
function pm_initialize(map) {
    pm_layer_floorplan = new pm_Overlay_Layer(map,placemarks_locations_json);       // add/remove floorplans with this layer
    pm_markers = new pm_Marker_Layer(map); // marker layer 
    
    // build interface
    pm_layer_controls();		
    

    
    //when campus changes
	$('#pm-select-campus,#pm-select-building,#pm-select-floor,#pm-select-room').change(function () { 
        window.location.hash = $(this).val();
        
        if(window.location.hash==""){
            map.setView(pm_default_center, pm_default_zoom);
        }
    });
   
    // floor alternative: buttons
    $('#pm-buttons-floor').on('click', 'button', function(){
       $('#pm-select-floor').val($(this).val());
       $('#pm-select-floor').change();
    });
} 

// reset layers
function pm_layer_controls(){
    var loc = pm_filter_hash(); // locations
    var type = $('#pm-select-feature').val(); // type
    pm_markers.load_url('https://wwwtest.pcc.edu/disability-services/wp-json/placemarks/v2/pins/?per_page=9999&locations='+loc+'&types='+type);
}
