<!DOCTYPE html>
<html>
<head><title>Static Jake</title></head>
<body>
<strong>Static Jake: TramTracker made simple</strong>
<?php

$stopid = $_REQUEST['id'];
$routeid = $_REQUEST['route'];
$about = $_REQUEST['about'];

if (isset($_REQUEST['about']))
{
	drawAbout();
}
else if (isset($_REQUEST['id']))
{
	if (!is_numeric($stopid))
	{
		echo '<p>Invalid stop ID given!</p>';
		drawReturnLink();
	}
	else
	{
		if (strlen($routeid) == 0)
		{
			$routeid = 0;
		}
		if (!is_numeric($routeid))
		{
			echo '<p>Invalid route number given!</p>';
			drawReturnLink();
		}
		else
		{
			drawStopData($stopid, $routeid);
		}
	}
}
else
{
	drawHome();
}

function drawStopData($stopid, $routeid)
{
	$timestamp = time();
	$nexttrams = array();
	
	$timesurl = "http://www.tramtracker.com/Controllers/GetNextPredictionsForStop.ashx?stopNo=$stopid&routeNo=$routeid&isLowFloor=false&ts=$timestamp";
	$timesjson = file_get_contents($timesurl);
	$timesresults = json_decode($timesjson)->responseObject;
	
	$infourl = "http://tramtracker.com/Controllers/GetStopInformation.ashx?s=$stopid";
	$infojson = file_get_contents($infourl);
	$inforesults = json_decode($infojson)->ResponseObject;

?>
<p>Stop <?php echo $inforesults->FlagStopNo; ?>: <?php echo $inforesults->StopName; ?>, <?php echo $inforesults->CityDirection; ?></p>
<ul>
<?php

	$servicesdata = array();
	$includelinks = false;
	$lastroute = -1;

	foreach($timesresults as $tramservice)
	{
		// parse out the timestamp part
		preg_match('/(\d{10})(\d{3})([\+\-]\d{4})/', $tramservice->PredictedArrivalDateTime, $matches);
		// Get the timestamp as the TS tring / 1000
		$predicted = (int) $matches[1];
		
		// Convert to minutes, and round down
		$minutesuntil = floor(($predicted - $timestamp) / 60);
		
		// Keep track of different routes
		$serviceroute = $tramservice->HeadBoardRouteNo;
		$newroute = ($lastroute != $serviceroute);
		
		// only want links for more than on route
		if ($newroute && $lastroute > 0)
		{
			$includelinks = true;
		}
		$lastroute = $serviceroute;
		
		array_push($servicesdata, array('newroute' => $newroute, 'serviceroute' => $serviceroute , 'minutesuntil' => $minutesuntil ));
	}
	
	foreach($servicesdata as $tramservice)
	{		
		$routetitle = "Route " . $tramservice['serviceroute'];		
		if ($includelinks && $tramservice['newroute'])
		{
			$routetitle = "<a href=\"?id=" . $stopid . "&route=" . $tramservice['serviceroute'] . "\">$routetitle</a>";
		}
?>
<li><?php echo $routetitle ?>: <?php echo $tramservice['minutesuntil'] ?> minutes</li>
<?php

	}
	
?>
</ul>
<a href="?">Return</a>
<?php
}

function drawReturnLink()
{
?>
<a href="?">Return</a>
<?php
}

function drawHome()
{
?>
<form action="" method="get">
<label for="id">TramTracker ID</label>
<input id="id" name="id" maxlength="4" size="4" />
<button type="submit">Go</button></br>
<a href="?about">About</a>
<?php
}

function drawAbout()
{
?>
<p>The full version of TramTracker <a href="http://wongm.com/2013/12/yarra-trams-tramtracker-website-broken/">doesn't work on ancient mobile phones</a>.</p>
<p>This website uses the same TramTracker API, and presents the data in a lightweight way.</p>
<p>Created by Marcus Wong (<a href="http://wongm.com/">http://wongm.com/</a>)</p>
<a href="?">Return</a>
<?php
}
?>
</body>
</html>