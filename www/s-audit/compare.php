<?php

//============================================================================
//
// compare.php
// -----------
//
// Compare servers so you can see differences and commonalities.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

// Get the classes we need. A couple of the compare classes extend a
// Hardware class, so we need the hardware classess too.

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/reader_file_classes.php");
require_once(LIB . "/display_classes.php");
require_once(LIB . "/compare_classes.php");

//-----------------------------------------------------------------------------
// SCRIPT STARTS HERE

$pg = new ssPage("Server comparison tool", false);

$context = (isset($_GET["c"])) ? $_GET["c"] : false;
$z1 = (isset($_GET["z1"])) ? $_GET["z1"] : false;
$z2 = (isset($_GET["z2"])) ? $_GET["z2"] : false;

if (isset($_POST["c"])) 
	$context = $_POST["c"];

if (isset($_POST["z1"]))
	$z1 = $_POST["z1"];

if (isset($_POST["z2"]))
	$z2 = $_POST["z2"];

// Create a zone file map. We need that whatever we're doing

$map = new ZoneMap(LIVE_DIR);

// We also want the list of paired servers every time

//$friends = $map->get_pairs();
$friends = array("cs-w-01" => "cs-w-02");
$pair_list = new CompareList($map, $friends);

switch($context) {

//-- COMPARE TWO ZONES -------------------------------------------------------

	case "compare":

		// Do a few sanity checks

		if (!(isset($z1) && isset($z2)))
			$pg->error("need two zones");

		if (!(is_string($z1) && is_string($z2)))
			$pg->error("undefined zones");

		if (!$map->has_data($z1))
			$pg->error("no audit data for $z1");

		if (!$map->has_data($z2))
			$pg->error("no audit data for $z2");

		if ($z1 == $z2)
			$pg->error("You can't compare a zone with itself");

		// Get the server data.  If we're comparing two global zones, we
		// don't need zone data. The define will be heeded by the
		// parse_m_file() method in GetServers.

    	if ($map->is_global($z1) && $map->is_global($z2))
			define("NO_ZONES", 1);

		$s = new GetServers($map, array($z1, $z2), array("os", "net", "fs",
		"app", "tool", "hosted", "security", "patch" ));

		$servers = $s->get_array();

		$comparison = new compareView($map, $servers);

		echo $comparison->show_grid("40%", true);

		break;

	default:

		
//-- DEFAULT CASE ------------------------------------------------------------

?>

<p>This page lets you perform direct comparisons of zones or servers. Select
a pair of twinned zones from the list below, or use the gadget at the bottom
of the page to select any two zones to compare.</p>

<?php
		// Peel the values off the top of the friends array as defaults for
		// the cycle gadgets

		$z1 = current(array_keys($friends));
		$z2 = current(array_values($friends));
}

echo $pair_list->show_grid(), CompareGrid::compare_bar($map->list_all(),
$z1, $z2);

$pg->close_page();

?>
