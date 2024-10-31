/* Object to hold/control each marker layer
 *  map         : map element
 *  json        : location jason
 */

function pm_Overlay_Layer(map, json){
    var o = this;                                               // obj shortcut
    this.layer = L.layerGroup();                                // layer 
    
    /* Clear
     */
    this.clear = function(){
        o.layer.clearLayers();                                  // reset layer
    }
    
    /* Update image overlay
     */
    this.update = function(slug){
        o.layer.clearLayers();                                  // reset layer
        o.add_overlay(json.locations,slug);                     // find and add overlay
    }
    
    /* Find and update image overlay
     * json : location json
     * slug : what slug are we looking for
     */
    this.add_overlay = function(json,slug){
        var found = false;
        for(var i=0; i<json.length; i++){
            if (json[i].slug == slug) {     // we found it
                var img = json[i]['overlay'];
                var n   = json[i]['n'];   
                var e   = json[i]['e'];
                var s   = json[i]['s'];
                var w   = json[i]['w'];
                if(img && n && e && s && w){
                    o.layer.addLayer( L.imageOverlay(img, [[n,w],[s,e]]) );
                    o.layer.addTo(map);
                }
                found = true;
            } else if(json[i].locations) { // or keep looking
                found = o.add_overlay(json[i].locations,slug);
            }
            if(found){                     // end recursion
                break;
            }
       }
       return found;
    }
    
}; // pm_Overlay_Layer