/**
 * Function: update gui when map changes
 */
function pm_update_gui(map, campus, bldg, floor, room){
    var loc_campus = campus ? locationJsonFindSlug(placemarks_locations_json.locations, campus) : null; // find this campus in our json
    var loc_bldg = bldg ? locationJsonFindSlug(loc_campus.locations, bldg) : null;
    var loc_floor = floor ? locationJsonFindSlug(loc_bldg.locations, floor) : null;
    var loc_room = room ? locationJsonFindSlug(loc_floor.locations, room) : null;

    // update selects/buttons

    locationJsonToSelect(placemarks_locations_json.locations, 'pm-select-campus', 'campus');         // always show campus
    $('#where-selects .where-select').has('#pm-select-campus').show(); 
    
    if(campus){
        $('#pm-select-campus').val(campus);                                                 // set campus
        locationJsonToSelect(loc_campus.locations, "pm-select-building");                   // create building drop down
        $('#where-selects .where-select').has('#pm-select-building').show(); 
        
        // parking default - only when switching campus
        var pt = $('#control-parking');
        if( !bldg && pt.is(':not(:checked)')){
            $('#control-parking').click();
        }
    }
    else{
        $('#where-selects .where-select').has('#pm-select-building').hide(); 
    }
    
    if(bldg){
        $('#pm-select-building').val(bldg);                                                 // set bldg
        locationJsonToSelect(loc_bldg.locations, 'pm-select-floor', 'floor');               // create floor drop down
        locationJsonToButtons(loc_bldg.locations, 'pm-buttons-floor');                      // floor buttons
        $('#where-selects .where-select').has('#pm-select-floor').show();     
        pm_layer_floorplan.update(bldg);                                                    // update floorplan (outside?)
    }
    else{
        $('#where-selects .where-select').has('#pm-select-floor').hide(); 
        locationJsonToButtons(null, 'pm-buttons-floor');                                    // clear buttons
        pm_layer_floorplan.update();                                                        // clear floorplan
    }
    
    if(floor){
        $('#pm-select-floor').val(floor);                                                   // set floor
        pm_layer_floorplan.update(floor);                                                   // update floorplan
        var rooms = locationJsonToSelect(loc_floor.locations, 'pm-select-room');            // create room drop down, did we fin any?
        if(rooms){
            $('#where-selects .where-select').has('#pm-select-room').show();  
        }
        else{
            $('#where-selects .where-select').has('#pm-select-room').hide();
        }
        //pm_layer_buildings.removeFrom(pm_map);
    }
    else{
        $('#where-selects .where-select').has('#pm-select-room').hide(); 
        //pm_layer_buildings.addTo(pm_map);
    }
    
    /*
    if(room){
        $('#pm-select-room').val(room);                                                     // show floor
        pm_layer_rooms.clearLayers();                                                       // reset
        //pm_map.setView([loc_room.lat,loc_room.lng],20);                                       // move map 

        var circle = L.circle([loc_room.lat,loc_room.lng], {
            stroke: false,
            fillColor: '#008099',
            fillOpacity: 0.4,
            radius: 4,
        })

        // content
        var pm_desc = loc_room['description'];
        var content = "<div class='window'><h4>" + loc_room.name + "</h4>";
        if(pm_desc) { content += "<p>" + pm_desc + "</p>"; }

        //popup w/ offset
        var popup = L.popup({ offset: L.point(0, -25) }).setContent(content);
        var room_circle = circle.addTo(pm_layer_rooms);
        room_circle.bindPopup(popup).openPopup();                                           // bind popup, open by default
    }
    else{
        pm_layer_rooms.clearLayers();
    }
    */
    
    // zoom map
    if(room){
        map.setView([loc_room.lat,loc_room.lng], 20);
    }
    else if((floor || bldg) && loc_bldg.lat &&loc_bldg.lng){
        map.setView([loc_bldg.lat,loc_bldg.lng], loc_bldg.zoom);
    }
    else if(campus){
        map.setView([loc_campus.lat,loc_campus.lng], loc_campus.zoom); 
    }
        
    
    // update markers and marker controls
    pm_layer_controls();
    
}



/* Alternative way to select a building 
pm_map.addEventListener('click', function(ev) {
   var lat = ev.latlng.lat;
   var lng = ev.latlng.lng;
   var bldg = locationJsonFindgGps(locationsArray.locations, lat, lng);

    // would require floor selet to be ready :(
   if(bldg){
       $('#pm-select-floor').val(bldg);
       $('#pm-select-floor').change();
   }
});*/
    


