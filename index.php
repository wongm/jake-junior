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
	global $proxyUrl;
	
	$timestamp = time();
	$nexttrams = array();
	$melbournetimezone = new DateTimeZone('Australia/Melbourne');
	
	$infourl = $proxyUrl . "https://tramtracker.com.au/Controllers/GetStopInformation.ashx?s=$stopid";
	$infoRequest = curl_init($infourl);
	curl_setopt($infoRequest, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($infoRequest, CURLOPT_HEADER, 0);
	curl_setopt($infoRequest, CURLOPT_CONNECTTIMEOUT, 1); 
	curl_setopt($infoRequest, CURLOPT_TIMEOUT, 10); //timeout in seconds

	$infojson = curl_exec($infoRequest);
	curl_close($infoRequest);
	
	$inforesults = json_decode($infojson);
	
	if ($inforesults->HasError)
	{
		drawErrorLink('stop ID');
		return;
	}
	
	if ($routeid == 0)
	{
		// get list of routes passing
		$routesurl = $proxyUrl . "https://tramtracker.com.au/Controllers/GetPassingRoutes.ashx?s=$stopid";
		$routesRequest = curl_init($routesurl);
		curl_setopt($routesRequest, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($routesRequest, CURLOPT_HEADER, 0);
		curl_setopt($routesRequest, CURLOPT_CONNECTTIMEOUT, 1); 
		curl_setopt($routesRequest, CURLOPT_TIMEOUT, 10); //timeout in seconds

		$routesjson = curl_exec($routesRequest);
		curl_close($routesRequest);	
		
		$routesresults = json_decode($routesjson);	
		
		if (sizeof($routesresults->ResponseObject) == 1)
		{
			$routeid = $routesresults->ResponseObject[0]->RouteNo;
		}
		else
		{
			foreach ($routesresults->ResponseObject as $routesresult)
			{
				echo "<p><a href=\"?id=" . $stopid . "&route=" . $routesresult->RouteNo . "\">Route " . $routesresult->RouteNo . "</a></p>";
			}
			return;
		}
	}
	$timesurl = $proxyUrl . "https://tramtracker.com.au/Controllers/GetNextPredictionsForStop.ashx?stopNo=$stopid%26routeNo=$routeid%26isLowFloor=false%26ts=$timestamp";
	
	$timesRequest = curl_init($timesurl);
	curl_setopt($timesRequest, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($timesRequest, CURLOPT_HEADER, 0);
	curl_setopt($timesRequest, CURLOPT_CONNECTTIMEOUT, 1); 
	curl_setopt($timesRequest, CURLOPT_TIMEOUT, 10); //timeout in seconds

	$timesjson = curl_exec($timesRequest);
	curl_close($timesRequest);	
	
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

	foreach($timesresults->responseObject as $tramservice)
	{
		// Parse out the timestamp part
		preg_match('/(\d{10})(\d{3})([\+\-]\d{4})/', $tramservice->PredictedArrivalDateTime, $matches);
		// Get the timestamp as the TS tring / 1000
		$predicted = (int) $matches[1];
		
		// Convert to minutes, and round down
		$minutesuntil = floor(($predicted - $timestamp) / 60);
		
		// Format differently if a long wait
		if ($minutesuntil > 59)
		{
			$formattedprediction = new DateTime();
			$formattedprediction->setTimestamp($predicted);
			$formattedprediction->setTimezone($melbournetimezone);
			$minutesmessage = $formattedprediction->format('g:i a');
		}
		else
		{
			$minutesmessage = "$minutesuntil minutes";
		}
		
		$serviceroute = $tramservice->HeadBoardRouteNo;
		array_push($servicesdata, array('serviceroute' => $serviceroute , 'minutesmessage' => $minutesmessage ));
	}
	
	foreach($servicesdata as $tramservice)
	{
		$routetitle = "Route " . $tramservice['serviceroute'];
?>
<li><?php echo $routetitle ?>: <?php echo $tramservice['minutesmessage'] ?></li>
<?php

	}
	
	$formattedtimestamp = new DateTime();
	$formattedtimestamp->setTimestamp($timestamp);
	$formattedtimestamp->setTimezone($melbournetimezone);
	$timestampmessage = $formattedtimestamp->format('j M Y g:i:s a');
	
?>
</ul>
<p>Retrieved <?php echo $timestampmessage ?></p>
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