<?php

//============================================================================
//
// server.php
// ----------
//
// Show everything we know about a server
//
// R Fisher
//
// v1.0
// Please record changes below.
//
//============================================================================

define("HOST_COLS", 5);
	// How many columns of host names on the default page

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/reader_file_classes.php");
require_once(LIB . "/display_classes.php");
require_once(LIB . "/server_view_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

// This page can be called in two different contexts. If the $s variable is
// get in the $_GET method, we view the server with that name. If not, we
// present a list of all the servers we know about

$server = (isset($_GET["s"])) ? $_GET["s"] : false;

// We want to be able to view live and obsolete servers, so we have to make
// a big merged map

$map = new ZoneFileMap(LIVE_DIR);

//foreach ($map as $k=>$v)
	//$map->$k = $map->$k;

if (($server)) {

//- single server view ---------------------------------------------------------

	$pg = new ssPage($server, false);
	
	// parse all the audit files relating to this server, and create a new
	// serverview object, passing only the relevant server array. (It's the
	// entire contents of the array, just buried a couple of layers down.)

	$data = new GetServerSingle($map, $server);
	$view = new serverView($server, $data->all_data, $map);

	echo $view->show_grid();
}

else {
	$pg = new ssPage("single server view", 1);

//- list of servers view -------------------------------------------------------

	// Open a grid with the merged map we made earlier. We also pass through
	// the live map, so it's possible to distinguish live zones from
	// obsolete ones

	$grid = new serverListGrid($map, $map);

	// We're going to print a key, too.

	$key = new auditKey();

	// Now we can stream everything out.

	echo "<p class=\"center\">Click a server or zone name for a full
	overview.</p>", $grid->show_grid(),
	$key->open_key(),
	$key->key_row("svhn", false, "live servers/global zones"),
	$key->key_row("zhn", false, "live local zones"),
	//$key->key_row("osvhn", false, "obsolete servers/global zones"),
	//$key->key_row("ozhn", false, "obsolete local zones"),
	$key->close_key();
}

$pg->close_page();

?>
