<?php

//============================================================================
//
// s-audit/compare.php
// -------------------
//
// Compare servers so you can see differences and commonalities.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

// Get the classes we need. 

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/reader_file_classes.php");
require_once(LIB . "/display_classes.php");
require_once(LIB . "/compare_classes.php");

define("SINGLE_SERVER", 1);
	// needs to be here so reader_classes doesn't "forget" to offer us
	// friends outside the first PER_PAGE

//-----------------------------------------------------------------------------
// SCRIPT STARTS HERE

$map = ZoneMap::getInstance();

// $d should contain a serialized array of the hosts to compare

if (isset($_GET["d"]))
	$hosts = unserialize(urldecode($_GET["d"]));
elseif(isset($_POST["hosts"]))
	$hosts = $_POST["hosts"];
else {
	
	// Looks like we don't have any hosts to compare. Show the friends list
	// and the compare selector

	$pg = new compareListPage("Host Comparison Tool", $map);
	echo $pg->ff_list();
	$pg->close_page();
	exit();
}

// Generate a nice title for the page

$title = "Comparing $hosts[0]";

for ($i = 1; $i < count($hosts) - 1; $i++) 
	$title .= ", $hosts[$i]";

// Create the page

$pg = new comparePage($title . " and " . $hosts[count($hosts) - 1], false);

// We need at least two hosts

if (count($hosts) < 2)
	$pg->f_error("need a minimum of two hosts to compare");

// We need all the hosts to be different, otherwise what's the point?

if (count($hosts) != count(array_unique($hosts)))
	$pg->f_error("hosts are not unique");

// Echo out the Javascript button to show/hide common data

echo "\n\n<div id=\"togglehidden\"><p><a id=\"displayText\" 
href=\"javascript:toggleCommon();\">hide common data</a></p></div>";

// Get host data

foreach($hosts as $host) {

	// Make sure we have data for the host

	if (!$map->has_data($host))
		$pg->f_error("no audit data for $host");

	// See if we're doing any local zones. If not, we don't bother fetching
	// local host data

	if (!$map->is_global($host)) $locals = true;
}

if (!isset($locals)) define("NO_ZONES", 1);

$data = new GetServers($map, $hosts, array("os", "net", "fs", "app", "tool",
"hosted", "security", "patch" ));

$view = new compareView($data->get_array(), $map);

echo $view->show_grid("40%", true), $pg->spacer();

$pg->close_page();

?>
