/* Object to hold/control each marker layer
 *  map         : map element
 *  checkbox_id : id of checkbox control
 *  geojson_url : url to load data from
 *  layer_add   : layer to add (optional)
 *  ul_add      : ul content to add (optional)
 */

function pm_Marker_Layer(map, checkbox_id, geojson_url, layer_add, ul_add){
    var o = this;                                               // obj shortcut
    this.layer = L.layerGroup();                                // layer 
    this.checkbox = $('#'+checkbox_id+':checkbox');             // control element
    this.url = geojson_url;                                     // url to geojson data
    

    /* Update json url to include location filter from hash
     */
    this.filtered_url = function(){
       return o.url+'&locations='+pm_filter_hash();        
    };
    
    /* Update checkbox 
     * Load new content from json
     */
    this.update_checkbox = function(){
        o.layer.clearLayers(); // clear any old markers
        
        if( o.checkbox.is(':checked') ){                     // already open
            o.load_data();
        }
        else{             
            var ul = o.checkbox.parent('label').next('ul');  // grab ul to fill with types

            o.checkbox.one( 'click',function(){              // first click
                ul.slideDown();
                o.load_data();
            });

            o.checkbox.change(function() {                   // further click simply show/hide
                  if (this.checked) {	
                        o.layer.addTo(map);
                        ul.slideDown();
                  } else{
                        o.layer.remove();
                        ul.slideUp();
                  }
            });
        }
    }; // update_checkbox
        
    /* Ajax call to placemarks json file 'url'
     * Clears/creates markers in 'layergroup'
     * Reloads html for 'id' 
     */
    this.load_data = function(){
        var group = [];
        var unique = [];

        var ul = o.checkbox.parent('label').next('ul');                     // grab ul to fill with types
        ul.html('<li>Loading...</li>');                                     // remove old data. Loading notice.
        $.getJSON( o.filtered_url(), function(data) { 
            if(data.features.length == 0){
                ul.html('<li>...None found</li>');
            }
            else{
                ul_add ? ul.html(ul_add) : ul.html('');
                $.each(data.features, function (i) {                        // for each placemarks[i] 
                        o.build_marker(data.features[i], group);			// build osm marker and add to group
                        o.unique_markers(data.features[i], unique);        // add to unique based on type
                });

                o.layer.addLayer( L.featureGroup(group) );	                // create layer group
                if(layer_add){                                              // if added layer
                  o.layer.addLayer(layer_add);  
                }
    
                o.layer.addTo(map);			                            // add to map

                for(var i=0; i<unique.length; i++){                         // build types list
                    var name = unique[i]['properties']['name'];
                    var src = unique[i]['properties']['icon'];
                    ul.append('<li class="icon"><img src="'+src+'" alt="icon for '+name+'" class="graphic">'+name+'</li>');                
                }
            }
        }).fail(function( jqxhr, textStatus, error ) {
            var err = textStatus + ", " + error;
            log( "Request Failed: " + err + jqxhr.responseText);
        });
    }; // load_data
    
    /**
     * function: create placemarks on map and a legend that triggers info window.
     * Closure is important here to tie each pm_marker to each <li></li> in legend.
     * placemarksArray: json placemarks for this floor json from admin interface
     * i: position in placemarksArray
     */
    this.build_marker = function(array, group) {
        var latlng = array['geometry']['coordinates'];
        var pm_name = array['properties']['title'];
        var pm_src = array['properties']['icon'];
        var pm_desc = array['properties']['description'];
        var pm_loc = array['properties']['location_alt'];

        // are all required items available?
        if(!(latlng && pm_name && pm_src)){
            return;
        }

        // pm_markers
        var pm_icon = L.Icon.extend({
            options: {
                iconSize:     [24, 34],
                iconAnchor:   [14, 34],
                popupAnchor:  [0, -30]
                //shadowUrl: 'leaf-shadow.png',
                //shadowSize:   [50, 64],
                //shadowAnchor: [4, 62],
            }
        });
        var icon = new  pm_icon({iconUrl: pm_src});

        // info window content
        var content = "<div class='window'><h4>" + pm_name + "</h4>";
        if(pm_loc) { content += "<p><em>" + pm_loc + "</em></p>"; }
        if(pm_desc) { content += "<p>" + pm_desc + "</p>"; }

        var pm_marker = L.marker(latlng, {
            icon: icon,                     // set icon 
            zIndexOffset:0,                 // allow us to play with zindex to keep in icon in front
            riseOnHover: true,              // bring to front on hover
            riseOffset:10000,               // we're messing with zindex on click, so this number needs to be much bigger than  pm_zindex
        }); 
        pm_marker.bindPopup(content).on('click', pm_marker_to_top); // bind popup and click handler

        group.push(pm_marker);  // add to group  
    }; // build_marker
    
    /**
     * function: add to unique array, is not already there (baesd on type)
     */
    this.unique_markers = function(array, unique) {
        var pm_type = array['properties']['type'];
        for(var i=0; i<unique.length; i++){   
            if(unique[i]['properties']['type'] === pm_type){
                return;
            }
        }
        unique.push(array);  // add to group 
    }
    
}; //pm_Marker_Layer
