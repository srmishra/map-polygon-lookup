// if the distance in meters between first point and last click is lower than this
// then consider the polygon closed
var POINTS_MERGE_ACCURACY = 200;

// Radius of the earth in km
var EARTH_RADIUS = 6371; 

// pi
var PI = 3.1415;

// initial map options
var mapOptions = {
	zoom: 10,
    center: new google.maps.LatLng(35.1676692, 33.3710092), // this is digeni akrita street in Nicosia
    mapTypeId: google.maps.MapTypeId.TERRAIN
};


// global vars
var points = [];
var polygon;
var map;


/**
* converts from degrees to radians
* @var float deg
*
* @return float
*/
function deg2rad(deg)
{
	return deg * (PI/180);
}


/**
* calculates distance in meters between two lat/lng points
* @var google.maps.LatLng point1
* @var google.maps.LatLng point2
*
* @return float
*/
function calculateDistance(point1, point2)
{
	var dLat = deg2rad(point2.lat() - point1.lat());
	var dLng = deg2rad(point2.lng() - point1.lng());
	
	var a =	Math.pow( Math.sin(dLat / 2), 2)
			+ Math.cos(deg2rad(point1.lat())) * Math.cos(deg2rad(point2.lat()))
				* Math.pow( Math.sin(dLng / 2), 2);
				
	var c = 2 * Math.atan2( Math.sqrt(a), Math.sqrt(1 - a) );
	var d = EARTH_RADIUS * c * 1000;
	
	return d;
}

/**
* draws the polygon from the global array points
*/
function drawPolygon()
{
	if (polygon != null)
		polygon.setMap(null);
	
	polygon = new google.maps.Polyline({
		path: points,
		geodesic: true,
		strokeColor: '#FF0000',
		strokeOpacity: 1.0,
		strokeWeight: 2
	});
	
	polygon.setMap(map);
}

  
function initialize()
{
  map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

  google.maps.event.addListener(map, 'click', function(event)
  {
	var lat = event.latLng.lat();
    var lng = event.latLng.lng();
	var point = new google.maps.LatLng(lat, lng);
	if (points.length > 0 && calculateDistance(point, points[0]) < POINTS_MERGE_ACCURACY )
	{
		// we are done send ajax call here
		console.log(points);
		alert('polygon closed.  send ajax call here.');
	}
	else
	{
		// add point to global points array and re-draw the polygon
		points.push(point);
		drawPolygon();
	}
  });
  
  google.maps.event.addListener(map, 'rightclick', function(event)
  {
	  points = [];
	  drawPolygon();
  });
 }


// add event to call initialize() when DOM  loading is done (similar to $(document).ready())
google.maps.event.addDomListener(window, 'load', initialize);