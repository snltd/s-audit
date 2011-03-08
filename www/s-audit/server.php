<?php

//============================================================================
//
// server.php
// ----------
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

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

// This page can be called in two different contexts. If the $s variable is
// get in the $_GET array, we view the server with that name. If not, we
// present a list of all the servers we know about

$server = (isset($_GET["s"])) ? $_GET["s"] : false;
$map = new ZoneMap(LIVE_DIR);

if (($server)) {

//- single server view ---------------------------------------------------------

	$pg = new ssPage($server, false);
	
	// parse all the audit files relating to this server, and create a new
	// serverview object, passing only the relevant server array. (It's the
	// entire contents of the array, just buried a couple of layers down.)

	$data = new GetServers($map, $server);
	echo "<prE>", print_r($data), "</pre>";
	//$view = new serverView($server, $data->all_data, $map);
	define("SINGLE_VIEW", 1);
}

else {

//- list of servers view -------------------------------------------------------

	$pg = new ssPage("single server view", 1);

	// Create a getServers object to complete the map. We can destroy it
	// straight away

	$s = new getServers($map);
	unset($s);

	$view = new serverListGrid($map);
	echo "<p class=\"center\">Click a server or zone name for a full
	overview.</p>";
}

echo $view->show_grid();
$pg->close_page();

?>
