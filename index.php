<!DOCTYPE html>
<html>
<head>
<title>Jake Junior</title>
<meta name="description" property="og:description" content="TramTracker for Dumbphones" />
<meta property="og:title" content="Jake Junior" />
<meta property="og:image" content="http://jakejunior.wongm.com/largetile.png" />
</head>
<body>
<strong>Jake Junior: TramTracker for Dumbphones</strong>
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
		drawErrorLink('stop ID');
	}
	else
	{
		if (strlen($routeid) == 0)
		{
			$routeid = 0;
		}
		if (!is_numeric($routeid))
		{
			drawErrorLink('route number');
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
	
	$infourl = "http://tramtracker.com/Controllers/GetStopInformation.ashx?s=$stopid";
	$infojson = file_get_contents($infourl);
	$inforesults = json_decode($infojson);
	
	if ($inforesults->HasError)
	{
		drawErrorLink('stop ID');
		return;
	}
	
	$timesurl = "http://www.tramtracker.com/Controllers/GetNextPredictionsForStop.ashx?stopNo=$stopid&routeNo=$routeid&isLowFloor=false&ts=$timestamp";
	$timesjson = file_get_contents($timesurl);
	$timesresults = json_decode($timesjson);
	
	if ($timesresults->hasError)
	{
		drawErrorLink('stop ID');
		return;
	}
	
	if ($timesresults->responseObject == null)
	{
		drawErrorLink('stop ID and route number combination');
		return;
	}
	
?>
<p>Stop <?php echo $inforesults->ResponseObject->FlagStopNo; ?>: <?php echo $inforesults->ResponseObject->StopName; ?>, <?php echo $inforesults->ResponseObject->CityDirection; ?></p>
<ul>
<?php

	$servicesdata = array();
	$includelinks = false;
	$lastroute = -1;

	foreach($timesresults->responseObject as $tramservice)
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
<p>Retrieved <?php echo date('j M Y g:i:s A', $timestamp) ?></p>
<a href="/">Return</a>
<?php
}

function drawErrorLink($troublesome)
{
?>
<p>Invalid <?php echo $troublesome ?> given!</p>
<a href="/">Return</a>
<?php
}

function drawHome()
{
?>
<form action="/" method="get">
<label for="id">TramTracker ID</label>
<input type="number" id="id" name="id" maxlength="4" size="4" />
<input type="submit" value="Go" /></br>
</form>
<a href="?about">About</a>
<?php
}

function drawAbout()
{
?>
<p>Created by Marcus Wong (<a href="http://wongm.com/">http://wongm.com/</a>) because the full version of TramTracker <a href="http://wongm.com/2013/12/yarra-trams-tramtracker-website-broken/">doesn't work on ancient mobile phones</a>.</p>
<p>In the backend the same TramTracker API is used, but the data is presented in a lightweight way. You can find the PHP source code at <a href="https://github.com/wongm/jake-junior">https://github.com/wongm/jake-junior</a>.</p>
<p>The favicon is a <a href="https://commons.wikimedia.org/wiki/File:BSicon_TRAM.svg">public domain image by Seo75</a>.</p>
<a href="/">Return</a>
<?php
}
?>
</body>
</html>