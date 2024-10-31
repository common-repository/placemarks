=== Plugin Name ===
Contributors: gabrielmcgovern
Donate link: http://www.dreamhost.com/donate.cgi?id=17157
Tags: placemarks, placemark, map, maps, places, mark, marker, google maps, open street maps, osm, floorplans
Requires at least: 3.0
Tested up to: 5.2.2
Stable tag: 2.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow authors to easily manage placemarks and embed custom maps.

== Description ==
Create maps, drop pins, and so much more!

This plugin adds a new `placemark` post type that allows author to create and update map content. 

The author interface includes:

**Place**

* An interactive map to drop and move pins
* A way to edit GPS by hand and make use of the geolocation on your mobile phone
* An editable set of drop-down lists for picking locations
* An alternative text area to describe the location

**Mark**

* An editable drop-down of marker types and associated icons
* An optional title
* Optional bubble text
* Optional link

** Optional ** 
The locations and marker types can be set by an administrator. This allow the you to:

* Customize the types of markers includeing the image
* Set a custom taxonomy for locations, allowing authors to zoom in to campus, building, floor, etc...
* Add image overlays to each location, including floorplans. 

To embed the maps a simple short code is used. You can limit which type of placemarks will show up on each map.   

A basic API allows you to pull out the data for other mapping systems. 

== Installation ==

1. Upload the `placemarks` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create some new placemarks
1. Include the shortcode [placemarks] on any page or post

== Frequently Asked Questions ==

= What can the shortcode do? =

[placemarks types="list of type names" ids="list of placemarker ids" lat=# lng =# zoom=# width="" height="" alt=true/false]

Everything after `placemark` is optional:

* `types`: String. List of types slugs to include on the map "type, foo bar" (shows all by default)
* `locations`: String. List of location slugs to include on the map "location-1,location-2". can be used with types to further limit. 
* `ids`: String. List of placemarker ids - handy if you only want 1, or 2. Can be used with types and locatiosn to help limit. 
* `lat`: Number. Use lat+lng+zoom to choose an initial map view (defaults to show all pins)
* `lng`: Number. Use lat+lng+zoom to choose an initial map view (defaults to show all pins)
* `zoom`: Number. Use lat+lng+zoom to choose an initial map view (defaults to show all pins)
* `width`: String. Change the width of the map (default '100%') 
* `height`: String. Change the height of the map (default '400px')
* `alt`: True/False. A text list of all the markers shows under th map by default. This can be used to turn it off. 

= How do I edit the locations and types drop-downs? =

Go to `Settings` -> `Placemarks`. Here you can use JSON to create custom lists. For example:

**Marker Types (JSON)**: `name` and `src` are required

	{ "types": [
  		{"name":"Default", "src":"http://www.yoursite.com/default.png"},
  		{"name":"Hot", "src":"http://www.yoursite.com/hot.png"}
        ]
    }
    
**Locations (JSON)**: `name` and `slug` are required. slug should always be unique

	{"locations": [
  		{"name":"Oregon","slug":"or"},
    	{"name":"Washington","slug":"wa"}
        ]
    }
    
Optionally, you can also include: `lat`, `lng`, `zoom`. Together, these control the map when selected in the admin interface.

	{"locations": [
  		{"name":"Oregon","slug":"or","lat":45.563282,"lng":-122.673457,"zoom":17},
    	{"name":"Washington","slug":"wa","lat":45.563838,"lng":-122.672342,"zoom":19}
        ]
    }
  
Each location can also include `locations`. This can be used to create hierarchies of select lists!

	{"locations": [
  		{"name":"Oregon","slug":"or", "locations":[
        	{"name":"Portland","slug":"pdx"},
            {"name":"Bend","slug":"bend"}
       		]
        },
    	{"name":"Washington","slug":"wa", "locations":[
        	{"name":"Seattle","slug":"sea"}
      		]
        }
        ]
    }

== Screenshots ==

1. Demo of how the plugin works with custom locations and marker images
1. How a map might look on a post page
1. Creating a new 'Placemark'
1. With the settings you can customize the types of placemarks, locations and icons available.
1. And then we embed a map!

== Changelog ==

= 3.02 = 
* Disabled error reporting display
* Updated calls to API using rest_url() - to work with non-standard WordPress installs

= 3.01 =
* Multiple maps on a single page without conflict

= 3.00 =
* Reformat code using the WP Boilerplate format
* Switched from Google Maps to OpenStreetMaps
* New network settings available in multisite
* Uses the WordPress REST API (discontinue old api)
* Fixed xss vulnerability in old api (by removing it :)
* Admin data pulled direclty from api

= 2.1.0 =
* Remove/hide preview button
* Remove "view" message on update
* Shortcode: allow "ids" to only show 1 or 2
* Update icon on map from default
* Add slug to location
* Rename script/style files to make sence
* Create data export page
* Limit by location slug in shortcode

= 2.0.3 =
- Basic functions in place
- All placemarks: Show location names instead of slugs 
- Embedded map: Add "edit" link to markers displayed on map when user is logged in for quick editing!

= 2.0.2 =
* Bugfix: Only enqueue js on placemark admin pages
* Settings: Allow locations in locations (via JSON)

= 2.0.1 =
* Bug fix: 2.0.0 was missing function files
* Shortcode: set defaults for gps and zoom

= 2.0.0 =
* Feature: Setting can now be changed at the network level
* Feature: Basic API for pulling data: /placemarks-data/

= 1.1.0 =
* Bug fix: Lots of bug fixes
* Update: Marker types should now include slug. Allows you to change the name without breaking everything. Will work fine without slug for now. 
* Update: All placemarks list view updated. 
* Feature: Icon on map updates as you change type. 
* Feature: Allow for overlay images in Locations JSON!
* Feature: Data page /placemarks-data/ to act as api for external maps

= 1.0.3 =
* Bug fix: Only set up map if map_canvas is found on page

= 1.0.2 =
* Bug fix: Fixed comment error on pages with shortcode

= 1.0.1 =
* Bug fix: Only enqueue js on placemark admin pages
* Bug fix: Fix error on pages with comments
* Feature: Add edit link to each placemark on map

= 1.0.0 =
* First version to be released. 

== Upgrade Notice ==

= 1.0.3 =
* Bug fix

= 1.0.2 =
* Bug fix

= 1.0.1 =
* Bug fixes. New edit links on map.

= 1.0.0 =
Seems stable enough, but only has basic features. 
