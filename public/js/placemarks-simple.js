/**
 * Build simple map interface
 */
function pm_simple_map(id, json, lat, lng, zoom){
    
    // globals: placemarks_tiles
    var pm_marker_array=[],pm_marker_group={}; 
    
    var map = L.map(id);
    L.tileLayer(placemarks_tiles, {
        maxZoom: 20,
        attribution: '<a href=\"http://www.openstreetmap.org/copyright\">&copy; OpenStreetMap</a>, <a href=\"https://www.mapbox.com/about/maps/\">&copy; Mapbox</a>: <a href=\"https://www.mapbox.com/map-feedback/\" target=\"_blank\">Improve this map</a>',
        id: 'mapbox.streets'
    }).addTo(map);
    
    // Add each marker to pm_marker_array 
    $.each(json.features, function (i) {                       
			pm_markers(json.features[i],pm_marker_array,pm_marker_group);
    });
    
    // convert each "group" to a leaflet group. Add each group to the map. 
    for( var key in pm_marker_group){
        pm_marker_group[key]= L.featureGroup(pm_marker_group[key]).addTo(map); 
    }
    
    // add controls to turn on/off groups (if more than one)
    if(Object.keys(pm_marker_group).length > 1){
        L.control.layers('',pm_marker_group).addTo(map); 
    }

    // center
    if(lat && lng && !isNaN(lat) && !isNaN(lng)){
        map.setView([lat,lng]);
    }
    else{
        map.fitBounds(L.featureGroup(pm_marker_array).getBounds()); // fit map 
    }
    
    // zoom
    if(zoom && !isNaN(zoom)){
       map.setZoom(zoom);
    }
}

/**
 * Create individual placemarks from geojson
 * Expects GeoJason format:
 *      {'type':'FeatureCollection','features':[
 *           {
 *            'type': 'Feature',
 *            'geometry': {
 *                'type': 'Point',
 *                'coordinates': [lat, lng]
 *              },
 *             'properties': {
 *                'name': '',           // optional - shows in box
                  'description': ''     // optional - shows in box
 *                'icon': '',           // optional - icon src
 *                'location_alt': ''    // optional - alt description shows in box
 *              }
 *            }
 *      ]}
 */
function pm_markers(json,pm_marker_array,pm_marker_group ) {    
    var lat = json.geometry.coordinates[0] || '';
	var lng = json.geometry.coordinates[1] || '';
    var type = json.properties.type || '';
    var name = json.properties.name || '';
    var alt  = json.properties.location_alt || '';
    var desc = json.properties.description || ''; 
    var src = json.properties.icon || '';

    // are all required items available?
    if(!lat || !lng){
        return;
    }
	
    // pm_markers
	var pm_icon = L.Icon.extend({
		options: {
			iconSize:     [24, 34],
			iconAnchor:   [12, 30],
			popupAnchor:  [0, -30]
		}
	});
	var icon = new  pm_icon({iconUrl: json.properties.icon});
    
	var pm_marker = L.marker([lat, lng], {
        icon: icon,                     // set icon 
        zIndexOffset:0,                 // allow us to play with zindex to keep in icon in front
        riseOnHover: true,              // bring to front on hover
        riseOffset: 9999,               // we're messing with zindex on click, so this number needs to be much bigger than pm_zindex
    }); 
    
    // info window content
    if(name || alt || desc){
        var content = '';
        content += name ? "<div class='window'><h4>" + name + "</h4>" : '';
        content += alt ? "<p><em>" + alt + "</em></p>" : '';
        content += desc ? "<p>" + desc + "</p>" : '';
        pm_marker.bindPopup(content).on('click', pm_marker_to_top); // bind popup and click handler
    }
    
    pm_marker_array.push(pm_marker);    // add to array, so we can track/fit bounds
    if(!pm_marker_group[name]){      // also add to the group object so we can disable seperatly
       pm_marker_group[name] = [];
    }
    pm_marker_group[name].push(pm_marker);

}

/**
 * Function: move this pm_marker to the top
 */
function pm_marker_to_top(marker) {
    if( typeof pm_marker_to_top.zindex == 'undefined' ) {
        pm_marker_to_top.zindex = 5000; // create a static var to increment
    }
    this.setZIndexOffset(pm_marker_to_top.zindex);
    pm_marker_to_top.zindex++;
}
