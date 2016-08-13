<?php

function getpoisInPolygon( array $points )
{
	global $db;
	
	// We will be adding points to this array here as we confirm that they fall within our polygon.
	// var is also used to return empty array in case our validations fail
	$out = [];

	
	// check that we got at least 3 points
	if (count($points) < 3)
		return $out;
	
	// validate that all points are array of 2 numeric elements each (lat, lng)
	// validations here also protect from SQL injectons later
	foreach ($points as $point)
	{
		if (!is_array($point) || count($point) != 2 || !is_numeric($point[0]) || !is_numeric($point[1]))
			return $out;
	}
	
	
	// validate that last point is equal to first, thus polygon is closed
	$first = $points[0];
	$last = end($points);
	if ($first[0] != $last[0] || $first[1] != $last[1])
		return $out;
	
	
	// get min and max for lat and long forming the bounding rectangle of the polygon
	// use these in our sql query so we don't have to process all pois if they fall within
	// the rectangle and save cpu time
	$boundSW = [9999,9999];
	$boundNE = [-9999,-9999];
	foreach ($points as $point)
	{
		if ($point[0] < $boundSW[0])
			$boundSW[0] = $point[0];
		if ($point[0] > $boundNE[0])
			$boundNE[0] = $point[0];
		if ($point[1] < $boundSW[1])
			$boundSW[1] = $point[1];
		if ($point[1] > $boundNE[1])
			$boundNE[1] = $point[1];
	}
		
	// calculate and cache dLat / dLng
	// we need this for our calculations later on each poi
	// you will need the d later.
	$d = [];
	for ($i = 0; $i < count($points) - 1; $i++)
	{
		$dLat = $points[$i+1][0] - $points[$i][0];
		$dLng = $points[$i+1][1] - $points[$i][1];
		$d[] = $dLat / $dLng;
	}
	
	// this is an "inner" query.  fetches pois if their center falls within our polygon
	// we can replace check lat and lng with the respective columns for bounds for an "expanded" search
	// i.e. any poi that falls even a tiny bit within our polygon.
	$sql = "SELECT id, lat, lng FROM poi WHERE lat BETWEEN {$boundSW[0]} AND {$boundNE[0]} AND lng BETWEEN {$boundSW[1]} AND {$boundNE[1]}";
	$result = $db->query($sql);
	
	// reference this URL if you want to understand how this algorithm works:
	// http://alienryderflex.com/polygon/
	while ($poi = $result->fetch_assoc())
	{
		$left = 0;
		$right = 0;
		// told you need the d.
		for ($i = 0; $i < count($d); $i++)
		{
			if ($poi['lat'] < $points[$i][0] && $poi['lat'] < $points[$i+1][0]) // both vertices above our poi, so don't bother with this edge
				continue;

			if ($poi['lat'] > $points[$i][0] && $poi['lat'] > $points[$i+1][0]) // both vertices below our poi, so don't bother with this edge
				continue;
	
			if ($poi['lng'] < $points[$i][1] && $poi['lng'] < $points[$i+1][1]) // both vertices on the right, so edge surely on the right
				$right++;
			else if ($poi['lng'] > $points[$i][1] && $poi['lng'] > $points[$i+1][1]) // both vertices on the left, so edge surely on the left
				$left++;
			else // tricky part
			{
				// find the point on the edge on the same lat as our poi center
				$dLat = $poi['lat'] - $points[$i][0];
				$lng = $points[$i][1] + $dLat / $d[$i];
				if ($poi['lng'] < $lng)
					$right++;
				else
					$left++;
			}
		}

		if ($left % 2 == 1) // odd number of edges means inside polygon
			$out[] = $poi['id'];
	}
	
	return $out;
}




// test start here

// db conn
$db = new mysqli('localhost', 'root', '', 'adamos');
if($db->connect_errno > 0){
    die('Unable to connect to database [' . $db->connect_error . ']');
}


// test case
$points1 = [
	[35.10793770492938, 33.330416679382324],
	[35.11176436068285, 33.330631256103516],
	[35.11320369845072, 33.3359956741333],
	[35.110886703224935, 33.339171409606934],
	[35.107516410747394, 33.33784103393555],
	[35.1057609948814, 33.333678245544434],
	[35.10793770492938, 33.330416679382324]
];
//$test1 = getpoisInPolygon($points1);
//var_dump($test1);


$points2 = [
	[35.11460790591785, 33.31084728240967],
	[35.12099674433755, 33.31234931945801],
	[35.12317304741078, 33.32110404968262],
	[35.12096164219861, 33.32874298095703],
	[35.11464301079446, 33.32797050476074],
	[35.11102712905342, 33.325910568237305],
	[35.11067606402852, 33.31878662109375],
	[35.11246647984327, 33.312692642211914],
	[35.11460790591785, 33.31084728240967]
];
//$test2 = getpoisInPolygon($points2);
//var_dump($test2);


$points3 = [
	[34.734559229442404, 32.865943908691406],
	[34.73512351314973, 33.11897277832031],
	[34.62981798126, 33.120689392089844],
	[34.630665476776514, 32.86285400390625],
	[34.734559229442404, 32.865943908691406]
];
//$test3 = getpoisInPolygon($points3);
//var_dump($test3)


$points4 = [
	[35.14375729561589, 33.291664123535156],
	[35.143230909101376, 33.30260753631592],
	[35.13454504013576, 33.30277919769287],
	[35.13449239568032, 33.29102039337158],
	[35.14375729561589, 33.291664123535156]
];
$test4 = getpoisInPolygon($points4);
var_dump($test4);
