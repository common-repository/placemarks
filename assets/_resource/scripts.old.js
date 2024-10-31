	/**
 * @package Placemarks
 * @author Gabriel Nagmay <gabriel.nagmay.com>
 * @link http://wordpress.org/extend/plugins/placemarks/
 */
 
 
 
 
	//<![CDATA[
	
		// global
		var marker = null;
		
		/*--------------- Floorplan overlay constructor stuff  - can this be in floorplan-data.js-----------*/
		var overlay;
		floorplanOverlay.prototype = new google.maps.OverlayView();
		// constructor
		function floorplanOverlay(bounds, image, map) {
		  this.bounds_ = bounds;
		  this.image_ = image;
		  this.map_ = map;
		  this.div_ = null;
		  this.setMap(map);
		}
		floorplanOverlay.prototype.onAdd = function() {
		  var div = document.createElement('div');
		  div.style.borderStyle = 'none';
		  div.style.borderWidth = '0px';
		  div.style.position = 'absolute';
		  var img = document.createElement('img');
		  img.src = this.image_;
		  img.style.width = '100%';
		  img.style.height = '100%';
		  img.style.position = 'absolute';
		  div.appendChild(img);
		  this.div_ = div;
		  var panes = this.getPanes();
		  panes.mapPane.appendChild(div);
		}
		floorplanOverlay.prototype.draw = function() {
		  var overlayProjection = this.getProjection();
		  var sw = overlayProjection.fromLatLngToDivPixel(this.bounds_.getSouthWest());
		  var ne = overlayProjection.fromLatLngToDivPixel(this.bounds_.getNorthEast());
		  var div = this.div_;
		  div.style.left = sw.x + 'px';
		  div.style.top = ne.y + 'px';
		  div.style.width = (ne.x - sw.x) + 'px';
		  div.style.height = (sw.y - ne.y) + 'px';
		}
		floorplanOverlay.prototype.onRemove = function() {
		  this.div_.parentNode.removeChild(this.div_);
		  this.div_ = null;
		}

		
		// update select lists.
		jQuery(document).ready(function(){
			initialize();	// map
			
			// placeMeta and markerMeta available
			//alert (markerMeta['m04']['name']);
			
			jQuery('#'+placeMeta['m04']['name']).change(function() { updateBuildings( jQuery(this).val() ); });		// campus updated
			jQuery('#'+placeMeta['m05']['name']).change(function() { updateFloors( jQuery('#'+placeMeta['m04']['name']).val(), jQuery(this).val() ); });		// building updated
		    jQuery('#'+placeMeta['m06']['name']).change(function() { changeFloor() }); // floor updated
		  	
		 
		  
			// are there already values
			if( cIn!="" ){ 
				updateBuildings(cIn, false, true);												// selected campus set w/ php
			}
			if( bIn!="" ){ 
				updateFloors(cIn, bIn);
				jQuery('#'+placeMeta['m05']['name']).val(bIn); 	// set selected building
				
			}
			if( fIn!=""){
				jQuery('#'+placeMeta['m06']['name']).val(fIn); 	// set selected floor
				changeFloor(); // show floorplan
			}
			
			
			//update icon
			jQuery('#pcc-placemarker-type').change( function(){
				updateMarkers( jQuery(this).val() );
			});
			updateMarkers( jQuery('#pcc-placemarker-type').val() );

		});
		
		
		function updateMarkers(marker){
			jQuery.each(tArr["types"], function(j) {
				if( marker==tArr["types"][j]["name"]){
					jQuery('#marker-image').css("background-image","url('"+tArr["types"][j]["src"]+"')" );	
					marker=null;
				}
			});
			if(marker!=null){
				jQuery('#marker-image').css("background-image","" );	 // none found
			}
			
		}
		
		function updateBuildings(campus, resetFloors, first){
				if(typeof(resetFloors)==='undefined') resetFloors = true;
				var bArr = new Array();;
				// find buildings for a campus
				for(var i=0; i<lArr.length; i++) {
					if(lArr[i][1] == campus){
						bArr = lArr[i][2];
						break;
					}
				}																		
				// update available rooms
				var bOut = "";
				for(var j=0; j<bArr.length; j++) {
					bOut = bOut+'<option value="'+bArr[j][1]+'">'+bArr[j][0]+'</option>';
				}
				jQuery('#'+placeMeta['m05']['name']).html(bOut);
				if(resetFloors){
					updateFloors(campus, bArr[0][1]); 										// also update floors to listed building
				}
				
				// move map ?
				var latlng = null;				
				switch(campus){
					case "ca": 
						latlng =  new google.maps.LatLng(45.56329, -122.67328);
						break;	
					case "rc": 
						latlng =  new google.maps.LatLng(45.565828, -122.857555);
						break;	
					case "sy": 
						latlng =  new google.maps.LatLng(45.438565, -122.729765); 	
						break;	
					case "se": 
						latlng =  new google.maps.LatLng(45.506241, -122.579000); 
						break;	
					case "climb": 
						latlng =  new google.maps.LatLng(45.511519, -122.665572); 
						break;
					case "dc": 
						latlng =  new google.maps.LatLng(45.517350, -122.674761); 
						break;	
					case "hc": 
						latlng =  new google.maps.LatLng(45.520135, -122.978103); 
						break;	
					case "mc": 
						latlng =  new google.maps.LatLng(45.563215, -122.619695); 
						break;	
					case "wc": 
						latlng =  new google.maps.LatLng(45.517504, -122.868497); 
						break;	
				}
				if(latlng && !first){ // don't fire when loading first time with a marker set by php
					map.setZoom(17);
					map.panTo(latlng); // set center
					
				}
				
		}
		
		function updateFloors(campus, building){
		  var bArr, fArr = new Array();
		  // find buildings, then floors for a campus
		  for(var i=0; i<lArr.length; i++) {
			  if(lArr[i][1] == campus){
				  bArr = lArr[i][2];  													
				  for(var j=0; j<bArr.length; j++) { 									
					  if(bArr[j][1] == building){										
						  if(bArr[j].length > 2){										// No rooms, leave blank array
							  fArr = bArr[j][2];
						  }
						  break;
					  }
				  }
				  break;
			  }
		  } 																			
		  // update available floors
		  var fOut = "";
		  for(var k=0; k<fArr.length; k++) {
			  fOut = fOut+'<option value="'+fArr[k]+'">'+fArr[k]+'</option>';
		  }
		  jQuery('#'+placeMeta['m06']['name']).html(fOut);
		  
		  // now, since a floor was preselected
		  changeFloor();
		}
		
		function changeFloor(){
			// add overlay for building (will need to be updated for other campus locations (uses floorplan-data.js)
			//get values
			var cVal = jQuery("select#pcc-placemarker-campus").val(); 
			var bVal = jQuery("select#pcc-placemarker-building").val(); 
			var fVal = jQuery("select#pcc-placemarker-floor").val(); 
		  	var cbf = cVal+"-"+bVal+"-"+fVal;
			
			if(cVal == "sy") {
				for(var i = 0; i < sylvania.length; i++) {
					if(sylvania[i][1] == cbf) {
						var north = sylvania[i][2];
						var east = sylvania[i][3];
						var south = sylvania[i][4];
						var west = sylvania[i][5];
						var neBound = new google.maps.LatLng(north,east);
						var swBound = new google.maps.LatLng(south,west);
						var bounds = new google.maps.LatLngBounds(swBound, neBound);
						// set image source and apply to map
						var srcImage = 'http://www.pcc.edu/temp/map/images/floorplans/'+cbf+'.png'; 
						if (overlay != null) { overlay.setMap(null);};
						overlay = new floorplanOverlay(bounds, srcImage, map); // requires overlay junk!
						break;
					}
				}
			}
		}
		
		
		
		/* map stuff based on http://www.geocodezip.com/v3_example_click2add_infowindow.html */
		var map = null;
		var marker = null;
		
		/*
		//var window = new google.maps.InfoWindow({ size: new google.maps.Size(270,20) });
		
		// A function to create the marker and set up the event window function 
		function createMarker(latlng, name, html) {
			var contentString = html;
			var marker = new google.maps.Marker({
				position: latlng,
				map: map,
				zIndex: Math.round(latlng.lat()*-100000)<<5
				});
		
			google.maps.event.addListener(marker, 'click', function() {
				infowindow.setContent(contentString); 
				//infowindow.open(map,marker);
				});
			google.maps.event.trigger(marker, 'click');    
			return marker;
		}
		*/
		 
		function setMarker(latlng){
			if (!marker) {
				console.log("new marker");
				// create marker
				marker = new google.maps.Marker({
					position: latlng,
					draggable: true,
					map: map,
					zIndex: Math.round(latlng.lat()*-100000)<<5
				});
			    // listen for drag 
				google.maps.event.addListener(marker, 'dragend', function(event) {
					marker = setMarker(event.latLng);
					console.log("drag");
		  		});	
			}
			else{
				console.log("position changed");
				marker.setPosition(latlng);
			}
			
			// edit GPS on form
			jQuery('#'+placeMeta['m01']['name']).val( latlng.lat() );
			jQuery('#'+placeMeta['m02']['name']).val( latlng.lng() );
			
			
			
			return marker;
		}
		
		function initialize() {
			
			// some style for the map
			var pccStyle = [
				{ "featureType": "all", "elementType": "labels", "stylers": [ { "visibility": "off" } ] },
				{ "featureType": "poi.park", "elementType": "geometry", "stylers": [ { "visibility": "on" }, { "saturation": -34 }, { "lightness": 21 } ] },
				{ "featureType": "poi.park", "elementType": "labels.text", "stylers": [ { "visibility": "on" } ] },
				{ "featureType": "poi.park", "elementType": "labels.text.fill", "stylers": [ { "color": "#93a294" } ] },
				{ "featureType": "poi.park", "elementType": "labels.icon", "stylers": [ { "visibility": "on" }, { "saturation": -34 }, { "lightness": 21 } ] },
				{ "featureType": "poi.school", "elementType": "geometry", "stylers": [ { "visibility": "off" } ] },
				{ "featureType": "road", "elementType": "geometry", "stylers": [ { "visibility": "on" }, { "saturation": -36 }, { "lightness": 19 } ] },
				{ "featureType": "road", "elementType": "labels", "stylers": [ { "visibility": "on" } ] },
				{ "featureType": "road", "elementType": "labels.text.fill", "stylers": [ { "color": "#808080" } ] },
				{ "featureType": "road", "elementType": "labels.text.stroke", "stylers": [ { "saturation": -36 }, { "lightness": 19 } ] },
				{ "featureType": "road", "elementType": "labels.icon", "stylers": [ { "visibility": "on" }, { "saturation": -36 }, { "lightness": 19 } ] },
				{ "featureType": "water", "elementType": "geometry", "stylers": [ { "visibility": "on" } ] },
				{ "featureType": "water", "elementType": "labels", "stylers": [ { "visibility": "on" } ] }
			];
			var styledMapOptions = { name: "PCC"};
			
			var lat = placeMeta['m01']['value'];
			var lng = placeMeta['m02']['value'];
				
			  if(lat!="" & lng!=""){ // we have a marker already!
				  var myOptions = {
						zoom: 20,
						center: new google.maps.LatLng(lat,lng),
						mapTypeControl: true,
						//mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
						mapTypeControlOptions: { mapTypeIds: ['pcc', google.maps.MapTypeId.SATELLITE] },
						navigationControl: true,
						streetViewControl: false,
						mapTypeId: google.maps.MapTypeId.ROADMAP,
						draggableCursor:'crosshair'
				 }		  
			  }
			  else{
				var myOptions = { // brand new map
						zoom: 10,
						center: new google.maps.LatLng(45.48372492603276, -122.73582458496094),
						mapTypeControl: true,
						//mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
						mapTypeControlOptions: { mapTypeIds: ['pcc', google.maps.MapTypeId.SATELLITE] },
						navigationControl: true,
						streetViewControl: false,
						mapTypeId: google.maps.MapTypeId.ROADMAP,
						draggableCursor:'crosshair'
				 }		
			  }
			  // create the map
			  var pccMapType = new google.maps.StyledMapType (pccStyle, styledMapOptions);  // create styles
			  map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);	// create map	
			  map.mapTypes.set('pcc', pccMapType); // hook em up
			  map.setMapTypeId('pcc');			 // here too	
			  map.setTilt(0);
			  
			  if(lat!="" && lng!=""){
				 marker = setMarker( new google.maps.LatLng(lat,lng) ); 
		  }
	      // on click
		  google.maps.event.addListener(map, 'click', function(event) {
			marker = setMarker(event.latLng);
		  });
	  
		  
		  // html5 location
		  jQuery("#mapgps").hide();
		  if(!!navigator.geolocation) {
				
			
				navigator.geolocation.getCurrentPosition(function(position) {
					jQuery("#mapgps").show().click( function (){
						
						//alert(position.coords.latitude);
						//marker = setMarker(event.latLng);
						var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
						marker = setMarker( latlng );
					    // map.setZoom(17);
						map.panTo(latlng); // set center
						
					});
					
	
				});
				
			} else {
				// no support
			}
		}
		
	//]]>	
	
	
	
	
	
	
	/* PCC SPECIFIC */
	jQuery(document).ready(function(){
			createLabels(map);
			google.maps.event.addListener(map, 'bounds_changed', function() {
				var zoomLevel = map.getZoom();
				if (zoomLevel == 20) { fontSize = '0px'; };
				if (zoomLevel == 19) { fontSize = '150px'; };
				if (zoomLevel == 18) { fontSize = '50px'; };
				if (zoomLevel == 17) { fontSize = '30px'; };
				if (zoomLevel <= 16) { fontSize = '0px'; };
				jQuery(".building-labels").css("font-size",fontSize);
				//console.info( zoomLevel + ' ' + fontSize);
			});
		});
	