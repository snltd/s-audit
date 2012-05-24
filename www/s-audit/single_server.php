<?php

//============================================================================
//
// s-audit/server.php
// ------------------
//
// Show everything we know about our single server friends.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/display_classes.php");
require_once(LIB . "/server_view_classes.php");

define("SINGLE_SERVER", 1);

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

// This page can be called in two different contexts. If the $s variable is
// get in the $_GET array, we view the server with that name. If not, we
// present a list of all the servers we know about

$server = (isset($_GET["s"])) ? $_GET["s"] : false;

$map = ZoneMap::getInstance();

if (($server)) {

//- single server view ---------------------------------------------------------

	$pg = new ssPage($server, false);
	
	// Get everything related to this server/zone. We don't want local zones
	// if this is a global. If more audit classes are added, they need to go
	// in this array. GetServers always gets platform data, so no need to
	// specify it here

	// If this is a global, no need to get the locals, but if not, get the
	// parent information

	if ($map->is_global($server)) {
		define("NO_ZONES", 1);
		$s_list = array($server);
	}
	else
		$s_list = array($server, preg_replace("/.*@/", "", $server));

	$data = new GetServers($map, $server, array("os", "net", "fs", "app",
	"tool", "hosted", "security", "patch"));

	$view = new serverView($map, $data->get_array());
}

else {

//- list of servers view -------------------------------------------------------

	$pg = new ssPage("single server view", 1);

	// Create a getServers object to complete the map. We can destroy it
	// straight away

	// Disregard "hide local zones" flag

	if (isset($_GET["h"])) unset($_GET["h"]);

	$s = new getServers($map);
	unset($s);

	$view = new serverListGrid($map);
	echo "<p class=\"center\">Click a server or zone name for a full
	overview.</p>";
}

echo $view->show_grid(), $pg->spacer(), $pg->close_page();

?>
