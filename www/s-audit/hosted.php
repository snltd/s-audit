<?php

//============================================================================
//
// hosted.php
// ----------
//
// Hosted services audit base page.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/display_classes.php");

$map = new ZoneMap(LIVE_DIR);
$s = new GetServers($map, false, array("hosted", "fs"));
$grid = new HostedGrid($map, $s->get_array(), "hosted");

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
