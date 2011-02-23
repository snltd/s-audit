<?php

//============================================================================
//
// hosted.php
// ----------
//
// Show hosted services, like web sites and databases.
//
// R Fisher
//
// v1.0
// Please record changes below.
//
// v1.1  Changed to work with new class layout. RDF 16/02/10
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/display_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$map = new ZoneMap(LIVE_DIR);
$s = new GetServersHosted($map);
$grid = new HostedGrid($map, $s->get_array());

$pg = new audPage("hosted services", $grid->server_count(),
$grid->zone_toggle());

$um = "<a href=\"" . DOC_URL . "/extras/uri_map_file.php" .
"\">URI map file</a>";

echo "\n<p class=\"center\">";

echo (file_exists(URI_MAP_FILE))
	? "This table incorporates data from a $um generated at "
	. date("H:i D d/m/Y", filemtime(URI_MAP_FILE))
	: "You do not have a $um";

echo ".</p>", $grid->show_grid();

$pg->close_page();

?>
