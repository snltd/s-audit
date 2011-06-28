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

//-----------------------------------------------------------------------------
// SCRIPT STARTS HERE

$map = new ZoneMap();

// $z1 and $z2 are the zones to compare. We can get them from _GET or _POST.

if (isset($_GET["z1"]))
	$in = $_GET;
elseif(isset($_POST["g"]))
	$in = $_POST;
else {
	
	// Looks like we don't have z1 set. Show the friends list and the
	// compare selector

	$pg = new compareListPage("Host Comparison Tool", $map);
	echo $pg->ff_list();
	$pg->close_page();
	exit();
}

$pg = new comparePage("Comparing $in[z1] and $in[z2]", false);

if (isset($in["z1"]) && isset($in["z2"])) {
	$z1 = $in["z1"];
	$z2 = $in["z2"];

// Do a few sanity checks

	if (!$map->has_data($z1))
		$pg->error("no audit data for $z1");

	if (!$map->has_data($z2))
		$pg->error("no audit data for $z2");

	if ($z1 == $z2)
		$pg->error("You can't compare a zone with itself");

	// Get the zone data. If both zones are global, don't bother with the
	// locals

	if ($map->is_global($z1) && $map->is_global($z2))
		define("NO_ZONES", 1);

	$data = new GetServers($map, array($z1, $z2), array("os", "net", "fs",
	"app", "tool", "hosted", "security", "patch" ));

	$view = new compareView($data->get_array(), $map);

	echo $view->show_grid("40%", true);
}
else
	$pg->error("need two zones");

$pg->close_page();

?>
