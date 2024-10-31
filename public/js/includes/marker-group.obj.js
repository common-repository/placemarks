/* Object to hold/control marker groups
 *  map         : map element
 *  checkbox_id : id of checkbox control
 *  geojson_url : url to load data from
 *  layer_add   : layer to add (optional)
 *  ul_add      : ul content to add (optional)
 */

function pm_Marker_Group(map){
    var o = this;               // obj shortcut
    this.marker_array=[];
    this.marker_group={}; 
    this.zindex = 5000;         // track marker on top
    this.map = '';              // optional map to apply layer to

    
    // init if map is set
    if(map){
      o.map = map;
    }
    
    /**
     * Load from url
     */
    this.load_url = function (url){
        $.getJSON( url, function(data) { 
            o.load_json(data);
        });
    }
    
    /** 
     * Load geojson
     */
    this.load_json = function(json){
        $.each(json.features, function (i) {                       
            o.add_markers(json.features[i]);
        });
        
        if(o.map){
            console.log('map');
            for( var key in o.marker_group){
                o.marker_group[key]= L.featureGroup(o.marker_group[key]).addTo(map); 
            }
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
    this.add_markers = function(json) {    
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
            pm_marker.bindPopup(content).on('click', o.pm_marker_to_top); // bind popup and click handler
        }

        o.marker_array.push(pm_marker);    // add to array, so we can track/fit bounds
        if(!o.marker_group[name]){        // also add to the group object so we can disable seperatly
           o.marker_group[name] = [];
        }
        o.marker_group[name].push(pm_marker);

    }
    
    /**
     * Function: move this pm_marker to the top
     */
    this.marker_to_top = function() {
        this.setZIndexOffset(o.zindex);
        o.zindex++;
    }
    
} // pm_Marker_Group