/* function to build campus and building selects	
 * json: location json
 * id: id of select in which to build	
 * mod: optional output modifier
 */
function locationJsonToSelect(json, id, mod) {
	var default_out = '<option value="">None found</option>';
    var out = default_out;
    if (json && json.length) {
        var prev = json[0].slug.split('-');     // figure out parent location for option "choose"
        prev.pop();
        prev = prev.join('-');        
		out = '<option value="'+prev+'">Choose</option>';
		$.each(json, function (i) {
				out += '<option value="' + json[i].slug + '">' + json[i].name + '</option>';
		});
	}
    $('#' + id).html(out);
    
    if(mod){
        if (mod == 'campus') {
            /*add optgroup after first four campus - assumes the first four are our campuses */
            $('select#'+id+' option').slice(1, 5).wrapAll('<optgroup label="campuses">');
            $('select#'+id+' option').slice(-($('select#campus option').size() - 5)).wrapAll('<optgroup label="centers">');
        }
        else if(mod == 'floor'){
            $('select#'+id+' option:contains("0")').text('Basement');
            $('select#'+id+' option:contains("1")').text('1st floor');
            $('select#'+id+' option:contains("2")').text('2nd floor');
            $('select#'+id+' option:contains("3")').text('3rd floor');
            $('select#'+id+' option:contains("4")').text('4th floor');
            $('select#'+id+' option:contains("5")').text('5th floor');
            $('select#'+id+' option:contains("6")').text('6th floor');
        }
    }

    return (out==default_out) ? 0 : 1; // did we find anything?
}

/* function to build buttons for floors
 * json: location json
 * id: id of div in which to build 
 */
function locationJsonToButtons(json, id) {
    var out = '';
	if (json && json.length) {
		$.each(json, function (i) {
            var n = json[i].name;
            if( n=='0'){n='B'}
            out += '<button type="button" value="' + json[i].slug + '">' + n + '</button>';
		});
		$('#' + id).html(out);
	} else {
		$('#' + id).html('');
	} 
}

/* function to build campus and building selects
 * json: geojson
 * id: id of select in which to build	
 *
function geoJsonToSelect(json, id) {
    var out = '<option class="none" value="">None found</option>';
	if(!json.features.length) {
        return false; // none found
    }
    else{
		out = '<option value="">Choose</option>';
		$.each(json.features, function (i) {
                var slug = pm_filter_hash() +'-'+json.features[i].properties.title.split(" ").slice(-1); // grab last 'word'
				out += '<option value="' + slug + '">' + json.features[i].properties.title + '</option>';
		});
	}
    $('#' + id).html(out);
    return true;
}
// */


/**
 * Function: Drill thru json
 * var l: location JSON
 * var slug: slug we are looking for
 */
function locationJsonFindSlug(l, slug) {
	var thisLocation = null;
	$.each(l, function (i) {
		if (l[i].slug == slug) { // we found it
			thisLocation = l[i];
			return false;
		} else if (l[i].locations) { // or keep looking
			thisLocation = locationJsonFindSlug(l[i].locations, slug);
			if (thisLocation) {
				return false;
			}
		}
	});
    
	return thisLocation;
}


/**
 * Function: find building by gps
 * var l: location JSON
 * var slug: slug we are looking for
 *
function locationJsonFindgGps(json,lat,lng) {
    var thisLocation = null;
	$.each(json, function (i) {
		if (!json[i].n && json[i].locations) { // no nsew = not a building, keep going
			thisLocation = locationJsonFindgGps(json[i].locations, lat,lng);
			if (thisLocation) {
				return false;
			}
		} 
        else if (json[i].n>lat && json[i].s<lat && json[i].e>lng && json[i].w<lng) { //inside bound
            thisLocation = json[i];
            return false;
		}
        else{

            //log(json[i].slug+ " "+json[i].n +" "+ json[i].s+" "+json[i].e+" "+json[i].w+" "+lat+" "+lng)
        }
	});
    
	return thisLocation;
}
// */

/**
 * Function: get filter hash
 * var l: location JSON
 * var slug: slug we are looking for
 */
function pm_filter_hash(){
       var hash = '';
       var hash_arr = window.location.hash.replace("#", "").split("-"); // get hash from url
       hash += hash_arr[0] ? hash_arr[0] : '';              // add campus
       hash += hash_arr[1] ? '-'+hash_arr[1] : '';          // add building
       hash += hash_arr[2] ? '-'+hash_arr[2] : '';          // add floor
       return hash;
}