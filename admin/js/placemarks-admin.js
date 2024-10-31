(function( $ ) {
	'use strict';
    
    /**
     * Required: 
     * jquery, library/overlay-layer.obj.js
     * vars: placemarks_tiles, placemarks_locations_json, placemarks_types_json, placemarks_locations_meta, placemarks_marker_meta 
     * #pm_map element on page
     */

    var pm_icon;
    var pm_map;                            // map
    var pm_marker;                         // marker
    var pm_floorplan;                      // floorplan, obj

    // defaults
    var pm_default_center = [45.517499,-122.676862]; // Portland
    var pm_default_zoom = 11;
    
    // layers
    var pm_layer_floorplan;    

   $(function() {
        /* set up map */
        pm_map = L.map('pm-map').setView(pm_default_center, pm_default_zoom);
        L.tileLayer(placemarks_tiles, {
            maxZoom: 20,
            attribution: '<a href="http://www.openstreetmap.org/copyright">&copy; OpenStreetMap</a>, <a href="https://www.mapbox.com/about/maps/">&copy; Mapbox</a>: <a href="https://www.mapbox.com/map-feedback/" target="_blank">Improve this map</a>',
            id: 'mapbox.streets'
        }).addTo(pm_map);

        /* marker */
        pm_icon = L.icon({
            iconUrl: placemarks_types_json["types"][0]['src'], // first in list
            className:'pm-icon',
            iconSize:     [28, 37],
            iconAnchor:   [14, 37],
        });
        pm_map.on('click', function(e) {
            pm_single_marker(e.latlng);
        });
       
        /* if preset */ 
        var first_run = 1;
        var lat = $('#placemarks-lat').val()|| '';
        var lng = $('#placemarks-lng').val() || '';
        if (lat!=="" && lng!=="") {
            pm_single_marker(L.latLng(lat,lng));
            pm_map.setView(L.latLng(lat,lng), 20); // zoom to marker
        }

        /* marker selects */
        markerJsonToSelect();
        updateMarkers(placemarks_marker_meta); // on load
        jQuery('#placemarks-type').change(function() { // when icon changes
            updateMarkers(jQuery(this).val());
        });
       
        /* location selects */
        locationJsonToSelect(placemarks_locations_json["locations"]); // build #placemarks-location-selects
       
        pm_layer_floorplan = new pm_Overlay_Layer(pm_map,placemarks_locations_json);       // add/remove floorplans with this layer
       
        jQuery('#placemarks-location-selects').on("change", 'select.placemarks-locations-select', function() { 		
            var thisLoc = locationJsonFindSlug(placemarks_locations_json["locations"], jQuery(this).val()); 		// find in json
            jQuery(this).nextAll().remove(); 																		// remove extra select inputs
            if (thisLoc) {
                locationJsonToSelect(thisLoc["locations"]); 														// rebuild selects
                if(thisLoc["lat"] && thisLoc["lng"] && !first_run) {                                                // don't zoom on first run
                    // move and zoom map? 
                    var latlng = L.latLng(thisLoc["lat"],thisLoc["lng"]);
                    pm_map.setView(latlng,thisLoc["zoom"]);
                }
                pm_layer_floorplan.update(thisLoc['slug']); 
            }
        });
       
        // are there inital values?
        updateLocationSelect(0); 
       
        /* html5 location */
        jQuery("#mapgps").hide();
        if(!!navigator.geolocation){
            jQuery("#mapgps").show().click(function() {
                navigator.geolocation.getCurrentPosition(function(position) {    
                    var latlng = L.latLng(position.coords.latitude, position.coords.longitude);
                    pm_single_marker(latlng);
                    pm_map.panTo(latlng); // set center
                });
            });
        }    
       
       first_run = 0;
   });


    /**
     * Map functions
     */

    /* create a single, dragable marker */
    function pm_single_marker(latlng){
        if(!pm_marker){ // first click
            pm_marker = new L.marker(latlng, {id:'pm_marker', draggable:'true', icon:pm_icon});
            pm_marker.on('dragend', function(e){ // when dragged
                var latlng = pm_marker.getLatLng();
                pm_update_gps(latlng);
            });
            pm_map.addLayer(pm_marker);
        }
        pm_marker.setLatLng(latlng); // new location
        pm_update_gps(latlng);
    }

    /* update gps field */
    function pm_update_gps(latlng){
        // edit GPS on form
        jQuery('#placemarks-lat').val(latlng.lat.toFixed(6));
        jQuery('#placemarks-lng').val(latlng.lng.toFixed(6));
    }

    /**
     * Location selection functions
     */

    /* Update the select boxes starting at index 's' */
    function updateLocationSelect(s) {
        if (placemarks_locations_meta && jQuery('#placemarks-location-selects select:eq(' + s + ')').size()) { 		// if select box available
            jQuery('#placemarks-location-selects select:eq(' + s + ') option').each(function() {
                for (var i = 0; i < placemarks_locations_meta.length; i++) {
                    if (jQuery(this).val() == placemarks_locations_meta[i]) { 										// we have a match
                        jQuery(this).attr('selected', 'selected'); 													// set option as selected
                        placemarks_locations_meta.splice(i, 1); 													// remove value from array (assumes slugs are unique)
                        jQuery('#placemarks-location-selects select:eq(' + s + ')').change(); 						// trigger change to create next select
                        updateLocationSelect(s + 1); 																// and try to set that one
                        break;
                    }
                }
            });
        }
    }

    
    /* Take marker json, create a select, update map */
    function markerJsonToSelect() {
        var out = '';
        var m = placemarks_types_json['types'];
        jQuery.each(m, function(i) {
                var slug = m[i]["slug"];
                var name = m[i]["name"];
                var selected = (slug==placemarks_marker_meta) ? 'selected' : ''
                out += '<option value="' + slug + '" ' + selected + '>' + name + '</option>';
        });
                
        jQuery('#placemarks-type').html(out);
    }    
    
    /* Take a location json, create a select. var l: location JSON */
    function locationJsonToSelect(l) {
        if (l) {
            var num = jQuery('#placemarks-location-selects select').length + 1; 									// which one will this be (for id)
            var out = '<select class="placemarks-locations-select" id="placemarks-locations-' + num + '" name="placemarks-locations[]"><option></option>';
            jQuery.each(l, function(i) {
                out += '<option value="' + l[i]["slug"] + '">' + l[i]["name"] + '</option>';
            });
            out += '</select>';
            jQuery('#placemarks-location-selects').append(out);
        }
    }

    /* Drill thru json. var l: location JSON. var slug: the slug that we are looking for */
    function locationJsonFindSlug(l, slug) {
        var thisLocation = null;
        jQuery.each(l, function(i) {
            if (l[i]["slug"] == slug) { 												// we found it
                thisLocation = l[i];
                return false;
            } else if (l[i]["locations"]) { 											// or keep looking
                thisLocation = locationJsonFindSlug(l[i]["locations"], slug)
                if (thisLocation) {
                    return false;
                }
            }
        });
        return thisLocation;
    }

    /* Show the marker icon. var marker: marker slug */
    function updateMarkers(slug) {
        jQuery.each(placemarks_types_json["types"], function(j) {
            var thisSlug = placemarks_types_json["types"][j]["slug"] ? placemarks_types_json["types"][j]["slug"] : placemarks_types_json["types"][j]["name"]; // prior to 1.0.4 slug was not required
            if (slug == thisSlug) {
                //jQuery('#placemark-marker-image').css("background-image", "url('" + placemarks_types_json["types"][j]["src"] + "')");
                jQuery('#placemark-marker-image').attr('src',placemarks_types_json["types"][j]["src"]);
                slug = null;
                pm_icon = L.icon({
                    iconUrl: placemarks_types_json["types"][j]['src']
                });
                pm_marker.setIcon(pm_icon);
            }
        });
        if (slug !== null) {
            jQuery('#marker-image').css("background-image", ""); 				// none found
        }
    }


})( jQuery );
