<?php

//============================================================================
//
// index.php
// ---------
//
// Show platform audit data.
//
// R Fisher
//
// v1.0
// Please record changes below.
//
// v1.1  Changed to work with new class layout. RDF 16/02/10
//
// v2.0  Changed from "Hardware" to "platform" RDF 10/12/10
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");
require_once(ROOT . "/_lib/reader_file_classes.php");
require_once(ROOT . "/_lib/display_classes.php");

//----------------------------------------------------------------------------
// SCRIPT STARTS HERE

// Create list of objects of all the servers in the audit directory

$map = new ZoneFileMap(LIVE_DIR);
$s = new GetServersPlatform($map);
$grid = new PlatformGrid($map, $s->get_array());

$pg = new audPage("platform audit", $grid->server_count(),
$grid->zone_toggle());

//-- PLATFORM AUDITS ---------------------------------------------------------

echo $grid->show_grid();

require_once(ROOT . "/_keys/platform_key.php");

$pg->close_page();

?>
